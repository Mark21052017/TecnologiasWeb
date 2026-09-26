-- Alinea el nombre de la columna con el contrato del catalogo.
-- Es seguro ejecutarlo sobre instalaciones nuevas o existentes.

USE testdb;

SET @has_legacy_name = (
  SELECT COUNT(*)
  FROM information_schema.columns
  WHERE table_schema = DATABASE()
    AND table_name = 'tipos_tutoria'
    AND column_name = 'nombre_tipo'
);
SET @rename_sql = IF(
  @has_legacy_name = 1,
  'ALTER TABLE tipos_tutoria CHANGE COLUMN nombre_tipo nombre VARCHAR(100) NOT NULL',
  'SELECT 1'
);
PREPARE rename_statement FROM @rename_sql;
EXECUTE rename_statement;
DEALLOCATE PREPARE rename_statement;
