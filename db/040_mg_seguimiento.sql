-- Informes versionados, sesiones/asistencia y resultados MDG I/II.
-- Requiere db/039_mg_calendario_modalidad_plantillas.sql.
USE testdb;

SET @column_exists = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'mg_modalidades' AND column_name = 'requiere_asistencia');
SET @ddl = IF(@column_exists = 0, 'ALTER TABLE mg_modalidades ADD COLUMN requiere_asistencia TINYINT(1) NOT NULL DEFAULT 0 AFTER requiere_descripcion', 'SELECT 1');
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @column_exists = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'mg_modalidades' AND column_name = 'requiere_informes');
SET @ddl = IF(@column_exists = 0, 'ALTER TABLE mg_modalidades ADD COLUMN requiere_informes TINYINT(1) NOT NULL DEFAULT 0 AFTER requiere_descripcion', 'SELECT 1');
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @column_exists = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'mg_modalidades' AND column_name = 'asistencia_minima_pct');
SET @ddl = IF(@column_exists = 0, 'ALTER TABLE mg_modalidades ADD COLUMN asistencia_minima_pct DECIMAL(5,2) NULL AFTER requiere_asistencia', 'SELECT 1');
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

ALTER TABLE mg_seguimiento_hitos
    MODIFY COLUMN estado ENUM('pendiente', 'entregado', 'en_revision', 'observado', 'corregido', 'aprobado', 'completado') NOT NULL DEFAULT 'pendiente';

CREATE TABLE IF NOT EXISTS mg_informes_grado (
    id_informe BIGINT AUTO_INCREMENT PRIMARY KEY,
    id_seguimiento BIGINT NOT NULL,
    estado ENUM('pendiente', 'entregado', 'en_revision', 'observado', 'corregido', 'aprobado') NOT NULL DEFAULT 'pendiente',
    version_actual SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_mg_informe_seguimiento FOREIGN KEY (id_seguimiento) REFERENCES mg_seguimiento_hitos(id_seguimiento) ON DELETE RESTRICT,
    UNIQUE KEY uq_mg_informe_seguimiento (id_seguimiento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS mg_informe_versiones (
    id_version BIGINT AUTO_INCREMENT PRIMARY KEY,
    id_informe BIGINT NOT NULL,
    numero_version SMALLINT UNSIGNED NOT NULL,
    nombre_archivo VARCHAR(255) NOT NULL,
    ruta_archivo VARCHAR(500) NOT NULL,
    hash_archivo CHAR(64) NOT NULL,
    tamano_bytes INT UNSIGNED NOT NULL,
    comentario_estudiante TEXT NULL,
    estado ENUM('entregado', 'en_revision', 'observado', 'corregido', 'aprobado') NOT NULL DEFAULT 'entregado',
    observacion_tutor TEXT NULL,
    subido_por INT NOT NULL,
    subido_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    revisado_por INT NULL,
    revisado_en DATETIME NULL,
    CONSTRAINT fk_mg_version_informe FOREIGN KEY (id_informe) REFERENCES mg_informes_grado(id_informe) ON DELETE RESTRICT,
    CONSTRAINT fk_mg_version_subido_por FOREIGN KEY (subido_por) REFERENCES usuarios(id_usuario) ON DELETE RESTRICT,
    CONSTRAINT fk_mg_version_revisado_por FOREIGN KEY (revisado_por) REFERENCES usuarios(id_usuario) ON DELETE RESTRICT,
    UNIQUE KEY uq_mg_version_numero (id_informe, numero_version),
    UNIQUE KEY uq_mg_version_ruta (ruta_archivo),
    INDEX idx_mg_version_estado (estado, subido_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS mg_sesiones_seguimiento (
    id_sesion BIGINT AUTO_INCREMENT PRIMARY KEY,
    id_trabajo BIGINT NOT NULL,
    id_seguimiento BIGINT NULL,
    id_tutor INT NULL,
    tipo ENUM('tutoria', 'taller', 'seguimiento') NOT NULL DEFAULT 'seguimiento',
    titulo VARCHAR(180) NOT NULL,
    descripcion TEXT NULL,
    inicio DATETIME NOT NULL,
    fin DATETIME NULL,
    modalidad ENUM('presencial', 'virtual') NOT NULL DEFAULT 'presencial',
    ubicacion VARCHAR(255) NULL,
    estado ENUM('programada', 'realizada', 'cancelada') NOT NULL DEFAULT 'programada',
    creado_por INT NOT NULL,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_mg_sesion_trabajo FOREIGN KEY (id_trabajo) REFERENCES mg_trabajos(id_trabajo) ON DELETE RESTRICT,
    CONSTRAINT fk_mg_sesion_hito FOREIGN KEY (id_seguimiento) REFERENCES mg_seguimiento_hitos(id_seguimiento) ON DELETE RESTRICT,
    CONSTRAINT fk_mg_sesion_tutor FOREIGN KEY (id_tutor) REFERENCES tutores(id_tutor) ON DELETE RESTRICT,
    CONSTRAINT fk_mg_sesion_usuario FOREIGN KEY (creado_por) REFERENCES usuarios(id_usuario) ON DELETE RESTRICT,
    INDEX idx_mg_sesion_trabajo_fecha (id_trabajo, inicio, estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS mg_asistencias (
    id_sesion BIGINT NOT NULL,
    id_estudiante INT NOT NULL,
    estado ENUM('pendiente', 'presente', 'ausente', 'justificada') NOT NULL DEFAULT 'pendiente',
    observacion VARCHAR(500) NULL,
    registrado_por INT NULL,
    registrado_en DATETIME NULL,
    PRIMARY KEY (id_sesion, id_estudiante),
    CONSTRAINT fk_mg_asistencia_sesion FOREIGN KEY (id_sesion) REFERENCES mg_sesiones_seguimiento(id_sesion) ON DELETE RESTRICT,
    CONSTRAINT fk_mg_asistencia_estudiante FOREIGN KEY (id_estudiante) REFERENCES estudiantes(id_estudiante) ON DELETE RESTRICT,
    CONSTRAINT fk_mg_asistencia_usuario FOREIGN KEY (registrado_por) REFERENCES usuarios(id_usuario) ON DELETE SET NULL,
    INDEX idx_mg_asistencia_estudiante_estado (id_estudiante, estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS mg_resultados_etapa (
    id_resultado BIGINT AUTO_INCREMENT PRIMARY KEY,
    id_trabajo BIGINT NOT NULL,
    etapa ENUM('mdg1', 'mdg2') NOT NULL,
    estado ENUM('pendiente', 'en_curso', 'observado', 'aprobado', 'reprobado') NOT NULL DEFAULT 'pendiente',
    nota DECIMAL(5,2) NULL,
    observaciones TEXT NULL,
    actualizado_por INT NOT NULL,
    actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_mg_resultado_trabajo FOREIGN KEY (id_trabajo) REFERENCES mg_trabajos(id_trabajo) ON DELETE RESTRICT,
    CONSTRAINT fk_mg_resultado_usuario FOREIGN KEY (actualizado_por) REFERENCES usuarios(id_usuario) ON DELETE RESTRICT,
    CONSTRAINT chk_mg_resultado_nota CHECK (nota IS NULL OR nota BETWEEN 0 AND 100),
    UNIQUE KEY uq_mg_resultado_trabajo_etapa (id_trabajo, etapa)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS mg_resultado_etapa_historial (
    id_evento BIGINT AUTO_INCREMENT PRIMARY KEY,
    id_resultado BIGINT NOT NULL,
    estado_anterior ENUM('pendiente', 'en_curso', 'observado', 'aprobado', 'reprobado') NULL,
    estado_nuevo ENUM('pendiente', 'en_curso', 'observado', 'aprobado', 'reprobado') NOT NULL,
    nota DECIMAL(5,2) NULL,
    observaciones TEXT NULL,
    registrado_por INT NOT NULL,
    registrado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_mg_resultado_hist_resultado FOREIGN KEY (id_resultado) REFERENCES mg_resultados_etapa(id_resultado) ON DELETE RESTRICT,
    CONSTRAINT fk_mg_resultado_hist_usuario FOREIGN KEY (registrado_por) REFERENCES usuarios(id_usuario) ON DELETE RESTRICT,
    INDEX idx_mg_resultado_historial (id_resultado, registrado_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO mg_permisos (codigo, nombre, descripcion) VALUES
    ('mg.seguimiento.gestionar', 'Gestionar seguimiento MG', 'Consultar y gestionar el seguimiento de trabajos MG'),
    ('mg.seguimiento.propios', 'Consultar seguimiento propio como tutor', 'Consultar seguimiento de trabajos asignados al tutor'),
    ('mg.informes.propios', 'Entregar informes MG propios', 'Subir versiones de informes de trabajos propios'),
    ('mg.informes.revisar', 'Revisar informes MG', 'Observar o aprobar informes entregados'),
    ('mg.asistencia.registrar', 'Registrar asistencia MG', 'Programar sesiones y registrar asistencia de integrantes'),
    ('mg.etapas.registrar', 'Registrar resultados MDG I/II', 'Registrar y consultar resultados de etapas MDG')
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), descripcion = VALUES(descripcion), estado = 'activo';

INSERT IGNORE INTO mg_permisos_rol (id_rol, codigo_permiso, permitido)
SELECT id_rol, 'mg.seguimiento.propios', 1 FROM roles WHERE nombre_rol = 'tutor';

INSERT IGNORE INTO mg_permisos_rol (id_rol, codigo_permiso, permitido)
SELECT id_rol, 'mg.informes.propios', 1 FROM roles WHERE nombre_rol = 'estudiante';

INSERT IGNORE INTO mg_permisos_rol (id_rol, codigo_permiso, permitido)
SELECT r.id_rol, p.codigo, 1
FROM roles r CROSS JOIN mg_permisos p
WHERE r.nombre_rol = 'tutor'
  AND p.codigo IN ('mg.informes.revisar', 'mg.asistencia.registrar', 'mg.etapas.registrar');
