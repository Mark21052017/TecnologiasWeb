-- Evita carreras duplicadas por mayusculas o espacios exteriores.
-- Es seguro ejecutarlo mas de una vez sobre testdb.

USE testdb;

SET @sql = IF(
    EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = DATABASE()
          AND table_name = 'carreras'
          AND column_name = 'nombre_carrera_clave'
    ),
    'SELECT 1',
    'ALTER TABLE carreras ADD COLUMN nombre_carrera_clave VARCHAR(150) GENERATED ALWAYS AS (LOWER(TRIM(nombre_carrera))) STORED'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS (
        SELECT 1
        FROM information_schema.statistics
        WHERE table_schema = DATABASE()
          AND table_name = 'carreras'
          AND index_name = 'uq_carrera_nombre_clave'
    ),
    'SELECT 1',
    'ALTER TABLE carreras ADD UNIQUE KEY uq_carrera_nombre_clave (nombre_carrera_clave)'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
