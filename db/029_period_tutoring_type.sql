-- Cada periodo pertenece a un solo tipo de tutoria (Tipo -> Periodo -> Ofertas).
USE testdb;

SET @columna = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'periodos_tutoria'
      AND COLUMN_NAME = 'id_tipo_tutoria'
);
SET @sql = IF(@columna = 0,
    'ALTER TABLE periodos_tutoria ADD COLUMN id_tipo_tutoria INT NULL AFTER nombre_periodo',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Backfill: el tipo mas usado en las ofertas del periodo.
UPDATE periodos_tutoria p
INNER JOIN (
    SELECT id_periodo, id_tipo_tutoria
    FROM (
        SELECT id_periodo, id_tipo_tutoria,
               ROW_NUMBER() OVER (PARTITION BY id_periodo ORDER BY COUNT(*) DESC, id_tipo_tutoria) AS posicion
        FROM ofertas_tutoria
        GROUP BY id_periodo, id_tipo_tutoria
    ) ranked
    WHERE posicion = 1
) pick ON pick.id_periodo = p.id_periodo
SET p.id_tipo_tutoria = pick.id_tipo_tutoria
WHERE p.id_tipo_tutoria IS NULL;

SET @fk = (
    SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND TABLE_NAME = 'periodos_tutoria'
      AND CONSTRAINT_NAME = 'fk_periodo_tipo_tutoria'
);
SET @sql = IF(@fk = 0,
    'ALTER TABLE periodos_tutoria ADD CONSTRAINT fk_periodo_tipo_tutoria FOREIGN KEY (id_tipo_tutoria) REFERENCES tipos_tutoria(id_tipo_tutoria)',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- La unicidad ahora incluye el tipo de tutoria del periodo.
SET @uq_vieja = (
    SELECT COUNT(DISTINCT INDEX_NAME) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'periodos_tutoria'
      AND INDEX_NAME = 'uq_periodo_nombre_fechas'
);
SET @sql = IF(@uq_vieja > 0,
    'ALTER TABLE periodos_tutoria DROP KEY uq_periodo_nombre_fechas',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @uq_nueva = (
    SELECT COUNT(DISTINCT INDEX_NAME) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'periodos_tutoria'
      AND INDEX_NAME = 'uq_periodo_nombre_fechas_tipo'
);
SET @sql = IF(@uq_nueva = 0,
    'ALTER TABLE periodos_tutoria ADD UNIQUE KEY uq_periodo_nombre_fechas_tipo (nombre_periodo, fecha_inicio, fecha_fin, id_tipo_tutoria)',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
