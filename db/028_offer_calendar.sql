-- Mueve la frecuencia y el calendario desde el periodo a cada oferta academica.
USE testdb;

ALTER TABLE ofertas_tutoria
    ADD COLUMN frecuencia_programacion VARCHAR(20) NOT NULL DEFAULT 'mensual' AFTER id_turno;

UPDATE ofertas_tutoria o
INNER JOIN periodo_tipos_tutoria ptt
    ON ptt.id_periodo = o.id_periodo
   AND ptt.id_tipo_tutoria = o.id_tipo_tutoria
SET o.frecuencia_programacion = ptt.frecuencia;

ALTER TABLE ofertas_tutoria
    ADD CONSTRAINT chk_oferta_frecuencia
        CHECK (frecuencia_programacion IN ('mensual', 'semanal', 'diaria'));

CREATE TABLE oferta_tutoria_fechas (
    id_oferta_fecha INT AUTO_INCREMENT PRIMARY KEY,
    id_oferta INT NOT NULL,
    fecha DATE NOT NULL,
    estado ENUM('activa', 'omitida') NOT NULL DEFAULT 'activa',
    UNIQUE KEY uq_oferta_fecha (id_oferta, fecha),
    KEY idx_oferta_fecha_fecha (fecha),
    CONSTRAINT fk_oferta_fecha_oferta
        FOREIGN KEY (id_oferta) REFERENCES ofertas_tutoria(id_oferta)
        ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT IGNORE INTO oferta_tutoria_fechas (id_oferta, fecha, estado)
SELECT o.id_oferta, ptf.fecha, ptf.estado
FROM ofertas_tutoria o
INNER JOIN periodo_tipo_fechas ptf
    ON ptf.id_periodo = o.id_periodo
   AND ptf.id_tipo_tutoria = o.id_tipo_tutoria;

ALTER TABLE ofertas_tutoria
    DROP FOREIGN KEY fk_oferta_periodo_tipo,
    DROP FOREIGN KEY fk_oferta_periodo_turno;

ALTER TABLE ofertas_tutoria
    ADD CONSTRAINT fk_oferta_turno
        FOREIGN KEY (id_turno) REFERENCES turnos(id_turno);

DROP TABLE periodo_tipo_fechas;
DROP TABLE periodo_tipos_tutoria;
DROP TABLE periodo_turnos;
