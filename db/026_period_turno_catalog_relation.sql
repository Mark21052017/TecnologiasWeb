-- Usa el catalogo de turnos como fuente unica para periodos y ofertas.
USE testdb;

SET @sql = IF(
    EXISTS (SELECT 1 FROM information_schema.table_constraints WHERE table_schema = DATABASE() AND table_name = 'ofertas_tutoria' AND constraint_name = 'fk_oferta_periodo_turno'),
    'ALTER TABLE ofertas_tutoria DROP FOREIGN KEY fk_oferta_periodo_turno',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS (SELECT 1 FROM information_schema.table_constraints WHERE table_schema = DATABASE() AND table_name = 'oferta_horarios' AND constraint_name = 'fk_oferta_horario_turno'),
    'ALTER TABLE oferta_horarios DROP FOREIGN KEY fk_oferta_horario_turno',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS (SELECT 1 FROM information_schema.table_constraints WHERE table_schema = DATABASE() AND table_name = 'periodo_turnos' AND constraint_name = 'fk_periodo_turno_periodo'),
    'ALTER TABLE periodo_turnos DROP FOREIGN KEY fk_periodo_turno_periodo',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

ALTER TABLE periodo_turnos DROP PRIMARY KEY, DROP INDEX idx_periodo_turno_turno;
ALTER TABLE periodo_turnos ADD COLUMN id_turno INT NULL AFTER id_periodo;
UPDATE periodo_turnos pt
INNER JOIN turnos t ON t.turno = pt.turno
SET pt.id_turno = t.id_turno;
DELETE FROM periodo_turnos WHERE id_turno IS NULL;
ALTER TABLE periodo_turnos MODIFY id_turno INT NOT NULL;
ALTER TABLE periodo_turnos DROP COLUMN turno,
    ADD PRIMARY KEY (id_periodo, id_turno),
    ADD INDEX idx_periodo_turno_turno (id_turno),
    ADD CONSTRAINT fk_periodo_turno_periodo FOREIGN KEY (id_periodo) REFERENCES periodos_tutoria(id_periodo),
    ADD CONSTRAINT fk_periodo_turno_turno FOREIGN KEY (id_turno) REFERENCES turnos(id_turno);

ALTER TABLE ofertas_tutoria ADD COLUMN id_turno INT NULL AFTER id_tipo_tutoria;
UPDATE ofertas_tutoria o
INNER JOIN turnos t ON t.turno = o.turno
SET o.id_turno = t.id_turno;
DELETE FROM ofertas_tutoria WHERE id_turno IS NULL;
ALTER TABLE ofertas_tutoria MODIFY id_turno INT NOT NULL;
ALTER TABLE ofertas_tutoria DROP INDEX uq_oferta_periodo_materia_turno_grupo;
ALTER TABLE ofertas_tutoria DROP COLUMN turno,
    ADD UNIQUE KEY uq_oferta_periodo_materia_turno_grupo (id_periodo, id_materia, id_turno, nombre_grupo),
    ADD CONSTRAINT fk_oferta_periodo_turno FOREIGN KEY (id_periodo, id_turno) REFERENCES periodo_turnos(id_periodo, id_turno);

ALTER TABLE oferta_horarios DROP INDEX uq_oferta_dia_turno;
ALTER TABLE oferta_horarios DROP COLUMN id_turno,
    ADD UNIQUE KEY uq_oferta_dia (id_oferta, dia_semana);

ALTER TABLE turnos DROP COLUMN turno;
