-- Solicitudes estudiantiles para abrir una oferta por materia, periodo y turno.
-- Se conserva solicitudes_tutor y su historial legado; esta tabla es independiente.
USE testdb;

CREATE TABLE IF NOT EXISTS solicitudes_apertura_materia (
    id_solicitud INT AUTO_INCREMENT PRIMARY KEY,
    id_estudiante INT NOT NULL,
    id_periodo INT NOT NULL,
    id_materia INT NOT NULL,
    id_turno INT NOT NULL,
    motivo VARCHAR(500) NULL,
    estado ENUM('pendiente', 'aprobada', 'rechazada') NOT NULL DEFAULT 'pendiente',
    id_oferta_generada INT NULL,
    revisado_por INT NULL,
    observaciones_revision VARCHAR(500) NULL,
    fecha_solicitud DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_revision DATETIME NULL,
    solicitud_activa TINYINT
        GENERATED ALWAYS AS (CASE WHEN estado IN ('pendiente', 'aprobada') THEN 1 ELSE NULL END) STORED,
    CONSTRAINT fk_solicitud_apertura_estudiante
        FOREIGN KEY (id_estudiante) REFERENCES estudiantes(id_estudiante) ON DELETE RESTRICT,
    CONSTRAINT fk_solicitud_apertura_periodo
        FOREIGN KEY (id_periodo) REFERENCES periodos_tutoria(id_periodo) ON DELETE RESTRICT,
    CONSTRAINT fk_solicitud_apertura_materia
        FOREIGN KEY (id_materia) REFERENCES materias(id_materia) ON DELETE RESTRICT,
    CONSTRAINT fk_solicitud_apertura_turno
        FOREIGN KEY (id_turno) REFERENCES turnos(id_turno) ON DELETE RESTRICT,
    CONSTRAINT fk_solicitud_apertura_oferta
        FOREIGN KEY (id_oferta_generada) REFERENCES ofertas_tutoria(id_oferta) ON DELETE SET NULL,
    CONSTRAINT fk_solicitud_apertura_revisor
        FOREIGN KEY (revisado_por) REFERENCES usuarios(id_usuario) ON DELETE SET NULL,
    UNIQUE KEY uq_solicitud_apertura_activa
        (id_estudiante, id_periodo, id_materia, id_turno, solicitud_activa),
    INDEX idx_solicitud_apertura_estado_fecha (estado, fecha_solicitud),
    INDEX idx_solicitud_apertura_demanda (id_periodo, id_materia, id_turno, estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO modulos_sistema (clave, nombre, descripcion, orden, estado)
VALUES ('solicitudes_apertura', 'Solicitudes', 'Pedidos estudiantiles de apertura de materias', 59, 'activo')
ON DUPLICATE KEY UPDATE
    nombre = VALUES(nombre),
    descripcion = VALUES(descripcion),
    orden = VALUES(orden),
    estado = 'activo';

INSERT INTO permisos_rol (id_rol, id_modulo, permitido)
SELECT r.id_rol, m.id_modulo, 1
FROM roles r
INNER JOIN modulos_sistema m ON m.clave = 'solicitudes_apertura'
WHERE r.nombre_rol IN ('administrador', 'estudiante')
ON DUPLICATE KEY UPDATE permitido = VALUES(permitido);
