-- Reglas mínimas por modalidad, solicitudes estudiantiles, revisión y habilitación MG.
-- Requiere db/035_mg_administrative_panel.sql.
USE testdb;

SET @column_exists = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'mg_modalidades' AND column_name = 'descripcion');
SET @ddl = IF(@column_exists = 0, 'ALTER TABLE mg_modalidades ADD COLUMN descripcion VARCHAR(1000) NULL AFTER nombre', 'SELECT 1');
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @column_exists = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'mg_modalidades' AND column_name = 'permite_trabajo_grupal');
SET @ddl = IF(@column_exists = 0, 'ALTER TABLE mg_modalidades ADD COLUMN permite_trabajo_grupal TINYINT(1) NOT NULL DEFAULT 0 AFTER requiere_tutor', 'SELECT 1');
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @column_exists = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'mg_modalidades' AND column_name = 'max_integrantes');
SET @ddl = IF(@column_exists = 0, 'ALTER TABLE mg_modalidades ADD COLUMN max_integrantes TINYINT UNSIGNED NULL AFTER permite_trabajo_grupal', 'SELECT 1');
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @column_exists = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'mg_modalidades' AND column_name = 'requiere_tema_preliminar');
SET @ddl = IF(@column_exists = 0, 'ALTER TABLE mg_modalidades ADD COLUMN requiere_tema_preliminar TINYINT(1) NOT NULL DEFAULT 1 AFTER max_integrantes', 'SELECT 1');
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @column_exists = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'mg_modalidades' AND column_name = 'requiere_descripcion');
SET @ddl = IF(@column_exists = 0, 'ALTER TABLE mg_modalidades ADD COLUMN requiere_descripcion TINYINT(1) NOT NULL DEFAULT 1 AFTER requiere_tema_preliminar', 'SELECT 1');
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

CREATE TABLE IF NOT EXISTS mg_solicitudes (
    id_solicitud BIGINT AUTO_INCREMENT PRIMARY KEY,
    id_estudiante INT NOT NULL,
    id_modalidad INT NOT NULL,
    tipo_trabajo ENUM('individual', 'grupal') NOT NULL DEFAULT 'individual',
    tema_preliminar VARCHAR(250) NULL,
    descripcion TEXT NULL,
    observaciones_estudiante TEXT NULL,
    estado ENUM('borrador', 'enviada', 'en_revision', 'observada', 'aprobada', 'rechazada', 'cancelada') NOT NULL DEFAULT 'borrador',
    solicitud_activa TINYINT GENERATED ALWAYS AS (CASE WHEN estado IN ('borrador', 'enviada', 'en_revision', 'observada', 'aprobada') THEN 1 ELSE NULL END) STORED,
    id_plan_verificado BIGINT NULL,
    materias_requeridas_snapshot SMALLINT UNSIGNED NULL,
    materias_aprobadas_snapshot SMALLINT UNSIGNED NULL,
    promedio_snapshot DECIMAL(5,2) NULL,
    verificado_en DATETIME NULL,
    enviado_en DATETIME NULL,
    revisado_por INT NULL,
    revisado_en DATETIME NULL,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_mg_solicitud_estudiante FOREIGN KEY (id_estudiante) REFERENCES estudiantes(id_estudiante) ON DELETE RESTRICT,
    CONSTRAINT fk_mg_solicitud_modalidad FOREIGN KEY (id_modalidad) REFERENCES mg_modalidades(id_modalidad) ON DELETE RESTRICT,
    CONSTRAINT fk_mg_solicitud_plan FOREIGN KEY (id_plan_verificado) REFERENCES mg_planes_estudio(id_plan_estudio) ON DELETE RESTRICT,
    CONSTRAINT fk_mg_solicitud_revisor FOREIGN KEY (revisado_por) REFERENCES usuarios(id_usuario) ON DELETE RESTRICT,
    UNIQUE KEY uq_mg_solicitud_estudiante_activa (id_estudiante, solicitud_activa),
    INDEX idx_mg_solicitud_estado_fecha (estado, enviado_en),
    INDEX idx_mg_solicitud_modalidad (id_modalidad, estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS mg_solicitud_historial (
    id_evento BIGINT AUTO_INCREMENT PRIMARY KEY,
    id_solicitud BIGINT NOT NULL,
    accion VARCHAR(40) NOT NULL,
    estado_anterior ENUM('borrador', 'enviada', 'en_revision', 'observada', 'aprobada', 'rechazada', 'cancelada') NULL,
    estado_nuevo ENUM('borrador', 'enviada', 'en_revision', 'observada', 'aprobada', 'rechazada', 'cancelada') NOT NULL,
    id_actor INT NOT NULL,
    comentario TEXT NULL,
    resumen_academico JSON NULL,
    visible_estudiante TINYINT(1) NOT NULL DEFAULT 1,
    ocurrido_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_mg_solicitud_evento FOREIGN KEY (id_solicitud) REFERENCES mg_solicitudes(id_solicitud) ON DELETE RESTRICT,
    CONSTRAINT fk_mg_solicitud_evento_actor FOREIGN KEY (id_actor) REFERENCES usuarios(id_usuario) ON DELETE RESTRICT,
    INDEX idx_mg_solicitud_eventos (id_solicitud, ocurrido_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

SET @column_exists = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'mg_solicitud_historial' AND column_name = 'visible_estudiante');
SET @ddl = IF(@column_exists = 0, 'ALTER TABLE mg_solicitud_historial ADD COLUMN visible_estudiante TINYINT(1) NOT NULL DEFAULT 1 AFTER resumen_academico', 'SELECT 1');
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

CREATE TABLE IF NOT EXISTS mg_habilitaciones (
    id_habilitacion BIGINT AUTO_INCREMENT PRIMARY KEY,
    id_solicitud BIGINT NOT NULL,
    habilitado_por INT NOT NULL,
    habilitado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    observacion VARCHAR(1000) NULL,
    CONSTRAINT fk_mg_habilitacion_solicitud FOREIGN KEY (id_solicitud) REFERENCES mg_solicitudes(id_solicitud) ON DELETE RESTRICT,
    CONSTRAINT fk_mg_habilitacion_usuario FOREIGN KEY (habilitado_por) REFERENCES usuarios(id_usuario) ON DELETE RESTRICT,
    UNIQUE KEY uq_mg_habilitacion_solicitud (id_solicitud)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO mg_permisos (codigo, nombre, descripcion) VALUES
    ('mg.solicitudes.propias', 'Gestionar solicitud propia', 'Crear, consultar, corregir y cancelar solicitudes MG propias'),
    ('mg.solicitudes.revisar', 'Revisar solicitudes MG', 'Revisar, observar, aprobar o rechazar solicitudes MG'),
    ('mg.habilitaciones.gestionar', 'Habilitar solicitudes MG aprobadas', 'Registrar habilitación formal separada de la aprobación')
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), descripcion = VALUES(descripcion), estado = 'activo';

INSERT IGNORE INTO mg_permisos_rol (id_rol, codigo_permiso, permitido)
SELECT id_rol, 'mg.solicitudes.propias', 1 FROM roles WHERE nombre_rol = 'estudiante';
