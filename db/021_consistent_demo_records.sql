-- Correccion de registros demo incoherentes.
-- Ejecutar despues de 020_offer_turno_paralelo.sql. Es seguro ejecutarlo mas de una vez.
-- El script es ASCII a proposito; los textos con tilde se construyen con UNHEX.

USE testdb;

UPDATE periodos_tutoria
SET nombre_periodo = 'Materias de Invierno'
WHERE nombre_periodo = 'Materias de invierno'
  AND fecha_inicio = '2026-07-01'
  AND fecha_fin = '2026-07-31';

-- Estas materias pertenecen a Ingenieria Comercial, no a Ingenieria en Sistemas.
UPDATE materias m
INNER JOIN carreras c ON c.nombre_carrera = 'Ingenieria Comercial'
SET m.id_carrera = c.id_carrera
WHERE m.nombre_materia IN ('Comercio Electronico', 'Gestion Empresarial');

-- Reemplaza asignaciones confirmadas que no coinciden con las materias del tutor.
-- Ana tiene Base de Datos I; Diego tiene Ingenieria de Software II;
-- Carlos tiene Medios de Transmision.
UPDATE oferta_tutores ot
INNER JOIN ofertas_tutoria o ON o.id_oferta = ot.id_oferta
INNER JOIN tutores old_t ON old_t.id_tutor = ot.id_tutor
INNER JOIN usuarios old_u ON old_u.id_usuario = old_t.id_usuario
INNER JOIN usuarios new_u ON new_u.usuario = 'tutor.ana'
INNER JOIN tutores new_t ON new_t.id_usuario = new_u.id_usuario
SET ot.id_tutor = new_t.id_tutor
WHERE o.id_materia = 1
  AND old_u.usuario = 'juan1'
  AND ot.estado = 'confirmada';

UPDATE oferta_tutores ot
INNER JOIN ofertas_tutoria o ON o.id_oferta = ot.id_oferta
INNER JOIN tutores old_t ON old_t.id_tutor = ot.id_tutor
INNER JOIN usuarios old_u ON old_u.id_usuario = old_t.id_usuario
INNER JOIN usuarios new_u ON new_u.usuario = 'tutor.diego'
INNER JOIN tutores new_t ON new_t.id_usuario = new_u.id_usuario
SET ot.id_tutor = new_t.id_tutor
WHERE o.id_materia = 9
  AND old_u.usuario = 'luis1'
  AND ot.estado = 'confirmada';

UPDATE oferta_tutores ot
INNER JOIN ofertas_tutoria o ON o.id_oferta = ot.id_oferta
INNER JOIN tutores old_t ON old_t.id_tutor = ot.id_tutor
INNER JOIN usuarios old_u ON old_u.id_usuario = old_t.id_usuario
INNER JOIN usuarios new_u ON new_u.usuario = 'tutor.carlos'
INNER JOIN tutores new_t ON new_t.id_usuario = new_u.id_usuario
SET ot.id_tutor = new_t.id_tutor
WHERE o.id_materia = 4
  AND old_u.usuario = 'tutor.ana'
  AND ot.estado = 'confirmada';
