-- Published offers are visible without a tutor; tutor acceptance covers every offered schedule.
-- Tutor withdrawal requests remain in a separate auditable history table.
-- Safe to rerun on testdb.
USE testdb;

ALTER TABLE ofertas_tutoria
    MODIFY COLUMN estado ENUM('borrador','pendiente','publicada','cerrada','finalizada','cancelada')
    NOT NULL DEFAULT 'pendiente';

UPDATE ofertas_tutoria SET estado = 'pendiente' WHERE estado = 'borrador';

ALTER TABLE ofertas_tutoria
    MODIFY COLUMN estado ENUM('pendiente','publicada','cerrada','finalizada','cancelada')
    NOT NULL DEFAULT 'pendiente';

ALTER TABLE oferta_tutores
    MODIFY COLUMN estado ENUM('pendiente','confirmada','rechazada','baja_solicitada','cancelada')
    NOT NULL DEFAULT 'pendiente';

CREATE TABLE IF NOT EXISTS oferta_tutor_bajas (
    id_baja INT AUTO_INCREMENT PRIMARY KEY,
    id_oferta_tutor INT NOT NULL,
    solicitada_por INT NULL,
    motivo VARCHAR(500) NOT NULL,
    estado ENUM('pendiente','aprobada','rechazada') NOT NULL DEFAULT 'pendiente',
    respuesta_admin VARCHAR(500) NULL,
    resuelta_por INT NULL,
    id_oferta_tutor_reemplazo INT NULL,
    fecha_solicitud DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_resolucion DATETIME NULL,
    baja_activa TINYINT
        GENERATED ALWAYS AS (CASE WHEN estado = 'pendiente' THEN 1 ELSE NULL END) STORED,
    CONSTRAINT fk_oferta_tutor_baja_asignacion
        FOREIGN KEY (id_oferta_tutor) REFERENCES oferta_tutores(id_oferta_tutor) ON DELETE CASCADE,
    CONSTRAINT fk_oferta_tutor_baja_solicitante
        FOREIGN KEY (solicitada_por) REFERENCES usuarios(id_usuario) ON DELETE SET NULL,
    CONSTRAINT fk_oferta_tutor_baja_revisor
        FOREIGN KEY (resuelta_por) REFERENCES usuarios(id_usuario) ON DELETE SET NULL,
    CONSTRAINT fk_oferta_tutor_baja_reemplazo
        FOREIGN KEY (id_oferta_tutor_reemplazo) REFERENCES oferta_tutores(id_oferta_tutor) ON DELETE SET NULL,
    UNIQUE KEY uq_oferta_tutor_baja_activa (id_oferta_tutor, baja_activa),
    INDEX idx_oferta_tutor_baja_estado_fecha (estado, fecha_solicitud)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Existing confirmed assignments without stored preferences now honor the complete offer schedule.
INSERT IGNORE INTO oferta_tutor_horarios (id_oferta_tutor, id_oferta_horario)
SELECT ot.id_oferta_tutor, oh.id_oferta_horario
FROM oferta_tutores ot
INNER JOIN oferta_horarios oh ON oh.id_oferta = ot.id_oferta
WHERE ot.estado = 'confirmada'
  AND NOT EXISTS (
      SELECT 1 FROM oferta_tutor_horarios existing
      WHERE existing.id_oferta_tutor = ot.id_oferta_tutor
  );
