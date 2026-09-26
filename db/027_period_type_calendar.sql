-- Fechas reales de tutorias por periodo y tipo de tutoría.
USE testdb;

CREATE TABLE IF NOT EXISTS periodo_tipo_fechas (
    id_periodo_tipo_fecha INT AUTO_INCREMENT PRIMARY KEY,
    id_periodo INT NOT NULL,
    id_tipo_tutoria INT NOT NULL,
    fecha DATE NOT NULL,
    estado ENUM('activa', 'omitida') NOT NULL DEFAULT 'activa',
    UNIQUE KEY uq_periodo_tipo_fecha (id_periodo, id_tipo_tutoria, fecha),
    KEY idx_periodo_tipo_fecha_fecha (fecha),
    CONSTRAINT fk_periodo_tipo_fecha_tipo
        FOREIGN KEY (id_periodo, id_tipo_tutoria)
        REFERENCES periodo_tipos_tutoria (id_periodo, id_tipo_tutoria)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);
