-- Los registros llamados bloques representan turnos institucionales.
-- Renombra tablas y columnas sin perder las relaciones existentes.
USE testdb;

SET @sql = IF(
    EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'bloques_horarios')
    AND NOT EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'turnos'),
    'RENAME TABLE bloques_horarios TO turnos',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

UPDATE modulos_sistema
SET clave = 'turnos', nombre = 'Turnos'
WHERE clave = 'bloques';

SET @sql = IF(
    EXISTS (SELECT 1 FROM information_schema.table_constraints WHERE table_schema = DATABASE() AND table_name = 'oferta_horarios' AND constraint_name = 'oferta_horarios_ibfk_2'),
    'ALTER TABLE oferta_horarios DROP FOREIGN KEY oferta_horarios_ibfk_2',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS (SELECT 1 FROM information_schema.table_constraints WHERE table_schema = DATABASE() AND table_name = 'disponibilidad_tutor' AND constraint_name = 'fk_disponibilidad_bloque'),
    'ALTER TABLE disponibilidad_tutor DROP FOREIGN KEY fk_disponibilidad_bloque',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'turnos' AND column_name = 'id_bloque'),
    'ALTER TABLE turnos CHANGE id_bloque id_turno INT AUTO_INCREMENT',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'turnos' AND column_name = 'nombre_bloque'),
    'ALTER TABLE turnos CHANGE nombre_bloque nombre_turno VARCHAR(80) NOT NULL',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS (SELECT 1 FROM information_schema.table_constraints WHERE table_schema = DATABASE() AND table_name = 'oferta_horarios' AND constraint_name = 'oferta_horarios_ibfk_2'),
    'ALTER TABLE oferta_horarios DROP FOREIGN KEY oferta_horarios_ibfk_2',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'oferta_horarios' AND column_name = 'id_bloque'),
    'ALTER TABLE oferta_horarios CHANGE id_bloque id_turno INT NOT NULL',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS (SELECT 1 FROM information_schema.table_constraints WHERE table_schema = DATABASE() AND table_name = 'disponibilidad_tutor' AND constraint_name = 'fk_disponibilidad_bloque'),
    'ALTER TABLE disponibilidad_tutor DROP FOREIGN KEY fk_disponibilidad_bloque',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'disponibilidad_tutor' AND column_name = 'id_bloque'),
    'ALTER TABLE disponibilidad_tutor CHANGE id_bloque id_turno INT NULL',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

DELETE FROM disponibilidad_tutor WHERE id_turno IS NULL;
ALTER TABLE disponibilidad_tutor MODIFY id_turno INT NOT NULL;

SET @sql = IF(
    EXISTS (SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'oferta_horarios' AND index_name = 'uq_oferta_dia_bloque'),
    'ALTER TABLE oferta_horarios RENAME INDEX uq_oferta_dia_bloque TO uq_oferta_dia_turno',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS (SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'disponibilidad_tutor' AND index_name = 'uq_disponibilidad_tutor_bloque'),
    'ALTER TABLE disponibilidad_tutor RENAME INDEX uq_disponibilidad_tutor_bloque TO uq_disponibilidad_tutor_turno',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(
    NOT EXISTS (SELECT 1 FROM information_schema.table_constraints WHERE table_schema = DATABASE() AND table_name = 'oferta_horarios' AND constraint_name = 'fk_oferta_horario_turno'),
    'ALTER TABLE oferta_horarios ADD CONSTRAINT fk_oferta_horario_turno FOREIGN KEY (id_turno) REFERENCES turnos(id_turno)',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(
    NOT EXISTS (SELECT 1 FROM information_schema.table_constraints WHERE table_schema = DATABASE() AND table_name = 'disponibilidad_tutor' AND constraint_name = 'fk_disponibilidad_turno'),
    'ALTER TABLE disponibilidad_tutor ADD CONSTRAINT fk_disponibilidad_turno FOREIGN KEY (id_turno) REFERENCES turnos(id_turno)',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
