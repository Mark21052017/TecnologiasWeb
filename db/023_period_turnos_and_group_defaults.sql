-- Periodos can be offered in more than one turno. Safe to run repeatedly.
USE testdb;

CREATE TABLE IF NOT EXISTS periodo_turnos (
  id_periodo INT NOT NULL,
  turno VARCHAR(30) NOT NULL,
  PRIMARY KEY (id_periodo, turno),
  CONSTRAINT fk_periodo_turno_periodo FOREIGN KEY (id_periodo)
    REFERENCES periodos_tutoria(id_periodo) ON DELETE CASCADE,
  INDEX idx_periodo_turno_turno (turno)
) ENGINE=InnoDB;

INSERT IGNORE INTO periodo_turnos (id_periodo, turno)
SELECT p.id_periodo, v.turno
FROM periodos_tutoria p
CROSS JOIN (
  SELECT CONVERT(UNHEX('6D61C3B1616E61') USING utf8mb4) AS turno
  UNION ALL SELECT CONVERT(UNHEX('6D6564696F64C3AD61') USING utf8mb4)
  UNION ALL SELECT CONVERT(UNHEX('7461726465') USING utf8mb4)
  UNION ALL SELECT CONVERT(UNHEX('6E6F636865') USING utf8mb4)
) v;

UPDATE ofertas_tutoria o
LEFT JOIN ofertas_tutoria a
  ON a.id_periodo = o.id_periodo
 AND a.id_materia = o.id_materia
 AND a.turno = o.turno
 AND LOWER(TRIM(a.nombre_grupo)) = 'grupo a'
SET o.nombre_grupo = 'Grupo A'
WHERE LOWER(TRIM(o.nombre_grupo)) = 'general'
  AND a.id_oferta IS NULL;
