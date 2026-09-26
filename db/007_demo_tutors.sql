-- Datos de prueba para el flujo completo del rol tutor.
-- Ejecutar despues de 006_tutor_permissions.sql.
-- Las cuentas se crean con hashes aleatorios no conocidos; asignar credenciales locales si se requieren para pruebas.

USE testdb;
SET NAMES utf8mb4;

INSERT INTO usuarios (id_rol, nombre, apellido, correo, usuario, contrasena_hash, telefono, estado)
SELECT r.id_rol, 'Ana', 'Salazar', 'ana.salazar@tutorias.local', 'tutor.ana', '$2y$12$miU6EVW01VvpV9eXcseMROF/oWVyWvQXjTKb9.OraOA27zaD8N7mW', '71230001', 'activo'
FROM roles r
WHERE r.nombre_rol = 'tutor'
  AND NOT EXISTS (SELECT 1 FROM usuarios WHERE usuario = 'tutor.ana');

INSERT INTO usuarios (id_rol, nombre, apellido, correo, usuario, contrasena_hash, telefono, estado)
SELECT r.id_rol, 'Diego', 'Rojas', 'diego.rojas@tutorias.local', 'tutor.diego', '$2y$12$hWLfLgFmrC1ZAn.d4flk3O1WQzdK550XWF.erfHiYy04GHWMr2zBu', '71230002', 'activo'
FROM roles r
WHERE r.nombre_rol = 'tutor'
  AND NOT EXISTS (SELECT 1 FROM usuarios WHERE usuario = 'tutor.diego');

INSERT INTO usuarios (id_rol, nombre, apellido, correo, usuario, contrasena_hash, telefono, estado)
SELECT r.id_rol, 'Valeria', 'Gomez', 'valeria.gomez@tutorias.local', 'tutor.valeria', '$2y$12$A4c94hLjkIbgq6w3.UO93OPIw4a1jm.wHOqUMg4.TVAcY/lAQPZne', '71230003', 'activo'
FROM roles r
WHERE r.nombre_rol = 'tutor'
  AND NOT EXISTS (SELECT 1 FROM usuarios WHERE usuario = 'tutor.valeria');

INSERT INTO usuarios (id_rol, nombre, apellido, correo, usuario, contrasena_hash, telefono, estado)
SELECT r.id_rol, 'Carlos', 'Perez', 'carlos.perez@tutorias.local', 'tutor.carlos', '$2y$12$S8odO.74jVO3CataqhHouuCRkvdAjkmppE7clOrCr4WuuA1fvsriK', '71230004', 'activo'
FROM roles r
WHERE r.nombre_rol = 'tutor'
  AND NOT EXISTS (SELECT 1 FROM usuarios WHERE usuario = 'tutor.carlos');

INSERT INTO usuarios (id_rol, nombre, apellido, correo, usuario, contrasena_hash, telefono, estado)
SELECT r.id_rol, 'Mariana', 'Torres', 'mariana.torres@tutorias.local', 'tutor.mariana', '$2y$12$dHhpCvwLQSpcf9hcXY5HjeBfBgWLCEVKUHPU/rri9pwlq52XsMbdC', '71230005', 'activo'
FROM roles r
WHERE r.nombre_rol = 'tutor'
  AND NOT EXISTS (SELECT 1 FROM usuarios WHERE usuario = 'tutor.mariana');

INSERT INTO usuarios (id_rol, nombre, apellido, correo, usuario, contrasena_hash, telefono, estado)
SELECT r.id_rol, 'Andres', 'Villarroel', 'andres.villarroel@tutorias.local', 'tutor.andres', '$2y$12$ql0VKyeHDd0qpp8tPOHXkOseBYa3avGDSQ/EwRfgwVevOfqDGeaMe', '71230006', 'activo'
FROM roles r
WHERE r.nombre_rol = 'tutor'
  AND NOT EXISTS (SELECT 1 FROM usuarios WHERE usuario = 'tutor.andres');

INSERT INTO usuarios (id_rol, nombre, apellido, correo, usuario, contrasena_hash, telefono, estado)
SELECT r.id_rol, 'Sofia', 'Mendez', 'sofia.mendez@tutorias.local', 'tutor.sofia', '$2y$12$RBkaqtXDwy.23BQjldqWuOWtK25.NZsmVjy3G36kfx2xyewmRQKqa', '71230007', 'activo'
FROM roles r
WHERE r.nombre_rol = 'tutor'
  AND NOT EXISTS (SELECT 1 FROM usuarios WHERE usuario = 'tutor.sofia');

INSERT INTO tutores (id_usuario, especialidad, biografia)
SELECT u.id_usuario, v.especialidad, v.biografia
FROM usuarios u
INNER JOIN (
    SELECT 'tutor.ana' AS usuario, 'Matematica aplicada' AS especialidad, 'Tutorias practicas de bases de datos y resolucion de problemas.' AS biografia
    UNION ALL SELECT 'tutor.diego', 'Ingenieria de software', 'Acompañamiento paso a paso para programacion y buenas practicas.'
    UNION ALL SELECT 'tutor.valeria', 'Desarrollo web', 'Enfocada en interfaces, accesibilidad y tecnologia web.'
    UNION ALL SELECT 'tutor.carlos', 'Redes y telecomunicaciones', 'Explicaciones claras sobre redes, medios y conectividad.'
    UNION ALL SELECT 'tutor.mariana', 'Analisis de datos', 'Apoyo en modelado, consultas y lectura de informacion.'
    UNION ALL SELECT 'tutor.andres', 'Desarrollo frontend', 'Practica guiada para construir experiencias web modernas.'
    UNION ALL SELECT 'tutor.sofia', 'Arquitectura de sistemas', 'Orientacion para integrar software, datos y servicios.'
) v ON v.usuario = u.usuario
WHERE NOT EXISTS (SELECT 1 FROM tutores t WHERE t.id_usuario = u.id_usuario);

INSERT IGNORE INTO tutor_materia (id_tutor, id_materia)
SELECT t.id_tutor, a.id_materia
FROM tutores t
INNER JOIN usuarios u ON u.id_usuario = t.id_usuario
INNER JOIN (
    SELECT 'luis1' AS usuario, 1 AS id_materia
    UNION ALL SELECT 'luis1', 2
    UNION ALL SELECT 'luis1', 4
    UNION ALL SELECT 'marce1', 1
    UNION ALL SELECT 'marce1', 3
    UNION ALL SELECT 'marce1', 4
    UNION ALL SELECT 'juan1', 2
    UNION ALL SELECT 'juan1', 5
    UNION ALL SELECT 'tutor.ana', 1
    UNION ALL SELECT 'tutor.ana', 2
    UNION ALL SELECT 'tutor.diego', 2
    UNION ALL SELECT 'tutor.diego', 5
    UNION ALL SELECT 'tutor.valeria', 2
    UNION ALL SELECT 'tutor.valeria', 3
    UNION ALL SELECT 'tutor.carlos', 1
    UNION ALL SELECT 'tutor.carlos', 4
    UNION ALL SELECT 'tutor.mariana', 1
    UNION ALL SELECT 'tutor.mariana', 5
    UNION ALL SELECT 'tutor.andres', 2
    UNION ALL SELECT 'tutor.andres', 3
    UNION ALL SELECT 'tutor.sofia', 3
    UNION ALL SELECT 'tutor.sofia', 4
) a ON a.usuario = u.usuario;

INSERT INTO disponibilidad_tutor (id_tutor, dia_semana, hora_inicio, hora_fin)
SELECT t.id_tutor, s.dia_semana, s.hora_inicio, s.hora_fin
FROM tutores t
INNER JOIN usuarios u ON u.id_usuario = t.id_usuario
INNER JOIN (
    SELECT 'luis1' AS usuario, 'Martes' AS dia_semana, '14:00:00' AS hora_inicio, '16:00:00' AS hora_fin
    UNION ALL SELECT 'marce1', 'Miercoles', '09:00:00', '11:00:00'
    UNION ALL SELECT 'juan1', 'Jueves', '16:00:00', '18:00:00'
    UNION ALL SELECT 'tutor.ana', 'Lunes', '08:00:00', '10:00:00'
    UNION ALL SELECT 'tutor.ana', 'Jueves', '10:00:00', '12:00:00'
    UNION ALL SELECT 'tutor.diego', 'Martes', '08:00:00', '10:00:00'
    UNION ALL SELECT 'tutor.diego', 'Viernes', '14:00:00', '16:00:00'
    UNION ALL SELECT 'tutor.valeria', 'Lunes', '16:00:00', '18:00:00'
    UNION ALL SELECT 'tutor.valeria', 'Miercoles', '16:00:00', '18:00:00'
    UNION ALL SELECT 'tutor.carlos', 'Martes', '10:00:00', '12:00:00'
    UNION ALL SELECT 'tutor.carlos', 'Sabado', '09:00:00', '11:00:00'
    UNION ALL SELECT 'tutor.mariana', 'Miercoles', '14:00:00', '16:00:00'
    UNION ALL SELECT 'tutor.mariana', 'Viernes', '10:00:00', '12:00:00'
    UNION ALL SELECT 'tutor.andres', 'Jueves', '08:00:00', '10:00:00'
    UNION ALL SELECT 'tutor.andres', 'Viernes', '16:00:00', '18:00:00'
    UNION ALL SELECT 'tutor.sofia', 'Lunes', '14:00:00', '16:00:00'
    UNION ALL SELECT 'tutor.sofia', 'Sabado', '14:00:00', '16:00:00'
) s ON s.usuario = u.usuario
WHERE NOT EXISTS (
    SELECT 1
    FROM disponibilidad_tutor d
    WHERE d.id_tutor = t.id_tutor
      AND d.dia_semana = s.dia_semana
      AND d.hora_inicio = s.hora_inicio
      AND d.hora_fin = s.hora_fin
);
