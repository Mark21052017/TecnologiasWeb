-- Fotografia opcional para perfiles de tutor y estudiante.
-- Ejecutar sobre testdb. Es seguro ejecutarlo mas de una vez.

USE testdb;

SET @has_profile_photo = (
  SELECT COUNT(*)
  FROM information_schema.columns
  WHERE table_schema = DATABASE()
    AND table_name = 'usuarios'
    AND column_name = 'foto_perfil'
);
SET @photo_sql = IF(
  @has_profile_photo = 0,
  'ALTER TABLE usuarios ADD COLUMN foto_perfil VARCHAR(255) NULL AFTER telefono',
  'SELECT 1'
);
PREPARE photo_statement FROM @photo_sql;
EXECUTE photo_statement;
DEALLOCATE PREPARE photo_statement;
