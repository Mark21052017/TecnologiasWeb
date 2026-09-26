-- Datos ilustrativos para explorar el modulo MG en entornos de desarrollo.
-- No se ejecuta automaticamente y puede volver a ejecutarse sin duplicar registros.
USE testdb;

INSERT INTO mg_cohortes (codigo, nombre, fecha_inicio, fecha_fin, activa)
SELECT 'DEMO-MG-2026-10', '[DEMO] Cohorte 1 - Octubre 2026', '2026-10-01', '2027-12-31', 1
WHERE NOT EXISTS (SELECT 1 FROM mg_cohortes WHERE codigo = 'DEMO-MG-2026-10');

INSERT INTO mg_cohortes (codigo, nombre, fecha_inicio, fecha_fin, activa)
SELECT 'DEMO-MG-2027-01', '[DEMO] Cohorte 2 - Enero 2027', '2027-01-01', '2027-10-31', 1
WHERE NOT EXISTS (SELECT 1 FROM mg_cohortes WHERE codigo = 'DEMO-MG-2027-01');

INSERT INTO mg_calendario (id_cohorte, etapa, tipo, nombre, orden, fecha_limite)
SELECT c.id_cohorte, demo.etapa, demo.tipo, demo.nombre, demo.orden, demo.fecha_limite
FROM mg_cohortes c
INNER JOIN (
    SELECT 'DEMO-MG-2026-10' AS codigo, 'previa' AS etapa, 'taller' AS tipo, 'Taller APA' AS nombre, 1 AS orden, '2026-10-07' AS fecha_limite
    UNION ALL SELECT 'DEMO-MG-2026-10', 'previa', 'taller', 'Taller metodologico', 2, '2026-10-14'
    UNION ALL SELECT 'DEMO-MG-2026-10', 'mg1', 'asignacion_tutor', 'Asignación de Tutor', 3, '2026-10-20'
    UNION ALL SELECT 'DEMO-MG-2026-10', 'mg1', 'informe', 'Informe MG1 - Avance 1', 4, '2026-11-30'
    UNION ALL SELECT 'DEMO-MG-2026-10', 'mg1', 'informe', 'Informe MG1 - Avance 2', 5, '2027-01-15'
    UNION ALL SELECT 'DEMO-MG-2026-10', 'mg1', 'informe', 'Informe MG1 - Avance 3', 6, '2027-02-15'
    UNION ALL SELECT 'DEMO-MG-2026-10', 'mg1', 'asignacion_tribunal', 'Asignación de Tribunal MG1', 7, '2027-03-01'
    UNION ALL SELECT 'DEMO-MG-2026-10', 'mg1', 'defensa', 'Defensa MG1', 8, '2027-03-15'
    UNION ALL SELECT 'DEMO-MG-2026-10', 'mg2', 'ingreso_mg2', 'Ingreso a MG2', 9, '2027-04-01'
    UNION ALL SELECT 'DEMO-MG-2026-10', 'mg2', 'informe', 'Informe MG2 - Avance 1', 10, '2027-06-30'
    UNION ALL SELECT 'DEMO-MG-2026-10', 'mg2', 'informe', 'Informe MG2 - Avance 2', 11, '2027-09-30'
    UNION ALL SELECT 'DEMO-MG-2026-10', 'mg2', 'defensa', 'Defensa MG2', 12, '2027-12-01'
    UNION ALL SELECT 'DEMO-MG-2027-01', 'previa', 'taller', 'Taller metodologico', 1, '2027-01-10'
    UNION ALL SELECT 'DEMO-MG-2027-01', 'mg1', 'asignacion_tutor', 'Asignación de Tutor', 2, '2027-01-20'
    UNION ALL SELECT 'DEMO-MG-2027-01', 'mg1', 'informe', 'Informe MG1 - Avance 1', 3, '2027-02-28'
    UNION ALL SELECT 'DEMO-MG-2027-01', 'mg1', 'informe', 'Informe MG1 - Avance 2', 4, '2027-04-15'
    UNION ALL SELECT 'DEMO-MG-2027-01', 'mg1', 'asignacion_tribunal', 'Asignación de Tribunal MG1', 5, '2027-05-15'
    UNION ALL SELECT 'DEMO-MG-2027-01', 'mg1', 'defensa', 'Defensa MG1', 6, '2027-05-30'
    UNION ALL SELECT 'DEMO-MG-2027-01', 'mg2', 'ingreso_mg2', 'Ingreso a MG2', 7, '2027-06-01'
    UNION ALL SELECT 'DEMO-MG-2027-01', 'mg2', 'informe', 'Informe MG2 - Avance', 8, '2027-08-15'
    UNION ALL SELECT 'DEMO-MG-2027-01', 'mg2', 'defensa', 'Defensa MG2', 9, '2027-10-15'
) AS demo ON demo.codigo = c.codigo
WHERE NOT EXISTS (
    SELECT 1 FROM mg_calendario h
    WHERE h.id_cohorte = c.id_cohorte AND h.tipo = demo.tipo AND h.nombre = demo.nombre
);
