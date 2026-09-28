-- Calendarios por cohorte/modalidad, plantillas y obligaciones por trabajo.
-- Requiere db/038_mg_tutor_assignments.sql.
USE testdb;

SET @column_exists = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'mg_calendario' AND column_name = 'id_modalidad');
SET @ddl = IF(@column_exists = 0, 'ALTER TABLE mg_calendario ADD COLUMN id_modalidad INT NULL AFTER id_cohorte', 'SELECT 1');
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

ALTER TABLE mg_calendario
    MODIFY COLUMN etapa ENUM('previa', 'mg1', 'mg2', 'defensa', 'cierre', 'finalizado') NOT NULL;

SET @index_exists = (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'mg_calendario' AND index_name = 'idx_mg_calendario_cohorte_modalidad');
SET @ddl = IF(@index_exists = 0, 'CREATE INDEX idx_mg_calendario_cohorte_modalidad ON mg_calendario (id_cohorte, id_modalidad, estado, orden)', 'SELECT 1');
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @constraint_exists = (SELECT COUNT(*) FROM information_schema.table_constraints WHERE constraint_schema = DATABASE() AND table_name = 'mg_calendario' AND constraint_name = 'fk_mg_calendario_modalidad');
SET @ddl = IF(@constraint_exists = 0, 'ALTER TABLE mg_calendario ADD CONSTRAINT fk_mg_calendario_modalidad FOREIGN KEY (id_modalidad) REFERENCES mg_modalidades(id_modalidad) ON DELETE RESTRICT', 'SELECT 1');
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

CREATE TABLE IF NOT EXISTS mg_tipos_hito (
    codigo VARCHAR(40) NOT NULL PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    estado ENUM('activo', 'inactivo') NOT NULL DEFAULT 'activo',
    orden SMALLINT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO mg_tipos_hito (codigo, nombre, orden) VALUES
    ('taller', 'Taller', 10),
    ('entrega', 'Entrega', 20),
    ('informe', 'Informe', 30),
    ('evaluacion_etapa', 'Evaluación de etapa', 40),
    ('asignacion_tutor', 'Asignación de tutor', 50),
    ('asignacion_tribunal', 'Asignación de tribunal', 60),
    ('revision', 'Revisión', 70),
    ('defensa', 'Defensa', 80),
    ('ingreso_mg2', 'Ingreso a MDG II', 90),
    ('cierre', 'Cierre', 100),
    ('otro', 'Otro', 110)
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), estado = 'activo', orden = VALUES(orden);

CREATE TABLE IF NOT EXISTS mg_plantillas_calendario (
    id_plantilla BIGINT AUTO_INCREMENT PRIMARY KEY,
    id_modalidad INT NOT NULL,
    nombre VARCHAR(150) NOT NULL,
    descripcion VARCHAR(1000) NULL,
    estado ENUM('activa', 'inactiva') NOT NULL DEFAULT 'activa',
    creado_por INT NOT NULL,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_mg_plantilla_modalidad FOREIGN KEY (id_modalidad) REFERENCES mg_modalidades(id_modalidad) ON DELETE RESTRICT,
    CONSTRAINT fk_mg_plantilla_usuario FOREIGN KEY (creado_por) REFERENCES usuarios(id_usuario) ON DELETE RESTRICT,
    UNIQUE KEY uq_mg_plantilla_modalidad_nombre (id_modalidad, nombre),
    INDEX idx_mg_plantillas_estado (id_modalidad, estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS mg_plantilla_hitos (
    id_plantilla_hito BIGINT AUTO_INCREMENT PRIMARY KEY,
    id_plantilla BIGINT NOT NULL,
    etapa ENUM('previa', 'mg1', 'mg2', 'defensa', 'cierre', 'finalizado') NOT NULL,
    tipo VARCHAR(40) NOT NULL,
    nombre VARCHAR(180) NOT NULL,
    orden SMALLINT NOT NULL DEFAULT 0,
    dias_desde_inicio INT UNSIGNED NULL,
    avance_esperado_pct DECIMAL(5,2) NULL,
    estado ENUM('activo', 'inactivo') NOT NULL DEFAULT 'activo',
    CONSTRAINT fk_mg_plantilla_hito_plantilla FOREIGN KEY (id_plantilla) REFERENCES mg_plantillas_calendario(id_plantilla) ON DELETE CASCADE,
    CONSTRAINT fk_mg_plantilla_hito_tipo FOREIGN KEY (tipo) REFERENCES mg_tipos_hito(codigo) ON DELETE RESTRICT,
    CONSTRAINT chk_mg_plantilla_hito_avance CHECK (avance_esperado_pct IS NULL OR avance_esperado_pct BETWEEN 0 AND 100),
    INDEX idx_mg_plantilla_hito_orden (id_plantilla, estado, orden)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS mg_seguimiento_hitos (
    id_seguimiento BIGINT AUTO_INCREMENT PRIMARY KEY,
    id_hito INT NOT NULL,
    id_trabajo BIGINT NOT NULL,
    estado ENUM('pendiente', 'entregado', 'observado', 'aprobado', 'completado') NOT NULL DEFAULT 'pendiente',
    fecha_limite DATE NULL,
    fecha_entrega DATETIME NULL,
    avance_real_pct DECIMAL(5,2) NULL,
    observacion_estudiante TEXT NULL,
    observacion_tutor TEXT NULL,
    revisado_por INT NULL,
    revisado_en DATETIME NULL,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_mg_seguimiento_hito FOREIGN KEY (id_hito) REFERENCES mg_calendario(id_hito) ON DELETE RESTRICT,
    CONSTRAINT fk_mg_seguimiento_trabajo FOREIGN KEY (id_trabajo) REFERENCES mg_trabajos(id_trabajo) ON DELETE RESTRICT,
    CONSTRAINT fk_mg_seguimiento_revisor FOREIGN KEY (revisado_por) REFERENCES usuarios(id_usuario) ON DELETE RESTRICT,
    CONSTRAINT chk_mg_seguimiento_avance CHECK (avance_real_pct IS NULL OR avance_real_pct BETWEEN 0 AND 100),
    UNIQUE KEY uq_mg_seguimiento_hito_trabajo (id_hito, id_trabajo),
    INDEX idx_mg_seguimiento_trabajo_estado (id_trabajo, estado),
    INDEX idx_mg_seguimiento_vencimiento (estado, fecha_limite)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO mg_permisos (codigo, nombre, descripcion) VALUES
    ('mg.calendario.plantillas', 'Gestionar plantillas de calendario MG', 'Crear plantillas por modalidad y aplicarlas a cohortes'),
    ('mg.hitos.entregar_propio', 'Entregar hitos del trabajo propio', 'Registrar entrega y avance de hitos de trabajos propios')
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), descripcion = VALUES(descripcion), estado = 'activo';

INSERT IGNORE INTO mg_permisos_rol (id_rol, codigo_permiso, permitido)
SELECT id_rol, 'mg.hitos.entregar_propio', 1 FROM roles WHERE nombre_rol = 'estudiante';
