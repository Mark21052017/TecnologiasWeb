-- Asignaciones históricas de tutores a trabajos MG.
-- Requiere db/037_mg_inscripciones_trabajos.sql.
USE testdb;

CREATE TABLE IF NOT EXISTS mg_asignaciones_tutor (
    id_asignacion BIGINT AUTO_INCREMENT PRIMARY KEY,
    id_trabajo BIGINT NOT NULL,
    id_tutor INT NOT NULL,
    estado ENUM('activa', 'finalizada') NOT NULL DEFAULT 'activa',
    asignacion_activa TINYINT GENERATED ALWAYS AS (CASE WHEN estado = 'activa' THEN 1 ELSE NULL END) STORED,
    fecha_inicio DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_fin DATETIME NULL,
    asignado_por INT NOT NULL,
    observacion VARCHAR(1000) NULL,
    CONSTRAINT fk_mg_asignacion_tutor_trabajo FOREIGN KEY (id_trabajo) REFERENCES mg_trabajos(id_trabajo) ON DELETE RESTRICT,
    CONSTRAINT fk_mg_asignacion_tutor_tutor FOREIGN KEY (id_tutor) REFERENCES tutores(id_tutor) ON DELETE RESTRICT,
    CONSTRAINT fk_mg_asignacion_tutor_usuario FOREIGN KEY (asignado_por) REFERENCES usuarios(id_usuario) ON DELETE RESTRICT,
    UNIQUE KEY uq_mg_asignacion_tutor_activa_trabajo (id_trabajo, asignacion_activa),
    INDEX idx_mg_asignacion_tutor_carga (id_tutor, estado),
    INDEX idx_mg_asignacion_tutor_historial (id_trabajo, fecha_inicio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO mg_permisos (codigo, nombre, descripcion) VALUES
    ('mg.tutores.asignar', 'Asignar tutores MG', 'Asignar o cambiar tutor de trabajos de grado conservando historial'),
    ('mg.trabajos.propios', 'Consultar trabajos MG asignados', 'Consultar trabajos y estudiantes asignados al tutor autenticado')
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), descripcion = VALUES(descripcion), estado = 'activo';

INSERT IGNORE INTO mg_permisos_rol (id_rol, codigo_permiso, permitido)
SELECT id_rol, 'mg.trabajos.propios', 1 FROM roles WHERE nombre_rol = 'tutor';
