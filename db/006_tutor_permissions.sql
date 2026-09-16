-- Accesos adicionales para el rol tutor.
-- Ejecutar sobre testdb despues de 005_student_catalog_permissions.sql.

USE testdb;

UPDATE permisos_rol pr
INNER JOIN roles r ON r.id_rol = pr.id_rol
INNER JOIN modulos_sistema m ON m.id_modulo = pr.id_modulo
SET pr.permitido = 1
WHERE r.nombre_rol = 'tutor'
  AND m.clave IN ('tutores', 'asignaciones');
