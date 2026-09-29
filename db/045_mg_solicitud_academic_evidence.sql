-- Private, versioned grade evidence attached to a student MG request.
-- Reuses mg_solicitud_historial for review events; does not import unverified grades into the official transcript.
USE testdb;

CREATE TABLE IF NOT EXISTS mg_solicitud_evidencias (
    id_evidencia BIGINT AUTO_INCREMENT PRIMARY KEY,
    id_solicitud BIGINT NOT NULL,
    id_plan_estudio BIGINT NULL,
    id_carrera INT NULL,
    numero_version SMALLINT UNSIGNED NOT NULL,
    nombre_archivo VARCHAR(255) NOT NULL,
    ruta_archivo VARCHAR(500) NOT NULL,
    hash_archivo CHAR(64) NOT NULL,
    tamano_bytes INT UNSIGNED NOT NULL,
    comentario_estudiante VARCHAR(2000) NULL,
    estado ENUM('pendiente','verificada','observada','rechazada') NOT NULL DEFAULT 'pendiente',
    materias_requeridas SMALLINT UNSIGNED NULL,
    materias_aprobadas SMALLINT UNSIGNED NULL,
    promedio_verificado DECIMAL(5,2) NULL,
    plan_referencia VARCHAR(100) NULL,
    observacion_revision VARCHAR(5000) NULL,
    subido_por INT NOT NULL,
    subido_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    revisado_por INT NULL,
    revisado_en DATETIME NULL,
    CONSTRAINT fk_mg_evidencia_solicitud FOREIGN KEY (id_solicitud) REFERENCES mg_solicitudes(id_solicitud) ON DELETE RESTRICT,
    CONSTRAINT fk_mg_evidencia_plan FOREIGN KEY (id_plan_estudio) REFERENCES mg_planes_estudio(id_plan_estudio) ON DELETE RESTRICT,
    CONSTRAINT fk_mg_evidencia_carrera FOREIGN KEY (id_carrera) REFERENCES carreras(id_carrera) ON DELETE RESTRICT,
    CONSTRAINT fk_mg_evidencia_subido FOREIGN KEY (subido_por) REFERENCES usuarios(id_usuario) ON DELETE RESTRICT,
    CONSTRAINT fk_mg_evidencia_revisado FOREIGN KEY (revisado_por) REFERENCES usuarios(id_usuario) ON DELETE RESTRICT,
    CONSTRAINT chk_mg_evidencia_promedio CHECK (promedio_verificado IS NULL OR promedio_verificado BETWEEN 0 AND 100),
    UNIQUE KEY uq_mg_evidencia_version (id_solicitud, numero_version),
    UNIQUE KEY uq_mg_evidencia_ruta (ruta_archivo),
    INDEX idx_mg_evidencia_revision (estado, subido_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

SET @nullable = (SELECT IS_NULLABLE FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='mg_solicitud_evidencias' AND column_name='id_plan_estudio');
SET @ddl = IF(@nullable='NO', 'ALTER TABLE mg_solicitud_evidencias MODIFY COLUMN id_plan_estudio BIGINT NULL', 'SELECT 1');
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @column_exists = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='mg_solicitud_evidencias' AND column_name='id_carrera');
SET @ddl = IF(@column_exists=0, 'ALTER TABLE mg_solicitud_evidencias ADD COLUMN id_carrera INT NULL AFTER id_plan_estudio, ADD CONSTRAINT fk_mg_evidencia_carrera FOREIGN KEY (id_carrera) REFERENCES carreras(id_carrera) ON DELETE RESTRICT', 'SELECT 1');
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

UPDATE mg_solicitud_evidencias ev
INNER JOIN mg_planes_estudio p ON p.id_plan_estudio=ev.id_plan_estudio
SET ev.id_carrera=p.id_carrera
WHERE ev.id_carrera IS NULL;

SET @column_exists = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='mg_solicitud_evidencias' AND column_name='plan_referencia');
SET @ddl = IF(@column_exists=0, 'ALTER TABLE mg_solicitud_evidencias ADD COLUMN plan_referencia VARCHAR(100) NULL AFTER promedio_verificado', 'SELECT 1');
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

CREATE TABLE IF NOT EXISTS mg_solicitud_evidencia_materias (
    id_evidencia BIGINT NOT NULL,
    id_materia INT NOT NULL,
    estado ENUM('APROBADA','REPROBADA') NOT NULL,
    nota DECIMAL(5,2) NOT NULL,
    periodo VARCHAR(40) NULL,
    PRIMARY KEY (id_evidencia,id_materia),
    CONSTRAINT fk_mg_evidencia_materia_evidencia FOREIGN KEY (id_evidencia) REFERENCES mg_solicitud_evidencias(id_evidencia) ON DELETE RESTRICT,
    CONSTRAINT fk_mg_evidencia_materia_materia FOREIGN KEY (id_materia) REFERENCES materias(id_materia) ON DELETE RESTRICT,
    CONSTRAINT chk_mg_evidencia_nota CHECK (nota BETWEEN 0 AND 100)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS mg_solicitud_evidencia_materias_libres (
    id_evidencia BIGINT NOT NULL,
    nombre_materia VARCHAR(150) NOT NULL,
    estado ENUM('APROBADA','REPROBADA') NOT NULL,
    nota DECIMAL(5,2) NOT NULL,
    periodo VARCHAR(40) NULL,
    PRIMARY KEY (id_evidencia,nombre_materia),
    CONSTRAINT fk_mg_evidencia_materia_libre FOREIGN KEY (id_evidencia) REFERENCES mg_solicitud_evidencias(id_evidencia) ON DELETE RESTRICT,
    CONSTRAINT chk_mg_evidencia_nota_libre CHECK (nota BETWEEN 0 AND 100)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

SET @column_exists = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='mg_solicitudes' AND column_name='fuente_verificacion_academica');
SET @ddl = IF(@column_exists=0, 'ALTER TABLE mg_solicitudes ADD COLUMN fuente_verificacion_academica ENUM(''historial_oficial'',''documento'') NULL AFTER verificado_en', 'SELECT 1');
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @column_exists = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='mg_solicitudes' AND column_name='id_evidencia_verificada');
SET @ddl = IF(@column_exists=0, 'ALTER TABLE mg_solicitudes ADD COLUMN id_evidencia_verificada BIGINT NULL AFTER fuente_verificacion_academica', 'SELECT 1');
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @constraint_exists = (SELECT COUNT(*) FROM information_schema.table_constraints WHERE constraint_schema=DATABASE() AND table_name='mg_solicitudes' AND constraint_name='fk_mg_solicitud_evidencia_verificada');
SET @ddl = IF(@constraint_exists=0, 'ALTER TABLE mg_solicitudes ADD CONSTRAINT fk_mg_solicitud_evidencia_verificada FOREIGN KEY (id_evidencia_verificada) REFERENCES mg_solicitud_evidencias(id_evidencia) ON DELETE SET NULL', 'SELECT 1');
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;
