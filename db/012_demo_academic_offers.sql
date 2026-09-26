-- Ofertas demo para probar el flujo universidad -> tutor -> estudiante.
-- No asigna tutores automaticamente: deben postularse desde Mis materias.

USE testdb;

INSERT INTO ofertas_tutoria (id_periodo, id_materia, nombre_grupo, cupo, descripcion, estado)
SELECT p.id_periodo, m.id_materia, 'Grupo A', 20,
       'Oferta demo publicada por la universidad.', 'publicada'
FROM periodos_tutoria p
INNER JOIN materias m ON m.nombre_materia IN (
    'Base de Datos I', 'Programacion I', 'Tecnologia Web I', 'Medios de Transmision',
    'Programacion 2', 'Algoritmos y Estructuras de Datos', 'Programacion Web II',
    'Sistemas Operativos', 'Ingenieria de Software II', 'Seguridad Informatica'
)
WHERE p.nombre_periodo = 'Diciembre 2026'
  AND NOT EXISTS (
      SELECT 1 FROM ofertas_tutoria o
      WHERE o.id_periodo = p.id_periodo AND o.id_materia = m.id_materia AND o.nombre_grupo = 'Grupo A'
  );

INSERT INTO oferta_horarios (id_oferta, id_bloque, dia_semana, id_aula)
SELECT o.id_oferta, b.id_bloque,
       ELT(MOD(o.id_oferta - 1, 6) + 1, 'Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado'),
       a.id_aula
FROM ofertas_tutoria o
INNER JOIN periodos_tutoria p ON p.id_periodo = o.id_periodo AND p.nombre_periodo = 'Diciembre 2026'
INNER JOIN bloques_horarios b ON b.hora_inicio = '08:00:00' AND b.hora_fin = '10:00:00'
LEFT JOIN aulas a ON a.nombre_aula = 'Aula 101'
WHERE o.nombre_grupo = 'Grupo A'
  AND NOT EXISTS (SELECT 1 FROM oferta_horarios oh WHERE oh.id_oferta = o.id_oferta);
