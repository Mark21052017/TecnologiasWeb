-- Asignación individual de plan y oferta de modalidades por carrera.
-- Requiere 034_mg_academic_imports.sql.
USE testdb;

CREATE TABLE IF NOT EXISTS mg_carrera_modalidades (
    id_carrera INT NOT NULL,
    id_modalidad INT NOT NULL,
    disponible TINYINT(1) NOT NULL DEFAULT 1,
    actualizado_por INT NULL,
    actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id_carrera, id_modalidad),
    CONSTRAINT fk_mg_carrera_modalidad_carrera FOREIGN KEY (id_carrera) REFERENCES carreras(id_carrera) ON DELETE RESTRICT,
    CONSTRAINT fk_mg_carrera_modalidad_modalidad FOREIGN KEY (id_modalidad) REFERENCES mg_modalidades(id_modalidad) ON DELETE RESTRICT,
    CONSTRAINT fk_mg_carrera_modalidad_usuario FOREIGN KEY (actualizado_por) REFERENCES usuarios(id_usuario) ON DELETE SET NULL,
    INDEX idx_mg_carrera_modalidad_disponible (disponible, id_modalidad)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS mg_estudiante_plan (
    id_estudiante INT NOT NULL PRIMARY KEY,
    id_plan_estudio BIGINT NOT NULL,
    asignado_por INT NOT NULL,
    asignado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_mg_estudiante_plan_estudiante FOREIGN KEY (id_estudiante) REFERENCES estudiantes(id_estudiante) ON DELETE RESTRICT,
    CONSTRAINT fk_mg_estudiante_plan_plan FOREIGN KEY (id_plan_estudio) REFERENCES mg_planes_estudio(id_plan_estudio) ON DELETE RESTRICT,
    CONSTRAINT fk_mg_estudiante_plan_usuario FOREIGN KEY (asignado_por) REFERENCES usuarios(id_usuario) ON DELETE RESTRICT,
    INDEX idx_mg_estudiante_plan_plan (id_plan_estudio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS mg_estudiante_plan_historial (
    id_asignacion BIGINT AUTO_INCREMENT PRIMARY KEY,
    id_estudiante INT NOT NULL,
    id_plan_anterior BIGINT NULL,
    id_plan_nuevo BIGINT NOT NULL,
    asignado_por INT NOT NULL,
    observacion VARCHAR(500) NULL,
    asignado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_mg_estudiante_plan_hist_estudiante FOREIGN KEY (id_estudiante) REFERENCES estudiantes(id_estudiante) ON DELETE RESTRICT,
    CONSTRAINT fk_mg_estudiante_plan_hist_anterior FOREIGN KEY (id_plan_anterior) REFERENCES mg_planes_estudio(id_plan_estudio) ON DELETE RESTRICT,
    CONSTRAINT fk_mg_estudiante_plan_hist_nuevo FOREIGN KEY (id_plan_nuevo) REFERENCES mg_planes_estudio(id_plan_estudio) ON DELETE RESTRICT,
    CONSTRAINT fk_mg_estudiante_plan_hist_usuario FOREIGN KEY (asignado_por) REFERENCES usuarios(id_usuario) ON DELETE RESTRICT,
    INDEX idx_mg_estudiante_plan_hist_estudiante_fecha (id_estudiante, asignado_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO mg_permisos (codigo, nombre, descripcion) VALUES
    ('mg.academico.asignar_plan', 'Asignar plan de estudios', 'Asignar a cada estudiante un plan y versión aprobados de su carrera'),
    ('mg.modalidades.carrera.gestionar', 'Gestionar oferta por carrera', 'Definir qué modalidades de grado están disponibles para cada carrera')
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), descripcion = VALUES(descripcion), estado = 'activo';
