-- Inscripción formal posterior a habilitación, cohorte y trabajo individual/grupal.
-- Requiere db/036_mg_solicitudes.sql.
USE testdb;

CREATE TABLE IF NOT EXISTS mg_trabajos (
    id_trabajo BIGINT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(40) NULL UNIQUE,
    id_cohorte INT NOT NULL,
    id_modalidad INT NOT NULL,
    tipo_trabajo ENUM('individual', 'grupal') NOT NULL,
    tema VARCHAR(250) NULL,
    descripcion TEXT NULL,
    estado ENUM('activo', 'finalizado', 'cancelado') NOT NULL DEFAULT 'activo',
    creado_por INT NOT NULL,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_mg_trabajo_cohorte FOREIGN KEY (id_cohorte) REFERENCES mg_cohortes(id_cohorte) ON DELETE RESTRICT,
    CONSTRAINT fk_mg_trabajo_modalidad FOREIGN KEY (id_modalidad) REFERENCES mg_modalidades(id_modalidad) ON DELETE RESTRICT,
    CONSTRAINT fk_mg_trabajo_usuario FOREIGN KEY (creado_por) REFERENCES usuarios(id_usuario) ON DELETE RESTRICT,
    INDEX idx_mg_trabajo_cohorte_modalidad (id_cohorte, id_modalidad, estado),
    INDEX idx_mg_trabajo_tema (tema)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS mg_inscripciones (
    id_inscripcion BIGINT AUTO_INCREMENT PRIMARY KEY,
    id_solicitud BIGINT NOT NULL,
    id_estudiante INT NOT NULL,
    id_cohorte INT NOT NULL,
    estado ENUM('activa', 'retirada', 'finalizada') NOT NULL DEFAULT 'activa',
    inscripcion_activa TINYINT GENERATED ALWAYS AS (CASE WHEN estado = 'activa' THEN 1 ELSE NULL END) STORED,
    inscrito_por INT NOT NULL,
    inscrito_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    observacion VARCHAR(1000) NULL,
    CONSTRAINT fk_mg_inscripcion_solicitud FOREIGN KEY (id_solicitud) REFERENCES mg_solicitudes(id_solicitud) ON DELETE RESTRICT,
    CONSTRAINT fk_mg_inscripcion_estudiante FOREIGN KEY (id_estudiante) REFERENCES estudiantes(id_estudiante) ON DELETE RESTRICT,
    CONSTRAINT fk_mg_inscripcion_cohorte FOREIGN KEY (id_cohorte) REFERENCES mg_cohortes(id_cohorte) ON DELETE RESTRICT,
    CONSTRAINT fk_mg_inscripcion_usuario FOREIGN KEY (inscrito_por) REFERENCES usuarios(id_usuario) ON DELETE RESTRICT,
    UNIQUE KEY uq_mg_inscripcion_solicitud (id_solicitud),
    UNIQUE KEY uq_mg_inscripcion_estudiante_activa (id_estudiante, inscripcion_activa),
    INDEX idx_mg_inscripcion_cohorte_estado (id_cohorte, estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS mg_trabajo_integrantes (
    id_integrante BIGINT AUTO_INCREMENT PRIMARY KEY,
    id_trabajo BIGINT NOT NULL,
    id_inscripcion BIGINT NOT NULL,
    id_estudiante INT NOT NULL,
    estado ENUM('activo', 'retirado') NOT NULL DEFAULT 'activo',
    integrante_activo TINYINT GENERATED ALWAYS AS (CASE WHEN estado = 'activo' THEN 1 ELSE NULL END) STORED,
    agregado_por INT NOT NULL,
    agregado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_mg_integrante_trabajo FOREIGN KEY (id_trabajo) REFERENCES mg_trabajos(id_trabajo) ON DELETE RESTRICT,
    CONSTRAINT fk_mg_integrante_inscripcion FOREIGN KEY (id_inscripcion) REFERENCES mg_inscripciones(id_inscripcion) ON DELETE RESTRICT,
    CONSTRAINT fk_mg_integrante_estudiante FOREIGN KEY (id_estudiante) REFERENCES estudiantes(id_estudiante) ON DELETE RESTRICT,
    CONSTRAINT fk_mg_integrante_usuario FOREIGN KEY (agregado_por) REFERENCES usuarios(id_usuario) ON DELETE RESTRICT,
    UNIQUE KEY uq_mg_integrante_inscripcion (id_inscripcion),
    UNIQUE KEY uq_mg_integrante_estudiante_activo (id_estudiante, integrante_activo),
    INDEX idx_mg_integrantes_trabajo_estado (id_trabajo, estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO mg_permisos (codigo, nombre, descripcion) VALUES
    ('mg.inscripciones.gestionar', 'Gestionar inscripciones MG', 'Crear inscripción formal desde solicitud habilitada y asociar cohorte y trabajo')
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), descripcion = VALUES(descripcion), estado = 'activo';
