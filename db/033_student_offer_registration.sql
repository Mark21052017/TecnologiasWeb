-- Students register for the published subject offer; Administration assigns a tutor later.
-- Existing tutor/schedule links stay intact. Safe to rerun on testdb.
USE testdb;

SET @offer_tutor_nullable = (
    SELECT IS_NULLABLE FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'inscripciones_tutoria'
      AND column_name = 'id_oferta_tutor'
);
SET @offer_tutor_nullable_sql = IF(
    @offer_tutor_nullable = 'NO',
    'ALTER TABLE inscripciones_tutoria MODIFY COLUMN id_oferta_tutor INT NULL',
    'SELECT 1'
);
PREPARE offer_tutor_nullable_statement FROM @offer_tutor_nullable_sql;
EXECUTE offer_tutor_nullable_statement;
DEALLOCATE PREPARE offer_tutor_nullable_statement;

SET @offer_schedule_nullable = (
    SELECT IS_NULLABLE FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'inscripciones_tutoria'
      AND column_name = 'id_oferta_horario'
);
SET @offer_schedule_nullable_sql = IF(
    @offer_schedule_nullable = 'NO',
    'ALTER TABLE inscripciones_tutoria MODIFY COLUMN id_oferta_horario INT NULL',
    'SELECT 1'
);
PREPARE offer_schedule_nullable_statement FROM @offer_schedule_nullable_sql;
EXECUTE offer_schedule_nullable_statement;
DEALLOCATE PREPARE offer_schedule_nullable_statement;
