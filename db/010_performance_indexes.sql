-- Indices para consultas frecuentes de tutorias, disponibilidad y auditoria.
-- Idempotente: puede ejecutarse mas de una vez sobre testdb.

USE testdb;

SET @sql = IF(
    EXISTS (
        SELECT 1 FROM information_schema.statistics
        WHERE table_schema = DATABASE() AND table_name = 'tutorias'
          AND index_name = 'idx_tutoria_tutor_fecha_estado_hora'
    ),
    'SELECT 1',
    'ALTER TABLE tutorias ADD INDEX idx_tutoria_tutor_fecha_estado_hora (id_tutor, fecha, estado, hora_inicio, hora_fin)'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS (
        SELECT 1 FROM information_schema.statistics
        WHERE table_schema = DATABASE() AND table_name = 'tutorias'
          AND index_name = 'idx_tutoria_estudiante_fecha_estado_hora'
    ),
    'SELECT 1',
    'ALTER TABLE tutorias ADD INDEX idx_tutoria_estudiante_fecha_estado_hora (id_estudiante, fecha, estado, hora_inicio, hora_fin)'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS (
        SELECT 1 FROM information_schema.statistics
        WHERE table_schema = DATABASE() AND table_name = 'tutorias'
          AND index_name = 'idx_tutoria_materia_fecha'
    ),
    'SELECT 1',
    'ALTER TABLE tutorias ADD INDEX idx_tutoria_materia_fecha (id_materia, fecha)'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS (
        SELECT 1 FROM information_schema.statistics
        WHERE table_schema = DATABASE() AND table_name = 'disponibilidad_tutor'
          AND index_name = 'idx_disponibilidad_tutor_dia_hora'
    ),
    'SELECT 1',
    'ALTER TABLE disponibilidad_tutor ADD INDEX idx_disponibilidad_tutor_dia_hora (id_tutor, dia_semana, hora_inicio, hora_fin)'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS (
        SELECT 1 FROM information_schema.statistics
        WHERE table_schema = DATABASE() AND table_name = 'registro_accesos'
          AND index_name = 'idx_acceso_fecha_id'
    ),
    'SELECT 1',
    'ALTER TABLE registro_accesos ADD INDEX idx_acceso_fecha_id (fecha_hora, id_acceso)'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
