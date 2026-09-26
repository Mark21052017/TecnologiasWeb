-- Turno explicito en ofertas, paralelos y control de duplicados de materia y aulas.
-- Ejecutar sobre testdb despues de 019_offer_type_and_season.sql. Es seguro ejecutarlo mas de una vez.
-- Nota: este script es ASCII a proposito; los valores con tilde se construyen con UNHEX para
-- que el resultado sea identico sin importar la codificacion del cliente que lo ejecute.
--   manana = 6D61C3B1616E61 | mediodia = 6D6564696F64C3AD61 | tarde = 7461726465 | noche = 6E6F636865

USE testdb;

-- 1) Turno explicito de la oferta (manana, mediodia, tarde o noche).
SET @has_turno = (
  SELECT COUNT(*) FROM information_schema.columns
  WHERE table_schema = DATABASE() AND table_name = 'ofertas_tutoria' AND column_name = 'turno'
);
SET @turno_sql = IF(
  @has_turno = 0,
  "ALTER TABLE ofertas_tutoria ADD COLUMN turno VARCHAR(30) NOT NULL DEFAULT (CONVERT(UNHEX('6D61C3B1616E61') USING utf8mb4)) AFTER id_tipo_tutoria",
  'SELECT 1'
);
PREPARE turno_statement FROM @turno_sql;
EXECUTE turno_statement;
DEALLOCATE PREPARE turno_statement;

-- El default de la columna se repara por si una ejecucion anterior dejo bytes invalidos.
SET @default_sql = "ALTER TABLE ofertas_tutoria MODIFY COLUMN turno VARCHAR(30) NOT NULL DEFAULT (CONVERT(UNHEX('6D61C3B1616E61') USING utf8mb4))";
PREPARE default_statement FROM @default_sql;
EXECUTE default_statement;
DEALLOCATE PREPARE default_statement;

-- 2) El turno de cada oferta existente se hereda de la hora de su primer bloque horario.
UPDATE ofertas_tutoria o
INNER JOIN (
    SELECT oh.id_oferta, b.hora_inicio
    FROM oferta_horarios oh
    INNER JOIN bloques_horarios b ON b.id_bloque = oh.id_bloque
    WHERE oh.id_oferta_horario = (
        SELECT MIN(oh2.id_oferta_horario) FROM oferta_horarios oh2 WHERE oh2.id_oferta = oh.id_oferta
    )
) first_schedule ON first_schedule.id_oferta = o.id_oferta
SET o.turno = CASE
    WHEN first_schedule.hora_inicio < '12:00:00' THEN CONVERT(UNHEX('6D61C3B1616E61') USING utf8mb4)
    WHEN first_schedule.hora_inicio < '15:00:00' THEN CONVERT(UNHEX('6D6564696F64C3AD61') USING utf8mb4)
    WHEN first_schedule.hora_inicio < '19:00:00' THEN CONVERT(UNHEX('7461726465') USING utf8mb4)
    ELSE CONVERT(UNHEX('6E6F636865') USING utf8mb4)
END;

-- Cualquier valor restante no valido se normaliza a manana.
UPDATE ofertas_tutoria
SET turno = CONVERT(UNHEX('6D61C3B1616E61') USING utf8mb4)
WHERE HEX(turno) NOT IN ('6D61C3B1616E61', '6D6564696F64C3AD61', '7461726465', '6E6F636865');

-- 3) La materia unica se define por periodo + materia + turno + paralelo.
-- La llave nueva se crea primero porque su columna inicial (id_periodo) sostiene la FK al periodo.
SET @new_key = (
  SELECT COUNT(*) FROM information_schema.statistics
  WHERE table_schema = DATABASE() AND table_name = 'ofertas_tutoria'
    AND index_name = 'uq_oferta_periodo_materia_turno_grupo'
);
SET @add_key_sql = IF(
  @new_key = 0,
  'ALTER TABLE ofertas_tutoria ADD UNIQUE KEY uq_oferta_periodo_materia_turno_grupo (id_periodo, id_materia, turno, nombre_grupo)',
  'SELECT 1'
);
PREPARE add_key_statement FROM @add_key_sql;
EXECUTE add_key_statement;
DEALLOCATE PREPARE add_key_statement;

SET @old_key = (
  SELECT COUNT(*) FROM information_schema.statistics
  WHERE table_schema = DATABASE() AND table_name = 'ofertas_tutoria'
    AND index_name = 'uq_oferta_periodo_materia_grupo'
);
SET @drop_key_sql = IF(@old_key > 0, 'ALTER TABLE ofertas_tutoria DROP INDEX uq_oferta_periodo_materia_grupo', 'SELECT 1');
PREPARE drop_key_statement FROM @drop_key_sql;
EXECUTE drop_key_statement;
DEALLOCATE PREPARE drop_key_statement;

-- 4) Libera las aulas repetidas en el mismo periodo, dia y bloque.
CREATE TEMPORARY TABLE tmp_oferta_horario_conflicto (
  id_oferta_horario INT PRIMARY KEY,
  id_periodo INT NOT NULL,
  dia_semana VARCHAR(20) NOT NULL,
  id_bloque INT NOT NULL
);

INSERT INTO tmp_oferta_horario_conflicto (id_oferta_horario, id_periodo, dia_semana, id_bloque)
SELECT ranked.id_oferta_horario, ranked.id_periodo, ranked.dia_semana, ranked.id_bloque
FROM (
    SELECT oh.id_oferta_horario, o.id_periodo, oh.dia_semana, oh.id_bloque,
           ROW_NUMBER() OVER (
               PARTITION BY o.id_periodo, oh.dia_semana, oh.id_bloque, oh.id_aula
               ORDER BY oh.id_oferta_horario
           ) AS posicion
    FROM oferta_horarios oh
    INNER JOIN ofertas_tutoria o ON o.id_oferta = oh.id_oferta
    WHERE oh.id_aula IS NOT NULL
) ranked
WHERE ranked.posicion > 1;

CREATE TEMPORARY TABLE tmp_asignacion_aula AS
SELECT oh.id_oferta_horario, oh.id_aula, oh.dia_semana, oh.id_bloque, o.id_periodo
FROM oferta_horarios oh
INNER JOIN ofertas_tutoria o ON o.id_oferta = oh.id_oferta;

UPDATE oferta_horarios oh
INNER JOIN tmp_oferta_horario_conflicto c ON c.id_oferta_horario = oh.id_oferta_horario
SET oh.id_aula = (
    SELECT a.id_aula
    FROM aulas a
    WHERE a.estado = 'activa'
      AND NOT EXISTS (
          SELECT 1 FROM tmp_asignacion_aula t
          WHERE t.id_aula = a.id_aula
            AND t.id_periodo = c.id_periodo
            AND t.dia_semana = c.dia_semana
            AND t.id_bloque = c.id_bloque
      )
    ORDER BY a.id_aula
    LIMIT 1
);

DROP TEMPORARY TABLE tmp_asignacion_aula;
DROP TEMPORARY TABLE tmp_oferta_horario_conflicto;

-- Si algun horario quedo sin aula libre, se conserva el primero y los demas quedan sin aula.
CREATE TEMPORARY TABLE tmp_oferta_horario_residual (
  id_oferta_horario INT PRIMARY KEY
);

INSERT INTO tmp_oferta_horario_residual (id_oferta_horario)
SELECT ranked.id_oferta_horario
FROM (
    SELECT oh.id_oferta_horario,
           ROW_NUMBER() OVER (
               PARTITION BY o.id_periodo, oh.dia_semana, oh.id_bloque, oh.id_aula
               ORDER BY oh.id_oferta_horario
           ) AS posicion
    FROM oferta_horarios oh
    INNER JOIN ofertas_tutoria o ON o.id_oferta = oh.id_oferta
    WHERE oh.id_aula IS NOT NULL
) ranked
WHERE ranked.posicion > 1;

UPDATE oferta_horarios oh
INNER JOIN tmp_oferta_horario_residual r ON r.id_oferta_horario = oh.id_oferta_horario
SET oh.id_aula = NULL;

DROP TEMPORARY TABLE tmp_oferta_horario_residual;

-- 5) Elimina materias duplicadas no referenciadas (ej. "base de datos 1" vs "Base de Datos I").
CREATE TEMPORARY TABLE tmp_materia_duplicada AS
SELECT m.id_materia
FROM materias m
WHERE LOWER(TRIM(m.nombre_materia)) = 'base de datos 1'
  AND EXISTS (
      SELECT 1 FROM materias canon
      WHERE LOWER(TRIM(canon.nombre_materia)) = 'base de datos i'
        AND canon.id_materia <> m.id_materia
  )
  AND NOT EXISTS (SELECT 1 FROM tutor_materia tm WHERE tm.id_materia = m.id_materia)
  AND NOT EXISTS (SELECT 1 FROM ofertas_tutoria o WHERE o.id_materia = m.id_materia)
  AND NOT EXISTS (SELECT 1 FROM tutorias t WHERE t.id_materia = m.id_materia);

DELETE m
FROM materias m
INNER JOIN tmp_materia_duplicada d ON d.id_materia = m.id_materia;

DROP TEMPORARY TABLE tmp_materia_duplicada;
