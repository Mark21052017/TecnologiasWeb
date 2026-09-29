-- Solo seis reglas generales editables. Se conservan valores y auditoría históricos.
-- Ejecutar después de db/043_mg_general_parameters.sql; repetible.
USE testdb;

UPDATE mg_parametros
SET visible = CASE WHEN clave IN (
        'max_estudiantes_grupo', 'tutor_max_estudiantes',
        'asistencia_minima_pct', 'avance_requerido_defensa',
        'miembros_tribunal_predeterminado', 'max_defensas'
    ) THEN 1 ELSE 0 END,
    solo_lectura = CASE WHEN clave IN (
        'max_estudiantes_grupo', 'tutor_max_estudiantes',
        'asistencia_minima_pct', 'avance_requerido_defensa',
        'miembros_tribunal_predeterminado', 'max_defensas'
    ) THEN 0 ELSE 1 END;
