# Modalidades de Grado — primera entrega

Este documento registra la primera fase MG para mantenerla separada del flujo de Tutorías. Las historias se derivan del prompt maestro; sus criterios se concretarán conforme se aprueben los procesos institucionales.

## Trazabilidad inicial

| HU | Alcance de esta entrega | Estado |
| --- | --- | --- |
| HU-019 | Roles `coordinador_mg` / `auxiliar_mg`, permiso del módulo y permisos MG granulares | Implementada en `db/030_mg_base.sql` y `includes/permisos.php` |
| HU-020 | Parámetros globales tipados, evidencia, validación e historial de cambios | Implementada en `mg_parametros` y `mg_parametros_historial` |
| HU-021 | Catálogo de cinco modalidades y ciclo de vida de cohortes | Implementada en `mg_modalidades` y `mg_cohortes` |
| HU-022 | Hitos configurables por cohorte y conteo dinámico de informes | Implementada en `mg_calendario` |

## Permisos

- `administrador`: superusuario técnico.
- `coordinador_mg`: configura parámetros, modalidades, cohortes e hitos.
- `auxiliar_mg`: consulta cohortes y calendarios.
- `tutor` y `estudiante`: el permiso se limita al calendario de expedientes propios; hasta que se implemente HU-024, la pantalla informa que aún no hay expedientes personales MG.
- Para esta matriz, `C` equivale a gestionar; `V`, a consultar; `V*`, a consultar únicamente los registros propios. El borrado físico no se ofrece para modalidades, cohortes ni hitos.

Los permisos detallados MG viven en `mg_permisos` y `mg_permisos_rol`. El acceso general al módulo continúa pasando por el permiso existente `modalidades-grado`.

## Esquema y ejecución

Las tablas creadas por la primera migración son:

```text
mg_permisos
mg_permisos_rol
mg_parametros
mg_modalidades
mg_cohortes
mg_calendario
```

`db/030_mg_base.sql` crea la tabla genérica `mg_parametros`. La migración aditiva `db/043_mg_general_parameters.sql` la reutiliza y añade categoría, tipo booleano, valor predeterminado, límites, visibilidad y auditoría antes/después; también agrega historiales específicos para cambios de cohorte y calendario. No reemplaza las tablas operativas ni borra parámetros históricos. Las reglas con evidencia `pendiente` o `propuesta` no se aplican; los valores predeterminados institucionales indicados como confirmados sí.

Para explorar la vista local con datos de ejemplo, `db/mg_demo_data.sql` agrega dos cohortes `[DEMO]` y sus calendarios. Es un seed repetible y no forma parte de la migración base.

## Secuencia de implementación

```text
HU-029 checklist, tribunales, defensas y cierre — implementada; pendiente validación de aceptación por modalidad con normativa institucional
```

Cada fase debe conservar los flujos y datos del módulo `/tutorias/`.

### Importación académica (HU-023)

La migración `db/034_mg_academic_imports.sql` añade lotes revisables, planes aprobados e historial académico. Aplicarla después de `db/030_mg_base.sql`.

- Solo Administración carga/revisa CSV UTF-8, separado por comas (máximo 5 MB y 10.000 filas). El archivo se conserva en `storage/mg-academic-imports`, no accesible desde una ruta pública.
- Plantilla de plan: `id_carrera,codigo_plan,version_plan,id_materia,obligatoria`. Se valida contra los catálogos; un plan/version se aprueba como una unidad y no se duplica.
- Plantilla de historial: `registro_universitario,codigo_plan,version_plan,id_materia,estado,nota,periodo`. El RU, carrera, plan aprobado y materia deben coincidir. El estado debe ser `APROBADA` o `REPROBADA` y la nota un número entre 0 y 100.
- Las filas con errores impiden aprobar el lote. Importar no publica nada: Aprobar aplica todos los datos en una transacción; Rechazar requiere observación. Se conservan archivo, hash, filas, persona que importó/revisó y fechas.
- La pantalla del estudiante solo consulta planes e historiales aprobados. El promedio presentado es la media aritmética de la mejor nota aprobada por materia obligatoria; no representa por sí mismo el promedio normativo de graduación.
- Los parámetros normativos pendientes (por ejemplo el umbral de Excelencia) no se aplican como bloqueos. La solicitud formal de MG aún requiere su fase de expedientes.

### Panel administrativo (HU-023 extensión)

Aplicar `db/035_mg_administrative_panel.sql` después de `db/034_mg_academic_imports.sql`.

- El inicio administrativo muestra importaciones, planes, solicitudes, habilitaciones, inscripciones y trabajos activos.
- **Planes por estudiante** asigna solamente un plan aprobado de la misma carrera. Cada asignación o cambio deja actor, fecha, plan anterior/nuevo y observación en `mg_estudiante_plan_historial`.
- **Oferta por carrera** configura qué modalidades activas puede solicitar cada carrera y registra quién hizo el último cambio.
- La verificación del estudiante consulta solo el plan actualmente asignado y sus historiales aprobados.

### Solicitudes y habilitación (HU-024)

Aplicar `db/036_mg_solicitudes.sql` después de `db/035_mg_administrative_panel.sql`.

- `mg_modalidades` configura la descripción, intención de trabajo grupal (deshabilitada por defecto), máximo de integrantes, y si tema y descripción son requeridos.
- El máximo global de integrantes por grupo es 3; un valor específico de modalidad lo sobrescribe. Los límites se validan también en backend al ofrecer y al incorporar estudiantes a un grupo.
- El estudiante dispone de `/modalidades-grado/mi-solicitud.php`: puede crear/editar borrador, enviar, consultar historial, corregir solicitudes observadas, reenviar y cancelar borradores u observadas.
- Aplicar `db/045_mg_solicitud_academic_evidence.sql` después de `db/044_mg_simplify_general_parameters.sql`. Si el historial oficial no está disponible o completo, el estudiante puede adjuntar a borrador/observada un PDF privado versionado de hasta 5 MB, aunque aún no tenga un plan digital asignado. La carga solo deja evidencia pendiente; no aprueba la solicitud ni copia notas al historial oficial.
- Si ya existe plan asignado, Administración contrasta cada materia obligatoria con ese plan. Si aún no existe, Administración debe identificar la malla oficial de la carrera, registrar todas sus materias obligatorias y sus notas desde el PDF, y confirmar expresamente el cotejo antes de verificar el documento. Una versión de documento revisada sin plan digital solo es válida mientras el estudiante permanezca en la misma carrera y no cambie su asignación de plan; un cambio requiere nueva evidencia.
- En `/modalidades-grado/solicitudes.php`, Administración contrasta cada materia obligatoria con el plan asignado o la malla oficial anotada manualmente si todavía no hay plan digital. Registra estado, nota y periodo. La verificación del documento y la aprobación de la solicitud son acciones distintas. Solo un documento revisado con todas las materias obligatorias aprobadas permite continuar; para Graduación por Excelencia además requiere promedio estrictamente mayor que 90.
- La evidencia verificada queda asociada a la solicitud, conserva sus versiones y queda auditada. La habilitación e inscripción formal vuelven a comprobar la elegibilidad académica.
- Solo se muestran modalidades activas y disponibles para su carrera. Toda acción verifica propiedad de la solicitud y estado de cuenta en backend.
- Enviar exige por defecto un plan asignado e historial oficial completo con todas las materias obligatorias aprobadas; si los datos oficiales aún no están disponibles, permite enviar con PDF adjunto y espera su verificación administrativa. Se guarda la captura de verificación cuando existe. La revisión y la habilitación vuelven a comprobar la elegibilidad; la aprobación administrativa continúa siendo manual y obligatoria.
- Administración revisa en `/modalidades-grado/solicitudes.php`: iniciar revisión, observar, aprobar o rechazar. Observación/rechazo requieren comentario y todas las transiciones se registran en `mg_solicitud_historial`.
- La habilitación se registra separadamente en `mg_habilitaciones`; la inscripción formal se crea después desde Administración.

### Inscripción, cohorte y trabajo (HU-025)

Aplicar `db/037_mg_inscripciones_trabajos.sql` después de `db/036_mg_solicitudes.sql`.

- Administración solo puede formalizar una solicitud aprobada, habilitada, con cuenta de estudiante activa y elegibilidad académica vigente. La operación es transaccional y no duplica inscripciones activas.
- Cada inscripción se asocia a una cohorte activa. No hay límite de estudiantes por cohorte.
- Se crea un trabajo individual, un nuevo trabajo grupal o se incorpora al estudiante a un grupo activo compatible. La compatibilidad comprueba modalidad, cohorte, carrera y tema; backend vuelve a comprobar capacidad según el máximo de modalidad o el global de 3.
- Los códigos de trabajo se generan en backend desde modalidad, año de inicio e identificador único (por ejemplo `PG-2027-001`). Las cohortes también pueden generar códigos correlativos `MG-AAAA-NN`; los códigos previos se conservan.
- El estudiante consulta su inscripción, cohorte, trabajo y cantidad de integrantes desde Mi Modalidad de Grado.

### Asignación de tutor (HU-026)

Aplicar `db/038_mg_tutor_assignments.sql` después de `db/037_mg_inscripciones_trabajos.sql`.

- Se reutiliza `tutores`; `mg_asignaciones_tutor` relaciona el tutor existente con un trabajo MG y conserva cada cambio como una asignación finalizada más una nueva asignación activa.
- La carga es la cantidad de estudiantes con inscripción e integrante activos bajo trabajos actualmente asignados al tutor, no el número de grupos.
- El límite global confirmado de tutor es 3 estudiantes activos, también validado al asignar o cambiar tutor. El sistema excluye del cálculo al estudiante que ya pertenece al trabajo reasignado para no contarle doble. Cambiar tutor exige motivo y la asignación anterior se conserva en el historial.
- Administración asigna/cambia en `/modalidades-grado/asignaciones-tutor.php`; el tutor consulta sus trabajos en `/modalidades-grado/mis-trabajos.php`.

### Calendarios, plantillas y obligaciones (HU-027)

Aplicar `db/039_mg_calendario_modalidad_plantillas.sql` después de `db/038_mg_tutor_assignments.sql`.

- `mg_calendario.id_modalidad` distingue calendarios por cohorte y modalidad. Las filas anteriores mantienen `NULL` y se presentan como legado de solo consulta; no se reasignan automáticamente.
- Los tipos de hito vienen del catálogo `mg_tipos_hito`. MDG I y MDG II conservan los códigos `mg1`/`mg2`; se agregan etapas Defensa y Cierre.
- Administración/coordinación pueden guardar una plantilla desde un calendario existente y aplicarla a otra cohorte de la misma modalidad. Las fechas se desplazan desde el inicio de cohorte; se rechazan calendarios duplicados y fechas fuera del periodo.
- Cada hito activo crea una obligación por trabajo activo correspondiente. Al inscribir un trabajo también se generan obligaciones desde el calendario ya configurado. Se conserva unicidad por hito/trabajo.
- El estudiante ve sus hitos y registra entrega, avance porcentual opcional y comentario desde su trabajo. El vencimiento se calcula.

### Seguimiento, informes, asistencia y MDG I/II (HU-028)

Aplicar `db/040_mg_seguimiento.sql` después de `db/039_mg_calendario_modalidad_plantillas.sql`.

- Administración y tutor asignado usan `/modalidades-grado/seguimiento.php`; el tutor solo accede a trabajos con asignación activa.
- Para hitos `informe`, el estudiante puede subir un PDF privado con el máximo de tamaño configurable (inicial 5 MB). Las correcciones crean versiones nuevas; tutor asignado o Administración puede iniciar revisión, observar con comentario obligatorio o aprobar la versión vigente. Correcciones máximas y entregas fuera de plazo se configuran y validan al confirmarse.
- Para otros hitos, el estudiante puede registrar un comentario y avance opcional. El vencimiento se calcula desde la fecha límite, no requiere cron; se auditan los cambios y el indicador de vencido no sobrescribe el estado operativo.
- Sesiones de tutoría/taller/seguimiento se registran dentro de MG, separadas del módulo de Tutorías tradicional, y crean asistencia para los integrantes activos. Administración o el tutor asignado registra presentes, ausentes o justificados.
- El porcentaje de presencia se calcula sobre sesiones marcadas presente/ausente; las asistencias justificadas se muestran aparte y no entran en ese denominador. El mínimo global es 80 % si la modalidad requiere asistencia y no define otro. MDG I y MDG II tienen historial propio; por defecto MDG I debe estar aprobado antes de registrar MDG II. No se usa el módulo general de Evaluaciones.

### Checklist, tribunal, defensas y cierre (HU-029)

Aplicar `db/041_mg_defensas_cierre.sql` después de `db/040_mg_seguimiento.sql`.

- Las modalidades configuran si requieren MDG I/II, informe final, tribunal y defensa; máximo de defensas, avance mínimo, composición mínima del tribunal y si el tutor puede ser miembro. Si un umbral numérico de modalidad está vacío, se usa el valor general confirmado (3 integrantes de tribunal, avance 100 %, máximo 2 defensas).
- Administración gestiona en `/modalidades-grado/defensas.php`. El checklist comprueba solo requisitos activos de modalidad: inscripción/cohorte, tutor requerido, etapas, informes, asistencia/avance configurados y tribunal si aplica.
- Los miembros se vinculan a usuarios existentes con perfil de tutor o roles de Coordinación/Auxiliar; no se crea una tabla de personas. Por configuración, puede impedirse que el tutor del trabajo forme parte de su tribunal.
- Cada defensa es un intento independiente. Reprogramar conserva su historial; cancelar no consume el límite configurado. Se pueden configurar días mínimos entre intentos y anticipación para designar tribunal. No se permite programar con requisitos pendientes por defecto. Los resultados de defensa y el cierre final quedan auditados.
- El cierre es una decisión administrativa posterior al checklist y, si la modalidad exige defensa, al resultado terminal de su último intento. Cerrar finaliza trabajo, inscripciones, integrantes y asignación activa de tutor conservando sus registros.
- Mi Modalidad de Grado permite consultar tribunal, intentos, resultado y cierre. El promedio no genera graduación automática.

La asistencia requerida y el avance se evalúan durante el checklist de defensa cuando la modalidad los configura. No bloquean la solicitud inicial. El cierre final permanece como una acción manual de Administración aunque la defensa esté aprobada.

### Parámetros generales y auditoría (HU-020 ampliada)

Aplicar `db/043_mg_general_parameters.sql` después de las migraciones MG hasta `db/042_*.sql`.

- Reutiliza `mg_parametros`: categorías, tipo booleano, valor predeterminado, rangos, visibilidad y valores fijos de solo lectura. Las claves específicas de Examen/Excelencia se conservan como históricas y sus campos migran a `mg_modalidades`.
- Los límites globales confirmados son 3 estudiantes por tutor y 3 integrantes predeterminados por grupo. Un máximo explícito de modalidad prevalece; un campo numérico nulo hereda el valor global.
- Elegibilidad académica exige por defecto plan completo y cero materias pendientes. Las decisiones de aprobación y habilitación continúan siendo acciones administrativas separadas.
- Avance general para defensa: 100 %; asistencia general: 80 %; MDG I aprobado antes de MDG II: sí; tribunal predeterminado: 3 miembros; máximo general de defensas: 2. Las reglas de modalidad pueden proporcionar requisitos más específicos.
- Las filas `propuesta` o `pendiente` se conservan pero no activan restricciones nuevas. Después de `db/044_mg_simplify_general_parameters.sql`, solo se editan los seis límites generales de capacidad, asistencia, avance, tribunal y defensas; el resto permanece para consulta histórica y el backend utiliza la política fija del proceso.
- Las ediciones de parámetros dejan valor anterior/nuevo, evidencia, fuente, actor y fecha en `mg_parametros_historial`. Cambios de cohortes e hitos se guardan en sus historiales; asignaciones, tribunales, informes, defensas y cierres usan los historiales existentes.
- Códigos automáticos de cohorte usan `MG`, año y secuencia protegida con bloqueo MySQL; códigos de trabajo usan modalidad, año e ID único. Los formatos ya no se exponen como parámetros y los códigos históricos no se reescriben.
- No hay todavía flujo de carga/verificación de documentos ni un proceso automático de alertas por reuniones; sus antiguos parámetros propuestos permanecen explícitamente como no operativos.

## Navegación simplificada

- Administración ve Resumen, Solicitudes, Cohortes, Seguimiento, Defensas y Configuración en el panel lateral. Inscripciones y Asignación de tutores se acceden desde Cohortes; el calendario y las plantillas se mantienen bajo ese mismo flujo.
- Configuración agrupa Parámetros, Modalidades, Oferta por carrera, Datos académicos y Planes por estudiante sin reemplazar sus rutas ni permisos.
- El Resumen conserva seis indicadores principales; los demás permanecen en un detalle desplegable. No se elimina ninguna función ni dato al simplificar la navegación.
