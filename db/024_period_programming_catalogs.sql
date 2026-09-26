-- Normaliza periodos, tipos de tutoria, frecuencias y turnos de bloques.
-- Ejecutar sobre testdb. Es seguro ejecutarlo mas de una vez.
USE testdb;

CREATE TABLE IF NOT EXISTS periodo_tipos_tutoria (
  id_periodo INT NOT NULL,
  id_tipo_tutoria INT NOT NULL,
  frecuencia VARCHAR(20) NOT NULL DEFAULT 'mensual',
  PRIMARY KEY (id_periodo, id_tipo_tutoria),
  CONSTRAINT fk_periodo_tipo_periodo FOREIGN KEY (id_periodo)
    REFERENCES periodos_tutoria(id_periodo) ON DELETE CASCADE,
  CONSTRAINT fk_periodo_tipo_tipo FOREIGN KEY (id_tipo_tutoria)
    REFERENCES tipos_tutoria(id_tipo_tutoria),
  CONSTRAINT chk_periodo_tipo_frecuencia CHECK (frecuencia IN ('mensual','semanal','diaria'))
) ENGINE=InnoDB;

UPDATE periodo_turnos
SET turno = CONVERT(UNHEX('6D61C3B1616E61') USING utf8mb4)
WHERE HEX(turno) = '6D616E616E61';

INSERT IGNORE INTO periodo_tipos_tutoria (id_periodo, id_tipo_tutoria, frecuencia)
SELECT DISTINCT o.id_periodo, o.id_tipo_tutoria, 'mensual'
FROM ofertas_tutoria o;

INSERT IGNORE INTO periodo_tipos_tutoria (id_periodo, id_tipo_tutoria, frecuencia)
SELECT p.id_periodo, tt.id_tipo_tutoria, 'mensual'
FROM periodos_tutoria p
CROSS JOIN tipos_tutoria tt
WHERE tt.estado = 'activo'
  AND NOT EXISTS (
    SELECT 1 FROM periodo_tipos_tutoria existing
    WHERE existing.id_periodo = p.id_periodo
  );

SET @sql = IF(
    EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'bloques_horarios' AND column_name = 'turno'),
    'SELECT 1',
    "ALTER TABLE bloques_horarios ADD COLUMN turno VARCHAR(30) NOT NULL DEFAULT (CONVERT(UNHEX('6D61C3B1616E61') USING utf8mb4)) AFTER nombre_bloque"
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

UPDATE bloques_horarios
SET turno = CASE
    WHEN HEX(LOWER(nombre_bloque)) = '6D61C3B1616E61' THEN CONVERT(UNHEX('6D61C3B1616E61') USING utf8mb4)
    WHEN HEX(LOWER(nombre_bloque)) = '6D6564696F64C3AD61' THEN CONVERT(UNHEX('6D6564696F64C3AD61') USING utf8mb4)
    WHEN HEX(LOWER(nombre_bloque)) = '7461726465' THEN CONVERT(UNHEX('7461726465') USING utf8mb4)
    WHEN HEX(LOWER(nombre_bloque)) = '6E6F636865' THEN CONVERT(UNHEX('6E6F636865') USING utf8mb4)
    WHEN hora_inicio < '12:00:00' THEN CONVERT(UNHEX('6D61C3B1616E61') USING utf8mb4)
    WHEN hora_inicio < '15:00:00' THEN CONVERT(UNHEX('6D6564696F64C3AD61') USING utf8mb4)
    WHEN hora_inicio < '19:00:00' THEN CONVERT(UNHEX('7461726465') USING utf8mb4)
    ELSE CONVERT(UNHEX('6E6F636865') USING utf8mb4)
END;

SET @sql = IF(
    EXISTS (SELECT 1 FROM information_schema.table_constraints WHERE table_schema = DATABASE() AND table_name = 'ofertas_tutoria' AND constraint_name = 'fk_oferta_periodo_tipo'),
    'SELECT 1',
    'ALTER TABLE ofertas_tutoria ADD CONSTRAINT fk_oferta_periodo_tipo FOREIGN KEY (id_periodo, id_tipo_tutoria) REFERENCES periodo_tipos_tutoria(id_periodo, id_tipo_tutoria)'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS (SELECT 1 FROM information_schema.table_constraints WHERE table_schema = DATABASE() AND table_name = 'ofertas_tutoria' AND constraint_name = 'fk_oferta_periodo_turno'),
    'SELECT 1',
    'ALTER TABLE ofertas_tutoria ADD CONSTRAINT fk_oferta_periodo_turno FOREIGN KEY (id_periodo, turno) REFERENCES periodo_turnos(id_periodo, turno)'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'periodos_tutoria' AND column_name = 'temporada'),
    'ALTER TABLE periodos_tutoria DROP COLUMN temporada',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
