-- Permisos de consulta para estudiantes.
-- Ejecutar sobre testdb despues de 004_student_registration.sql.

USE testdb;

UPDATE permisos_rol pr
INNER JOIN roles r ON r.id_rol = pr.id_rol
INNER JOIN modulos_sistema m ON m.id_modulo = pr.id_modulo
SET pr.permitido = 1
WHERE r.nombre_rol = 'estudiante'
  AND m.clave IN ('materias', 'tutores', 'disponibilidad');
