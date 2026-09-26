-- Genera el registro universitario mediante una secuencia transaccional.
-- Es seguro ejecutarlo mas de una vez sobre testdb.

USE testdb;

CREATE TABLE IF NOT EXISTS registro_universitario_secuencia (
  id TINYINT PRIMARY KEY,
  ultimo_numero INT NOT NULL
) ENGINE=InnoDB;

INSERT INTO registro_universitario_secuencia (id, ultimo_numero)
VALUES (1, 0)
ON DUPLICATE KEY UPDATE id = VALUES(id);

UPDATE registro_universitario_secuencia
SET ultimo_numero = GREATEST(
    ultimo_numero,
    (SELECT COALESCE(MAX(id_estudiante), 0) FROM estudiantes),
    (SELECT COALESCE(MAX(CAST(SUBSTRING(registro_universitario, 4) AS UNSIGNED)), 0)
     FROM estudiantes
     WHERE registro_universitario REGEXP '^RU-[0-9]+$')
)
WHERE id = 1;

UPDATE estudiantes
SET registro_universitario = CONCAT('RU-', LPAD(id_estudiante, 4, '0'))
WHERE registro_universitario IS NULL
   OR registro_universitario NOT REGEXP '^RU-[0-9]+$';

SET @sql = IF(
    EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = DATABASE()
          AND table_name = 'estudiantes'
          AND column_name = 'registro_universitario'
          AND is_nullable = 'NO'
    ),
    'SELECT 1',
    'ALTER TABLE estudiantes MODIFY registro_universitario VARCHAR(30) NOT NULL'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS (
        SELECT 1
        FROM information_schema.statistics
        WHERE table_schema = DATABASE()
          AND table_name = 'estudiantes'
          AND index_name = 'uq_estudiante_registro'
    ),
    'SELECT 1',
    'ALTER TABLE estudiantes DROP INDEX registro_universitario, ADD UNIQUE KEY uq_estudiante_registro (registro_universitario)'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
