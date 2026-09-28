-- Notificaciones dentro de la aplicación y postulación estudiantil a una oferta de tutoría.
-- La postulación pendiente no reserva cupo; se revalida al aprobar.
USE testdb;

CREATE TABLE IF NOT EXISTS notificaciones (
    id_notificacion BIGINT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    tipo VARCHAR(60) NOT NULL,
    titulo VARCHAR(180) NOT NULL,
    mensaje VARCHAR(1000) NOT NULL,
    ruta VARCHAR(255) NULL,
    clave_evento VARCHAR(190) NOT NULL,
    creada_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    leida_en DATETIME NULL,
    CONSTRAINT fk_notificacion_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
    UNIQUE KEY uq_notificacion_usuario_evento (id_usuario, clave_evento),
    INDEX idx_notificacion_usuario_lectura (id_usuario, leida_en, creada_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS postulaciones_tutoria (
    id_postulacion BIGINT AUTO_INCREMENT PRIMARY KEY,
    id_estudiante INT NOT NULL,
    id_oferta INT NOT NULL,
    estado ENUM('pendiente', 'aprobada', 'rechazada', 'cancelada') NOT NULL DEFAULT 'pendiente',
    postulacion_pendiente TINYINT GENERATED ALWAYS AS (CASE WHEN estado = 'pendiente' THEN 1 ELSE NULL END) STORED,
    motivo VARCHAR(1000) NULL,
    observaciones_revision VARCHAR(1000) NULL,
    id_inscripcion INT NULL,
    fecha_postulacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_revision DATETIME NULL,
    revisado_por INT NULL,
    CONSTRAINT fk_postulacion_tutoria_estudiante FOREIGN KEY (id_estudiante) REFERENCES estudiantes(id_estudiante) ON DELETE RESTRICT,
    CONSTRAINT fk_postulacion_tutoria_oferta FOREIGN KEY (id_oferta) REFERENCES ofertas_tutoria(id_oferta) ON DELETE RESTRICT,
    CONSTRAINT fk_postulacion_tutoria_inscripcion FOREIGN KEY (id_inscripcion) REFERENCES inscripciones_tutoria(id_inscripcion) ON DELETE SET NULL,
    CONSTRAINT fk_postulacion_tutoria_revisor FOREIGN KEY (revisado_por) REFERENCES usuarios(id_usuario) ON DELETE SET NULL,
    UNIQUE KEY uq_postulacion_oferta_pendiente (id_estudiante, id_oferta, postulacion_pendiente),
    UNIQUE KEY uq_postulacion_inscripcion (id_inscripcion),
    INDEX idx_postulacion_estado_fecha (estado, fecha_postulacion),
    INDEX idx_postulacion_estudiante (id_estudiante, fecha_postulacion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
