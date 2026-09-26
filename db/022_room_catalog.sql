-- Catalogo institucional de aulas y nomenclatura oficial.
-- Ejecutar despues de 021_consistent_demo_records.sql. Es seguro ejecutarlo mas de una vez.

USE testdb;

-- Conserva las referencias de las ofertas existentes y renombra las aulas demo.
UPDATE aulas SET nombre_aula = 'A100' WHERE id_aula = 1 AND nombre_aula = 'Aula 101';
UPDATE aulas SET nombre_aula = 'A101' WHERE id_aula = 2 AND nombre_aula = 'Aula 102';
UPDATE aulas SET nombre_aula = 'A102' WHERE id_aula = 3 AND nombre_aula = 'A403';

INSERT INTO aulas (nombre_aula, ubicacion, capacidad, estado)
SELECT nombres.nombre_aula, 'Bloque A', 30, 'activa'
FROM (
    SELECT 'A103' AS nombre_aula
    UNION ALL SELECT 'A104'
    UNION ALL SELECT 'A105'
    UNION ALL SELECT 'A106'
    UNION ALL SELECT 'A107'
    UNION ALL SELECT 'A108'
    UNION ALL SELECT 'A109'
    UNION ALL SELECT 'A110'
    UNION ALL SELECT 'A200'
    UNION ALL SELECT 'A201'
    UNION ALL SELECT 'A202'
    UNION ALL SELECT 'A203'
    UNION ALL SELECT 'A204'
    UNION ALL SELECT 'A205'
    UNION ALL SELECT 'A206'
    UNION ALL SELECT 'A207'
    UNION ALL SELECT 'A208'
) nombres
WHERE NOT EXISTS (
    SELECT 1 FROM aulas a WHERE a.nombre_aula = nombres.nombre_aula
);
