-- Normaliza los horarios institucionales y adapta la disponibilidad legacy.
-- Ejecutar sobre testdb. No elimina registros existentes.

USE testdb;

SET @sql = IF(
    EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'disponibilidad_tutor' AND column_name = 'id_bloque'),
    'SELECT 1',
    'ALTER TABLE disponibilidad_tutor ADD COLUMN id_bloque INT NULL AFTER dia_semana'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Relaciona los registros legacy que coincidian exactamente con los bloques anteriores.
UPDATE disponibilidad_tutor d
INNER JOIN bloques_horarios b
    ON b.hora_inicio = d.hora_inicio AND b.hora_fin = d.hora_fin
SET d.id_bloque = b.id_bloque
WHERE d.id_bloque IS NULL;

-- Conserva las referencias existentes de ofertas y cambia solo el horario del bloque.
UPDATE bloques_horarios SET nombre_bloque = CONVERT(0x4D61C3B1616E61 USING utf8mb4), hora_inicio = '07:30:00', hora_fin = '10:30:00', estado = 'activo' WHERE id_bloque = 1;
UPDATE bloques_horarios SET nombre_bloque = CONVERT(0x4D6564696F64C3AD61 USING utf8mb4), hora_inicio = '11:00:00', hora_fin = '14:00:00', estado = 'activo' WHERE id_bloque = 2;
UPDATE bloques_horarios SET nombre_bloque = 'Tarde', hora_inicio = '15:00:00', hora_fin = '18:00:00', estado = 'activo' WHERE id_bloque = 3;
UPDATE bloques_horarios SET nombre_bloque = 'Noche', hora_inicio = '19:00:00', hora_fin = '22:00:00', estado = 'activo' WHERE id_bloque = 4;

INSERT INTO bloques_horarios (nombre_bloque, hora_inicio, hora_fin, estado)
SELECT CONVERT(0x4D61C3B1616E61 USING utf8mb4), '07:30:00', '10:30:00', 'activo'
WHERE NOT EXISTS (SELECT 1 FROM bloques_horarios WHERE hora_inicio = '07:30:00' AND hora_fin = '10:30:00');
INSERT INTO bloques_horarios (nombre_bloque, hora_inicio, hora_fin, estado)
SELECT CONVERT(0x4D6564696F64C3AD61 USING utf8mb4), '11:00:00', '14:00:00', 'activo'
WHERE NOT EXISTS (SELECT 1 FROM bloques_horarios WHERE hora_inicio = '11:00:00' AND hora_fin = '14:00:00');
INSERT INTO bloques_horarios (nombre_bloque, hora_inicio, hora_fin, estado)
SELECT 'Tarde', '15:00:00', '18:00:00', 'activo'
WHERE NOT EXISTS (SELECT 1 FROM bloques_horarios WHERE hora_inicio = '15:00:00' AND hora_fin = '18:00:00');
INSERT INTO bloques_horarios (nombre_bloque, hora_inicio, hora_fin, estado)
SELECT 'Noche', '19:00:00', '22:00:00', 'activo'
WHERE NOT EXISTS (SELECT 1 FROM bloques_horarios WHERE hora_inicio = '19:00:00' AND hora_fin = '22:00:00');

SET @sql = IF(
    EXISTS (SELECT 1 FROM information_schema.table_constraints WHERE table_schema = DATABASE() AND table_name = 'disponibilidad_tutor' AND constraint_name = 'fk_disponibilidad_bloque'),
    'SELECT 1',
    'ALTER TABLE disponibilidad_tutor ADD CONSTRAINT fk_disponibilidad_bloque FOREIGN KEY (id_bloque) REFERENCES bloques_horarios(id_bloque)'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS (SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'disponibilidad_tutor' AND index_name = 'uq_disponibilidad_tutor_bloque'),
    'SELECT 1',
    'ALTER TABLE disponibilidad_tutor ADD UNIQUE KEY uq_disponibilidad_tutor_bloque (id_tutor, dia_semana, id_bloque)'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
