-- Catalog and audit support for general Modalidades de Grado rules.
-- Additive migration: preserves existing parameter values and modality rules.
USE testdb;

ALTER TABLE mg_modalidades
    MODIFY COLUMN impide_tutor_tribunal TINYINT(1) NULL DEFAULT NULL;

SET @min_interesados_column_exists = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='mg_modalidades' AND column_name='min_interesados');
SET @ddl = IF(@min_interesados_column_exists=0, 'ALTER TABLE mg_modalidades ADD COLUMN min_interesados SMALLINT UNSIGNED NULL AFTER miembros_minimos_tribunal', 'SELECT 1');
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @promedio_minimo_column_exists = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='mg_modalidades' AND column_name='promedio_minimo');
SET @ddl = IF(@promedio_minimo_column_exists=0, 'ALTER TABLE mg_modalidades ADD COLUMN promedio_minimo DECIMAL(5,2) NULL AFTER min_interesados', 'SELECT 1');
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @constraint_exists = (SELECT COUNT(*) FROM information_schema.table_constraints WHERE constraint_schema=DATABASE() AND table_name='mg_modalidades' AND constraint_name='chk_mg_modalidad_asistencia_pct');
SET @ddl = IF(@constraint_exists=0, 'ALTER TABLE mg_modalidades ADD CONSTRAINT chk_mg_modalidad_asistencia_pct CHECK (asistencia_minima_pct IS NULL OR asistencia_minima_pct BETWEEN 0 AND 100)', 'SELECT 1');
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @constraint_exists = (SELECT COUNT(*) FROM information_schema.table_constraints WHERE constraint_schema=DATABASE() AND table_name='mg_modalidades' AND constraint_name='chk_mg_modalidad_avance_defensa');
SET @ddl = IF(@constraint_exists=0, 'ALTER TABLE mg_modalidades ADD CONSTRAINT chk_mg_modalidad_avance_defensa CHECK (avance_requerido_defensa IS NULL OR avance_requerido_defensa BETWEEN 0 AND 100)', 'SELECT 1');
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @constraint_exists = (SELECT COUNT(*) FROM information_schema.table_constraints WHERE constraint_schema=DATABASE() AND table_name='mg_modalidades' AND constraint_name='chk_mg_modalidad_max_defensas');
SET @ddl = IF(@constraint_exists=0, 'ALTER TABLE mg_modalidades ADD CONSTRAINT chk_mg_modalidad_max_defensas CHECK (max_defensas IS NULL OR max_defensas >= 1)', 'SELECT 1');
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @constraint_exists = (SELECT COUNT(*) FROM information_schema.table_constraints WHERE constraint_schema=DATABASE() AND table_name='mg_modalidades' AND constraint_name='chk_mg_modalidad_miembros_tribunal');
SET @ddl = IF(@constraint_exists=0, 'ALTER TABLE mg_modalidades ADD CONSTRAINT chk_mg_modalidad_miembros_tribunal CHECK (miembros_minimos_tribunal IS NULL OR miembros_minimos_tribunal >= 2)', 'SELECT 1');
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @constraint_exists = (SELECT COUNT(*) FROM information_schema.table_constraints WHERE constraint_schema=DATABASE() AND table_name='mg_modalidades' AND constraint_name='chk_mg_modalidad_min_interesados');
SET @ddl = IF(@constraint_exists=0, 'ALTER TABLE mg_modalidades ADD CONSTRAINT chk_mg_modalidad_min_interesados CHECK (min_interesados IS NULL OR min_interesados >= 1)', 'SELECT 1');
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @constraint_exists = (SELECT COUNT(*) FROM information_schema.table_constraints WHERE constraint_schema=DATABASE() AND table_name='mg_modalidades' AND constraint_name='chk_mg_modalidad_promedio_minimo');
SET @ddl = IF(@constraint_exists=0, 'ALTER TABLE mg_modalidades ADD CONSTRAINT chk_mg_modalidad_promedio_minimo CHECK (promedio_minimo IS NULL OR promedio_minimo BETWEEN 0 AND 100)', 'SELECT 1');
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

ALTER TABLE mg_parametros
    MODIFY COLUMN tipo_dato ENUM('entero', 'decimal', 'texto', 'booleano') NOT NULL DEFAULT 'texto';

SET @column_exists = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='mg_parametros' AND column_name='categoria');
SET @ddl = IF(@column_exists=0, 'ALTER TABLE mg_parametros ADD COLUMN categoria VARCHAR(40) NOT NULL DEFAULT ''General'' AFTER clave', 'SELECT 1');
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @column_exists = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='mg_parametros' AND column_name='valor_defecto');
SET @ddl = IF(@column_exists=0, 'ALTER TABLE mg_parametros ADD COLUMN valor_defecto VARCHAR(255) NULL AFTER valor', 'SELECT 1');
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @column_exists = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='mg_parametros' AND column_name='minimo');
SET @ddl = IF(@column_exists=0, 'ALTER TABLE mg_parametros ADD COLUMN minimo DECIMAL(10,2) NULL AFTER descripcion', 'SELECT 1');
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @column_exists = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='mg_parametros' AND column_name='maximo');
SET @ddl = IF(@column_exists=0, 'ALTER TABLE mg_parametros ADD COLUMN maximo DECIMAL(10,2) NULL AFTER minimo', 'SELECT 1');
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @column_exists = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='mg_parametros' AND column_name='solo_lectura');
SET @ddl = IF(@column_exists=0, 'ALTER TABLE mg_parametros ADD COLUMN solo_lectura TINYINT(1) NOT NULL DEFAULT 0 AFTER maximo', 'SELECT 1');
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @column_exists = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='mg_parametros' AND column_name='visible');
SET @ddl = IF(@column_exists=0, 'ALTER TABLE mg_parametros ADD COLUMN visible TINYINT(1) NOT NULL DEFAULT 1 AFTER solo_lectura', 'SELECT 1');
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

CREATE TABLE IF NOT EXISTS mg_parametros_historial (
    id_evento BIGINT AUTO_INCREMENT PRIMARY KEY,
    id_parametro INT NOT NULL,
    valor_anterior VARCHAR(255) NULL,
    valor_nuevo VARCHAR(255) NULL,
    fuente_anterior VARCHAR(255) NULL,
    fuente_nueva VARCHAR(255) NULL,
    evidencia_anterior ENUM('confirmado','pendiente','propuesta') NOT NULL,
    evidencia_nueva ENUM('confirmado','pendiente','propuesta') NOT NULL,
    id_actor INT NOT NULL,
    ocurrido_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_mg_parametro_hist_parametro FOREIGN KEY (id_parametro) REFERENCES mg_parametros(id_parametro) ON DELETE RESTRICT,
    CONSTRAINT fk_mg_parametro_hist_usuario FOREIGN KEY (id_actor) REFERENCES usuarios(id_usuario) ON DELETE RESTRICT,
    INDEX idx_mg_parametro_historial (id_parametro, ocurrido_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS mg_calendario_historial (
    id_evento BIGINT AUTO_INCREMENT PRIMARY KEY,
    id_hito INT NOT NULL,
    accion VARCHAR(30) NOT NULL,
    antes JSON NULL,
    despues JSON NOT NULL,
    id_actor INT NOT NULL,
    ocurrido_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_mg_cal_hist_hito FOREIGN KEY (id_hito) REFERENCES mg_calendario(id_hito) ON DELETE RESTRICT,
    CONSTRAINT fk_mg_cal_hist_usuario FOREIGN KEY (id_actor) REFERENCES usuarios(id_usuario) ON DELETE RESTRICT,
    INDEX idx_mg_cal_historial (id_hito, ocurrido_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS mg_cohorte_historial (
    id_evento BIGINT AUTO_INCREMENT PRIMARY KEY,
    id_cohorte INT NOT NULL,
    accion VARCHAR(30) NOT NULL,
    antes JSON NULL,
    despues JSON NOT NULL,
    id_actor INT NOT NULL,
    ocurrido_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_mg_cohorte_hist_cohorte FOREIGN KEY (id_cohorte) REFERENCES mg_cohortes(id_cohorte) ON DELETE RESTRICT,
    CONSTRAINT fk_mg_cohorte_hist_usuario FOREIGN KEY (id_actor) REFERENCES usuarios(id_usuario) ON DELETE RESTRICT,
    INDEX idx_mg_cohorte_historial (id_cohorte, ocurrido_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO mg_parametros
    (clave, valor, tipo_dato, descripcion, fuente, estado_evidencia, categoria, valor_defecto, minimo, maximo, solo_lectura)
VALUES
    ('max_estudiantes_grupo', '3', 'entero', 'Límite global predeterminado de integrantes activos por trabajo grupal. La modalidad puede definir un máximo menor o mayor.', 'Configuración inicial solicitada', 'confirmado', 'General', '3', 2, 20, 0),
    ('tutor_max_estudiantes', '3', 'entero', 'Máximo global de estudiantes activos por tutor; se valida al asignar o cambiar tutor.', 'Configuración inicial solicitada', 'confirmado', 'General', '3', 1, 100, 0),
    ('permitir_cambio_tutor', '1', 'booleano', 'Permite reemplazar al tutor activo conservando el historial de asignaciones.', 'Configuración inicial solicitada', 'confirmado', 'General', '1', 0, 1, 0),
    ('motivo_cambio_tutor_obligatorio', '1', 'booleano', 'Exige una observación al asignar o cambiar tutor.', 'Configuración inicial solicitada', 'confirmado', 'General', '1', 0, 1, 0),
    ('verificar_materias_aprobadas', '1', 'booleano', 'Verifica el historial académico aprobado antes de enviar, aprobar, habilitar e inscribir una solicitud.', 'Configuración inicial solicitada', 'confirmado', 'Académicos', '1', 0, 1, 0),
    ('exigir_plan_completo', '1', 'booleano', 'Exige que el estudiante tenga asignado un plan académico completo y aprobado.', 'Configuración inicial solicitada', 'confirmado', 'Académicos', '1', 0, 1, 0),
    ('materias_pendientes_permitidas', '0', 'entero', 'Máximo de materias obligatorias pendientes permitido cuando la política no exige plan completo.', 'Configuración inicial solicitada', 'confirmado', 'Académicos', '0', 0, 30, 0),
    ('requiere_aprobacion_administrativa', '1', 'booleano', 'La aprobación final de solicitudes siempre requiere revisión administrativa; no se aprueba automáticamente por completar materias.', 'Regla de flujo del sistema', 'confirmado', 'Solicitudes', '1', 1, 1, 1),
    ('una_solicitud_activa_por_estudiante', '1', 'booleano', 'El sistema admite una solicitud activa por estudiante, garantizada también por una restricción única de base de datos.', 'Regla de integridad del sistema', 'confirmado', 'Solicitudes', '1', 1, 1, 1),
    ('permitir_cancelar_solicitud_enviada', '0', 'booleano', 'Permite al estudiante cancelar una solicitud enviada o en revisión. Borradores y observadas se pueden cancelar.', 'Configuración inicial solicitada', 'confirmado', 'Solicitudes', '0', 0, 1, 0),
    ('permitir_editar_solicitud_enviada', '0', 'booleano', 'Permite editar directamente una solicitud enviada o en revisión.', 'Configuración inicial solicitada', 'confirmado', 'Solicitudes', '0', 0, 1, 0),
    ('permitir_corregir_solicitud_observada', '1', 'booleano', 'Permite editar una solicitud observada.', 'Configuración inicial solicitada', 'confirmado', 'Solicitudes', '1', 0, 1, 0),
    ('permitir_reenviar_solicitud_observada', '1', 'booleano', 'Permite reenviar una solicitud observada después de corregirla.', 'Configuración inicial solicitada', 'confirmado', 'Solicitudes', '1', 0, 1, 0),
    ('max_reenvios_solicitud', '3', 'entero', 'Máximo de reenvíos de una solicitud observada; cero significa que no se permiten reenvíos.', 'Configuración inicial propuesta', 'propuesta', 'Solicitudes', '3', 0, 20, 0),
    ('control_avance', '1', 'booleano', 'Activa el control general de avance en los requisitos de defensa; la modalidad puede fijar su propio umbral.', 'Configuración inicial solicitada', 'confirmado', 'Seguimiento', '1', 0, 1, 0),
    ('avance_requerido_defensa', '100', 'decimal', 'Umbral general de avance requerido para defensa cuando la modalidad no define otro.', 'Configuración inicial solicitada', 'confirmado', 'Seguimiento', '100', 0, 100, 0),
    ('permitir_avance_superior_esperado', '1', 'booleano', 'Permite registrar un avance real superior al esperado para el hito.', 'Configuración inicial solicitada', 'confirmado', 'Seguimiento', '1', 0, 1, 0),
    ('permitir_hitos_fuera_plazo', '1', 'booleano', 'Permite entregar hitos después de su fecha límite; mantiene el comportamiento vigente hasta que la institución confirme otra regla.', 'Comportamiento vigente del sistema', 'confirmado', 'Seguimiento', '1', 0, 1, 0),
    ('control_informes', '1', 'booleano', 'Habilita la revisión de informes en Seguimiento. La modalidad determina si un trabajo requiere informes.', 'Configuración inicial solicitada', 'confirmado', 'Informes', '1', 0, 1, 0),
    ('control_tutorias_mg', '1', 'booleano', 'Activa la programación de tutorías, talleres y sesiones de seguimiento dentro del proceso MG.', 'Configuración inicial solicitada', 'confirmado', 'Seguimiento', '1', 0, 1, 0),
    ('informes_requieren_aprobacion', '1', 'booleano', 'Las entregas de informes requieren revisión antes de considerarse aprobadas.', 'Regla del sistema', 'confirmado', 'Informes', '1', 1, 1, 1),
    ('informe_final_requiere_aprobacion', '1', 'booleano', 'El informe final debe estar aprobado antes de habilitar una defensa cuando la modalidad lo requiere.', 'Regla del sistema', 'confirmado', 'Informes', '1', 1, 1, 1),
    ('requiere_archivo_informe', '1', 'booleano', 'Cada entrega de informe debe incluir un archivo adjunto.', 'Regla del sistema', 'confirmado', 'Informes', '1', 1, 1, 1),
    ('tamano_maximo_informe_mb', '5', 'entero', 'Tamaño máximo permitido para cada archivo de informe; el servidor Docker admite hasta 6 MB por archivo.', 'Configuración inicial solicitada', 'confirmado', 'Informes', '5', 1, 6, 0),
    ('tipos_archivo_informe', 'pdf', 'texto', 'Tipos de archivo admitidos actualmente por el validador de contenido.', 'Regla del sistema', 'confirmado', 'Informes', 'pdf', NULL, NULL, 1),
    ('control_asistencia', '1', 'booleano', 'Activa el control de asistencia en los requisitos de defensa cuando la modalidad lo requiere.', 'Configuración inicial solicitada', 'confirmado', 'Seguimiento', '1', 0, 1, 0),
    ('asistencia_minima_pct', '80', 'decimal', 'Asistencia mínima global cuando una modalidad requiere asistencia y no configura un porcentaje propio.', 'Configuración inicial solicitada', 'confirmado', 'Seguimiento', '80', 0, 100, 0),
    ('permitir_entrega_informe_fuera_plazo', '0', 'booleano', 'Permite entregar o corregir informes después de la fecha límite del hito.', 'Configuración inicial propuesta', 'propuesta', 'Informes', '0', 0, 1, 0),
    ('max_correcciones_informe', '3', 'entero', 'Cantidad máxima de versiones correctivas permitidas después de la primera entrega.', 'Configuración inicial propuesta', 'propuesta', 'Informes', '3', 0, 20, 0),
    ('marcar_hitos_vencidos_automaticamente', '1', 'booleano', 'Calcula el vencimiento de hitos a partir de su fecha límite, sin sobrescribir el estado operativo.', 'Regla del sistema', 'confirmado', 'Calendario', '1', 0, 1, 1),
    ('generar_hitos_desde_plantilla', '1', 'booleano', 'Permite generar hitos de cohorte y modalidad desde una plantilla de calendario.', 'Configuración inicial solicitada', 'confirmado', 'Calendario', '1', 0, 1, 0),
    ('requerir_fecha_limite_hito', '0', 'booleano', 'Exige fecha límite al crear o editar un hito de calendario.', 'Comportamiento vigente del sistema', 'confirmado', 'Calendario', '0', 0, 1, 0),
    ('permitir_modificar_fechas_cohorte_iniciada', '1', 'booleano', 'Permite editar fechas de hitos después del inicio de cohorte, registrando los cambios.', 'Comportamiento vigente del sistema', 'confirmado', 'Calendario', '1', 0, 1, 0),
    ('mdg1_aprobado_antes_mdg2', '1', 'booleano', 'Impide registrar MDG II aprobado antes de que MDG I esté aprobado.', 'Configuración inicial solicitada', 'confirmado', 'Calendario', '1', 0, 1, 0),
    ('miembros_tribunal_predeterminado', '3', 'entero', 'Cantidad predeterminada mínima de integrantes del tribunal cuando la modalidad no configura otra.', 'Configuración inicial solicitada', 'confirmado', 'Tribunal', '3', 2, 20, 0),
    ('tutor_puede_integrar_tribunal', '0', 'booleano', 'Regla general que prohíbe incluir al tutor como miembro del tribunal; una regla específica de modalidad puede ser más estricta.', 'Configuración inicial solicitada', 'confirmado', 'Tribunal', '0', 0, 1, 0),
    ('exigir_presidente_tribunal', '1', 'booleano', 'Todo tribunal debe tener exactamente un presidente.', 'Regla del sistema', 'confirmado', 'Tribunal', '1', 1, 1, 1),
    ('permitir_modificar_tribunal_asignado', '1', 'booleano', 'Permite reemplazar un tribunal previamente asignado; el historial se conserva.', 'Configuración inicial solicitada', 'confirmado', 'Tribunal', '1', 0, 1, 0),
    ('tribunal_completo_antes_defensa', '1', 'booleano', 'Una defensa no se programa antes de completar el tribunal requerido por la modalidad.', 'Regla del sistema', 'confirmado', 'Defensas', '1', 1, 1, 1),
    ('max_defensas', '2', 'entero', 'Máximo general de defensas cuando la modalidad no define un límite específico.', 'Configuración inicial solicitada', 'confirmado', 'Defensas', '2', 1, 20, 0),
    ('dias_minimos_entre_defensas', '0', 'entero', 'Días mínimos entre defensas de un mismo trabajo.', 'Configuración inicial propuesta', 'propuesta', 'Defensas', '0', 0, 365, 0),
    ('permitir_defensa_requisitos_pendientes', '0', 'booleano', 'Permite programar defensa aunque falten requisitos generales o específicos.', 'Configuración inicial solicitada', 'confirmado', 'Defensas', '0', 0, 1, 0),
    ('cierre_proceso_manual', '1', 'booleano', 'El proceso solo se cierra mediante una acción manual de Administración.', 'Regla del sistema', 'confirmado', 'Defensas', '1', 1, 1, 1),
    ('generar_codigos_automaticamente', '1', 'booleano', 'Genera automáticamente códigos de cohorte. Los códigos de trabajo siempre se generan en servidor; su patrón se configura por separado.', 'Configuración inicial solicitada', 'confirmado', 'Códigos', '1', 0, 1, 0),
    ('prefijo_codigo_cohorte', 'MG', 'texto', 'Prefijo para códigos automáticos de cohorte, por ejemplo MG-2027-01.', 'Configuración inicial solicitada', 'confirmado', 'Códigos', 'MG', NULL, NULL, 0),
    ('formato_codigo_cohorte', '{PREFIJO}-{ANIO}-{SECUENCIA:02}', 'texto', 'Formato del código de cohorte. Tokens admitidos: {PREFIJO}, {ANIO} y {SECUENCIA:02}.', 'Configuración inicial solicitada', 'confirmado', 'Códigos', '{PREFIJO}-{ANIO}-{SECUENCIA:02}', NULL, NULL, 0),
    ('formato_codigo_trabajo', '{MODALIDAD}-{ANIO}-{ID:03}', 'texto', 'Formato de código de trabajo. Tokens admitidos: {MODALIDAD}, {ANIO} e {ID:03}; el identificador mantiene unicidad concurrente.', 'Configuración inicial propuesta', 'propuesta', 'Códigos', '{MODALIDAD}-{ANIO}-{ID:03}', NULL, NULL, 0),
    ('auditoria_mg_activa', '1', 'booleano', 'La auditoría de acciones MG permanece activa y no puede deshabilitarse.', 'Regla del sistema', 'confirmado', 'Auditoría', '1', 1, 1, 1)
ON DUPLICATE KEY UPDATE
    tipo_dato = VALUES(tipo_dato),
    descripcion = VALUES(descripcion),
    categoria = VALUES(categoria),
    valor_defecto = VALUES(valor_defecto),
    minimo = VALUES(minimo),
    maximo = VALUES(maximo),
    solo_lectura = VALUES(solo_lectura),
    fuente = IF(valor IS NULL AND clave = 'tutor_max_estudiantes', VALUES(fuente), fuente),
    estado_evidencia = IF(valor IS NULL AND clave = 'tutor_max_estudiantes', 'confirmado', estado_evidencia),
    valor = IF(valor IS NULL AND clave = 'tutor_max_estudiantes', VALUES(valor), valor);

UPDATE mg_parametros
SET fuente='Configuración inicial solicitada'
WHERE clave='tutor_max_estudiantes' AND valor='3' AND estado_evidencia='confirmado'
  AND fuente='Pendiente de normativa institucional.';

UPDATE mg_parametros
SET categoria = CASE
        WHEN clave LIKE 'tutor_%' THEN 'General'
        WHEN clave LIKE 'reuniones_%' OR clave LIKE 'dias_alerta_%' OR clave LIKE 'plazo_registro_%' THEN 'Seguimiento'
        WHEN clave LIKE 'tribunales_%' THEN 'Modalidad (revisar)'
        WHEN clave = 'dias_anticipacion_tribunal' THEN 'Defensas'
        WHEN clave IN ('min_interesados_examen','promedio_excelencia') THEN 'Modalidad (revisar)'
        WHEN clave LIKE 'duracion_%' THEN 'Calendario'
        ELSE categoria
    END
WHERE categoria = 'General';

UPDATE mg_parametros
SET descripcion = 'Referencia histórica no usada como límite. El límite operativo es tutor_max_estudiantes.'
WHERE clave = 'tutor_carga_recomendada';

UPDATE mg_parametros
SET minimo=1, maximo=100, valor_defecto=COALESCE(valor_defecto,valor), categoria='General'
WHERE clave='tutor_carga_recomendada';

UPDATE mg_parametros
SET minimo=1, maximo=52, valor_defecto=COALESCE(valor_defecto,valor),
    descripcion='Referencia propuesta de reuniones de tutoría por semana; no bloquea ni genera reuniones automáticamente.'
WHERE clave='reuniones_min_semana_perfil';

UPDATE mg_parametros
SET minimo=1, maximo=365, valor_defecto=COALESCE(valor_defecto,valor),
    descripcion='Propuesta no aplicada: no existe una automatización de alertas por días sin reunión.'
WHERE clave='dias_alerta_sin_reunion';

UPDATE mg_parametros
SET minimo=0, maximo=365, valor_defecto=COALESCE(valor_defecto,valor), categoria='Defensas',
    descripcion='Anticipación mínima para designar tribunal antes de la defensa. Se aplica solo al confirmarse.'
WHERE clave='dias_anticipacion_tribunal';

UPDATE mg_parametros
SET minimo=1, maximo=36, valor_defecto=COALESCE(valor_defecto,valor), categoria='Calendario',
    descripcion='Duración referencial de la etapa; las fechas reales se definen en el calendario por cohorte y modalidad.'
WHERE clave IN ('duracion_mg1_meses','duracion_mg2_meses');

UPDATE mg_parametros
SET minimo=0, maximo=365, valor_defecto=COALESCE(valor_defecto,valor),
    descripcion='Plazo propuesto para registrar una reunión realizada; no se aplica hasta confirmar la política.'
WHERE clave='plazo_registro_reunion_dias';

UPDATE mg_modalidades m
INNER JOIN mg_parametros p ON p.clave='min_interesados_examen'
SET m.min_interesados = COALESCE(m.min_interesados, CAST(p.valor AS UNSIGNED))
WHERE @min_interesados_column_exists=0 AND m.codigo='EXAMEN_GRADO'
  AND p.valor IS NOT NULL AND p.estado_evidencia='confirmado';

UPDATE mg_modalidades m
INNER JOIN mg_parametros p ON p.clave='promedio_excelencia'
SET m.promedio_minimo = COALESCE(m.promedio_minimo, CAST(p.valor AS DECIMAL(5,2)))
WHERE @promedio_minimo_column_exists=0 AND m.codigo='GRADUACION_EXCELENCIA'
  AND p.valor IS NOT NULL AND p.estado_evidencia='confirmado';

UPDATE mg_parametros
SET visible=0,
    categoria='Modalidad (migrado)',
    descripcion=CONCAT('Migrado a la configuración de modalidad. Valor histórico: ', COALESCE(valor,'sin valor'), '.')
WHERE clave IN ('min_interesados_examen','promedio_excelencia');

UPDATE mg_parametros
SET visible=0,
    categoria='Modalidad (revisar)',
    descripcion='Clave histórica de interpretación ambigua (tribunales por defensa, no integrantes). No se aplica; configure miembros mínimos por modalidad.'
WHERE clave IN ('tribunales_por_defensa_mg1','tribunales_por_defensa_mg2');
