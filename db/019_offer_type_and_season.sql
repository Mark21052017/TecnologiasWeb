-- Clasificacion academica de ofertas y seleccion directa por tutores.
-- Ejecutar sobre testdb despues de 016_tutoring_types.sql.

USE testdb;

SET @has_season = (
  SELECT COUNT(*) FROM information_schema.columns
  WHERE table_schema = DATABASE() AND table_name = 'periodos_tutoria' AND column_name = 'temporada'
);
SET @season_sql = IF(
  @has_season = 0,
  "ALTER TABLE periodos_tutoria ADD COLUMN temporada ENUM('regular','verano','invierno') NOT NULL DEFAULT 'regular' AFTER nombre_periodo",
  'SELECT 1'
);
PREPARE season_statement FROM @season_sql;
EXECUTE season_statement;
DEALLOCATE PREPARE season_statement;

SET @has_type = (
  SELECT COUNT(*) FROM information_schema.columns
  WHERE table_schema = DATABASE() AND table_name = 'ofertas_tutoria' AND column_name = 'id_tipo_tutoria'
);
SET @type_sql = IF(
  @has_type = 0,
  'ALTER TABLE ofertas_tutoria ADD COLUMN id_tipo_tutoria INT NULL AFTER id_materia',
  'SELECT 1'
);
PREPARE type_statement FROM @type_sql;
EXECUTE type_statement;
DEALLOCATE PREPARE type_statement;

UPDATE ofertas_tutoria o
INNER JOIN tipos_tutoria tt ON LOWER(TRIM(tt.nombre)) = 'grado'
SET o.id_tipo_tutoria = tt.id_tipo_tutoria
WHERE o.id_tipo_tutoria IS NULL;

SET @type_nullable = (
  SELECT IS_NULLABLE FROM information_schema.columns
  WHERE table_schema = DATABASE() AND table_name = 'ofertas_tutoria' AND column_name = 'id_tipo_tutoria'
);
SET @required_type_sql = IF(
  @type_nullable = 'YES' AND NOT EXISTS (SELECT 1 FROM ofertas_tutoria WHERE id_tipo_tutoria IS NULL),
  'ALTER TABLE ofertas_tutoria MODIFY COLUMN id_tipo_tutoria INT NOT NULL',
  'SELECT 1'
);
PREPARE required_type_statement FROM @required_type_sql;
EXECUTE required_type_statement;
DEALLOCATE PREPARE required_type_statement;

SET @has_type_fk = (
  SELECT COUNT(*) FROM information_schema.table_constraints
  WHERE constraint_schema = DATABASE()
    AND table_name = 'ofertas_tutoria'
    AND constraint_name = 'fk_oferta_tipo_tutoria'
);
SET @type_fk_sql = IF(
  @has_type_fk = 0,
  'ALTER TABLE ofertas_tutoria ADD CONSTRAINT fk_oferta_tipo_tutoria FOREIGN KEY (id_tipo_tutoria) REFERENCES tipos_tutoria(id_tipo_tutoria)',
  'SELECT 1'
);
PREPARE type_fk_statement FROM @type_fk_sql;
EXECUTE type_fk_statement;
DEALLOCATE PREPARE type_fk_statement;

UPDATE oferta_tutores
SET estado = 'confirmada',
    fecha_revision = COALESCE(fecha_revision, CURRENT_TIMESTAMP),
    revisado_por = NULL
WHERE estado = 'pendiente';
