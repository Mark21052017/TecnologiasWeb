-- Requisitos de defensa, tribunales, intentos y cierre de trabajos MG.
-- Requiere db/040_mg_seguimiento.sql.
USE testdb;

SET @column_exists = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'mg_modalidades' AND column_name = 'requiere_mdg1');
SET @ddl = IF(@column_exists = 0, 'ALTER TABLE mg_modalidades ADD COLUMN requiere_mdg1 TINYINT(1) NOT NULL DEFAULT 0 AFTER requiere_informes', 'SELECT 1');
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @column_exists = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'mg_modalidades' AND column_name = 'requiere_mdg2');
SET @ddl = IF(@column_exists = 0, 'ALTER TABLE mg_modalidades ADD COLUMN requiere_mdg2 TINYINT(1) NOT NULL DEFAULT 0 AFTER requiere_mdg1', 'SELECT 1');
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @column_exists = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'mg_modalidades' AND column_name = 'requiere_informe_final');
SET @ddl = IF(@column_exists = 0, 'ALTER TABLE mg_modalidades ADD COLUMN requiere_informe_final TINYINT(1) NOT NULL DEFAULT 0 AFTER requiere_mdg2', 'SELECT 1');
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @column_exists = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'mg_modalidades' AND column_name = 'requiere_tribunal');
SET @ddl = IF(@column_exists = 0, 'ALTER TABLE mg_modalidades ADD COLUMN requiere_tribunal TINYINT(1) NOT NULL DEFAULT 0 AFTER requiere_informe_final', 'SELECT 1');
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @column_exists = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'mg_modalidades' AND column_name = 'requiere_defensa');
SET @ddl = IF(@column_exists = 0, 'ALTER TABLE mg_modalidades ADD COLUMN requiere_defensa TINYINT(1) NOT NULL DEFAULT 0 AFTER requiere_tribunal', 'SELECT 1');
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @column_exists = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'mg_modalidades' AND column_name = 'max_defensas');
SET @ddl = IF(@column_exists = 0, 'ALTER TABLE mg_modalidades ADD COLUMN max_defensas TINYINT UNSIGNED NULL AFTER requiere_defensa', 'SELECT 1');
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @column_exists = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'mg_modalidades' AND column_name = 'avance_requerido_defensa');
SET @ddl = IF(@column_exists = 0, 'ALTER TABLE mg_modalidades ADD COLUMN avance_requerido_defensa DECIMAL(5,2) NULL AFTER max_defensas', 'SELECT 1');
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @column_exists = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'mg_modalidades' AND column_name = 'impide_tutor_tribunal');
SET @ddl = IF(@column_exists = 0, 'ALTER TABLE mg_modalidades ADD COLUMN impide_tutor_tribunal TINYINT(1) NOT NULL DEFAULT 1 AFTER avance_requerido_defensa', 'SELECT 1');
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @column_exists = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'mg_modalidades' AND column_name = 'miembros_minimos_tribunal');
SET @ddl = IF(@column_exists = 0, 'ALTER TABLE mg_modalidades ADD COLUMN miembros_minimos_tribunal TINYINT UNSIGNED NULL AFTER impide_tutor_tribunal', 'SELECT 1');
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

ALTER TABLE mg_trabajo_integrantes
    MODIFY COLUMN estado ENUM('activo', 'retirado', 'finalizado') NOT NULL DEFAULT 'activo';

CREATE TABLE IF NOT EXISTS mg_tribunales (
    id_tribunal BIGINT AUTO_INCREMENT PRIMARY KEY,
    id_trabajo BIGINT NOT NULL,
    estado ENUM('activo', 'reemplazado') NOT NULL DEFAULT 'activo',
    tribunal_activo TINYINT GENERATED ALWAYS AS (CASE WHEN estado = 'activo' THEN 1 ELSE NULL END) STORED,
    designado_por INT NOT NULL,
    designado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    finalizado_en DATETIME NULL,
    observacion VARCHAR(1000) NULL,
    CONSTRAINT fk_mg_tribunal_trabajo FOREIGN KEY (id_trabajo) REFERENCES mg_trabajos(id_trabajo) ON DELETE RESTRICT,
    CONSTRAINT fk_mg_tribunal_usuario FOREIGN KEY (designado_por) REFERENCES usuarios(id_usuario) ON DELETE RESTRICT,
    UNIQUE KEY uq_mg_tribunal_activo_trabajo (id_trabajo, tribunal_activo),
    INDEX idx_mg_tribunal_trabajo_estado (id_trabajo, estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS mg_tribunal_miembros (
    id_miembro BIGINT AUTO_INCREMENT PRIMARY KEY,
    id_tribunal BIGINT NOT NULL,
    id_usuario INT NOT NULL,
    rol ENUM('presidente', 'miembro') NOT NULL,
    asignado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_mg_tribunal_miembro_tribunal FOREIGN KEY (id_tribunal) REFERENCES mg_tribunales(id_tribunal) ON DELETE RESTRICT,
    CONSTRAINT fk_mg_tribunal_miembro_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE RESTRICT,
    UNIQUE KEY uq_mg_tribunal_usuario (id_tribunal, id_usuario),
    INDEX idx_mg_tribunal_miembro_usuario (id_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS mg_defensas (
    id_defensa BIGINT AUTO_INCREMENT PRIMARY KEY,
    id_trabajo BIGINT NOT NULL,
    numero_defensa SMALLINT UNSIGNED NOT NULL,
    id_tribunal BIGINT NULL,
    fecha_hora DATETIME NOT NULL,
    ubicacion VARCHAR(255) NULL,
    estado ENUM('programada', 'realizada', 'cancelada') NOT NULL DEFAULT 'programada',
    resultado ENUM('aprobado', 'observado', 'reprobado') NULL,
    nota DECIMAL(5,2) NULL,
    observaciones TEXT NULL,
    programada_por INT NOT NULL,
    creada_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_mg_defensa_trabajo FOREIGN KEY (id_trabajo) REFERENCES mg_trabajos(id_trabajo) ON DELETE RESTRICT,
    CONSTRAINT fk_mg_defensa_tribunal FOREIGN KEY (id_tribunal) REFERENCES mg_tribunales(id_tribunal) ON DELETE RESTRICT,
    CONSTRAINT fk_mg_defensa_usuario FOREIGN KEY (programada_por) REFERENCES usuarios(id_usuario) ON DELETE RESTRICT,
    CONSTRAINT chk_mg_defensa_nota CHECK (nota IS NULL OR nota BETWEEN 0 AND 100),
    UNIQUE KEY uq_mg_defensa_intento (id_trabajo, numero_defensa),
    INDEX idx_mg_defensa_fecha_estado (fecha_hora, estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS mg_defensa_historial (
    id_evento BIGINT AUTO_INCREMENT PRIMARY KEY,
    id_defensa BIGINT NOT NULL,
    accion VARCHAR(40) NOT NULL,
    fecha_anterior DATETIME NULL,
    fecha_nueva DATETIME NULL,
    estado_anterior ENUM('programada', 'realizada', 'cancelada') NULL,
    estado_nuevo ENUM('programada', 'realizada', 'cancelada') NOT NULL,
    resultado ENUM('aprobado', 'observado', 'reprobado') NULL,
    nota DECIMAL(5,2) NULL,
    observaciones TEXT NULL,
    registrado_por INT NOT NULL,
    registrado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_mg_defensa_hist_defensa FOREIGN KEY (id_defensa) REFERENCES mg_defensas(id_defensa) ON DELETE RESTRICT,
    CONSTRAINT fk_mg_defensa_hist_usuario FOREIGN KEY (registrado_por) REFERENCES usuarios(id_usuario) ON DELETE RESTRICT,
    INDEX idx_mg_defensa_historial (id_defensa, registrado_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS mg_cierres (
    id_cierre BIGINT AUTO_INCREMENT PRIMARY KEY,
    id_trabajo BIGINT NOT NULL,
    resultado ENUM('aprobado', 'reprobado', 'retirado') NOT NULL,
    observaciones TEXT NULL,
    cerrado_por INT NOT NULL,
    cerrado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_mg_cierre_trabajo FOREIGN KEY (id_trabajo) REFERENCES mg_trabajos(id_trabajo) ON DELETE RESTRICT,
    CONSTRAINT fk_mg_cierre_usuario FOREIGN KEY (cerrado_por) REFERENCES usuarios(id_usuario) ON DELETE RESTRICT,
    UNIQUE KEY uq_mg_cierre_trabajo (id_trabajo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO mg_permisos (codigo, nombre, descripcion) VALUES
    ('mg.defensas.gestionar', 'Gestionar tribunales y defensas MG', 'Validar requisitos, designar tribunal, programar intentos y registrar resultados'),
    ('mg.cierre.gestionar', 'Cerrar trabajos MG', 'Cerrar procesos MG que completaron los requisitos configurados')
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), descripcion = VALUES(descripcion), estado = 'activo';
