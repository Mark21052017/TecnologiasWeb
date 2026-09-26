-- Diez materias de prueba para ampliar el catalogo academico.
-- Ejecutar despues de 007_demo_tutors.sql.

USE testdb;
SET NAMES utf8mb4;

INSERT INTO materias (nombre_materia, id_carrera)
SELECT v.nombre_materia, c.id_carrera
FROM carreras c
INNER JOIN (
    SELECT 'Algoritmos y Estructuras de Datos' AS nombre_materia, 'Ingenieria de Sistemas' AS carrera
    UNION ALL SELECT 'Programacion Web II', 'Ingenieria de Sistemas'
    UNION ALL SELECT 'Sistemas Operativos', 'Ingenieria de Sistemas'
    UNION ALL SELECT 'Ingenieria de Software II', 'Ingenieria de Sistemas'
    UNION ALL SELECT 'Seguridad Informatica', 'Ingenieria de Sistemas'
    UNION ALL SELECT 'Comercio Electronico', 'Ingenieria Comercial'
    UNION ALL SELECT 'Gestion Empresarial', 'Ingenieria Comercial'
    UNION ALL SELECT 'Estadistica Aplicada', 'Ingenieria Industrial'
    UNION ALL SELECT 'Automatizacion Industrial', 'Ingenieria Industrial'
    UNION ALL SELECT 'Redes Inalambricas', 'Ingenieria en Redes y Telecomunicaciones'
) v ON v.carrera = c.nombre_carrera
WHERE NOT EXISTS (
    SELECT 1
    FROM materias m
    WHERE m.nombre_materia = v.nombre_materia
);

INSERT IGNORE INTO tutor_materia (id_tutor, id_materia)
SELECT t.id_tutor, m.id_materia
FROM tutores t
INNER JOIN usuarios u ON u.id_usuario = t.id_usuario
INNER JOIN (
    SELECT 'tutor.ana' AS usuario, 'Algoritmos y Estructuras de Datos' AS materia
    UNION ALL SELECT 'tutor.diego', 'Algoritmos y Estructuras de Datos'
    UNION ALL SELECT 'tutor.diego', 'Sistemas Operativos'
    UNION ALL SELECT 'tutor.diego', 'Ingenieria de Software II'
    UNION ALL SELECT 'tutor.valeria', 'Programacion Web II'
    UNION ALL SELECT 'tutor.valeria', 'Comercio Electronico'
    UNION ALL SELECT 'tutor.carlos', 'Seguridad Informatica'
    UNION ALL SELECT 'tutor.carlos', 'Redes Inalambricas'
    UNION ALL SELECT 'tutor.mariana', 'Estadistica Aplicada'
    UNION ALL SELECT 'tutor.mariana', 'Gestion Empresarial'
    UNION ALL SELECT 'tutor.andres', 'Programacion Web II'
    UNION ALL SELECT 'tutor.andres', 'Seguridad Informatica'
    UNION ALL SELECT 'tutor.sofia', 'Sistemas Operativos'
    UNION ALL SELECT 'tutor.sofia', 'Automatizacion Industrial'
    UNION ALL SELECT 'luis1', 'Seguridad Informatica'
    UNION ALL SELECT 'marce1', 'Comercio Electronico'
    UNION ALL SELECT 'marce1', 'Gestion Empresarial'
    UNION ALL SELECT 'juan1', 'Redes Inalambricas'
) a ON a.usuario = u.usuario
INNER JOIN materias m ON m.nombre_materia = a.materia;
