-- Importación administrativa de planes de estudio e historiales académicos.
-- Aditiva e idempotente; no altera el flujo de Tutorías.
USE testdb;

CREATE TABLE IF NOT EXISTS mg_importaciones_academicas (
    id_importacion BIGINT AUTO_INCREMENT PRIMARY KEY,
    tipo ENUM('plan_estudio', 'historial') NOT NULL,
    nombre_archivo VARCHAR(255) NOT NULL,
    hash_archivo CHAR(64) NOT NULL,
    ruta_archivo VARCHAR(500) NOT NULL,
    estado ENUM('pendiente', 'aprobada', 'rechazada') NOT NULL DEFAULT 'pendiente',
    total_filas INT UNSIGNED NOT NULL DEFAULT 0,
    filas_validas INT UNSIGNED NOT NULL DEFAULT 0,
    filas_con_error INT UNSIGNED NOT NULL DEFAULT 0,
    importado_por INT NOT NULL,
    revisado_por INT NULL,
    fecha_importacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_revision DATETIME NULL,
    observacion_revision VARCHAR(1000) NULL,
    CONSTRAINT fk_mg_importacion_importador FOREIGN KEY (importado_por) REFERENCES usuarios(id_usuario) ON DELETE RESTRICT,
    CONSTRAINT fk_mg_importacion_revisor FOREIGN KEY (revisado_por) REFERENCES usuarios(id_usuario) ON DELETE RESTRICT,
    UNIQUE KEY uq_mg_importacion_hash_tipo (tipo, hash_archivo),
    INDEX idx_mg_importacion_estado_fecha (estado, fecha_importacion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS mg_importacion_academica_filas (
    id_fila BIGINT AUTO_INCREMENT PRIMARY KEY,
    id_importacion BIGINT NOT NULL,
    numero_fila INT UNSIGNED NOT NULL,
    datos JSON NOT NULL,
    error_validacion VARCHAR(1000) NULL,
    CONSTRAINT fk_mg_importacion_fila FOREIGN KEY (id_importacion) REFERENCES mg_importaciones_academicas(id_importacion) ON DELETE CASCADE,
    UNIQUE KEY uq_mg_importacion_numero_fila (id_importacion, numero_fila)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS mg_planes_estudio (
    id_plan_estudio BIGINT AUTO_INCREMENT PRIMARY KEY,
    id_carrera INT NOT NULL,
    codigo_plan VARCHAR(60) NOT NULL,
    version_plan VARCHAR(40) NOT NULL,
    id_importacion BIGINT NOT NULL,
    aprobado_por INT NOT NULL,
    aprobado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_mg_plan_carrera FOREIGN KEY (id_carrera) REFERENCES carreras(id_carrera) ON DELETE RESTRICT,
    CONSTRAINT fk_mg_plan_importacion FOREIGN KEY (id_importacion) REFERENCES mg_importaciones_academicas(id_importacion) ON DELETE RESTRICT,
    CONSTRAINT fk_mg_plan_aprobador FOREIGN KEY (aprobado_por) REFERENCES usuarios(id_usuario) ON DELETE RESTRICT,
    UNIQUE KEY uq_mg_plan_carrera_codigo_version (id_carrera, codigo_plan, version_plan)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS mg_plan_materias (
    id_plan_estudio BIGINT NOT NULL,
    id_materia INT NOT NULL,
    obligatoria TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id_plan_estudio, id_materia),
    CONSTRAINT fk_mg_plan_materia_plan FOREIGN KEY (id_plan_estudio) REFERENCES mg_planes_estudio(id_plan_estudio) ON DELETE CASCADE,
    CONSTRAINT fk_mg_plan_materia_materia FOREIGN KEY (id_materia) REFERENCES materias(id_materia) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS mg_historial_academico (
    id_historial BIGINT AUTO_INCREMENT PRIMARY KEY,
    id_estudiante INT NOT NULL,
    id_plan_estudio BIGINT NOT NULL,
    id_materia INT NOT NULL,
    estado ENUM('APROBADA', 'REPROBADA') NOT NULL,
    nota DECIMAL(5,2) NOT NULL,
    periodo VARCHAR(40) NOT NULL,
    id_importacion BIGINT NOT NULL,
    actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_mg_historial_estudiante FOREIGN KEY (id_estudiante) REFERENCES estudiantes(id_estudiante) ON DELETE RESTRICT,
    CONSTRAINT fk_mg_historial_plan FOREIGN KEY (id_plan_estudio) REFERENCES mg_planes_estudio(id_plan_estudio) ON DELETE RESTRICT,
    CONSTRAINT fk_mg_historial_materia FOREIGN KEY (id_materia) REFERENCES materias(id_materia) ON DELETE RESTRICT,
    CONSTRAINT fk_mg_historial_importacion FOREIGN KEY (id_importacion) REFERENCES mg_importaciones_academicas(id_importacion) ON DELETE RESTRICT,
    CONSTRAINT chk_mg_historial_nota CHECK (nota BETWEEN 0 AND 100),
    UNIQUE KEY uq_mg_historial_estudiante_materia_periodo (id_estudiante, id_plan_estudio, id_materia, periodo),
    INDEX idx_mg_historial_estudiante_plan_estado (id_estudiante, id_plan_estudio, estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO mg_permisos (codigo, nombre, descripcion) VALUES
    ('mg.academico.importar', 'Importar datos académicos', 'Cargar y revisar archivos CSV de planes e historiales académicos'),
    ('mg.academico.verificar', 'Verificar historial académico', 'Consultar el resumen de verificación académica propio')
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), descripcion = VALUES(descripcion), estado = 'activo';

INSERT IGNORE INTO mg_permisos_rol (id_rol, codigo_permiso, permitido)
SELECT id_rol, 'mg.academico.verificar', 1 FROM roles WHERE nombre_rol = 'estudiante';

INSERT INTO modulos_sistema (clave, nombre, descripcion, orden, estado)
VALUES ('modalidades-grado', 'Modalidades de Grado', 'Gestión independiente de procesos de grado', 140, 'activo')
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), descripcion = VALUES(descripcion), estado = 'activo';
