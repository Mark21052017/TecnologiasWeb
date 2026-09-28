# Modalidades de Grado — primera entrega

Este documento registra la primera fase MG para mantenerla separada del flujo de Tutorías. Las historias se derivan del prompt maestro; sus criterios se concretarán conforme se aprueben los procesos institucionales.

## Trazabilidad inicial

| HU | Alcance de esta entrega | Estado |
| --- | --- | --- |
| HU-019 | Roles `coordinador_mg` / `auxiliar_mg`, permiso del módulo y permisos MG granulares | Implementada en `db/030_mg_base.sql` y `includes/permisos.php` |
| HU-020 | Parámetros editables y evidencia `confirmado` / `pendiente` / `propuesta` | Implementada en `mg_parametros` |
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

La migración `db/030_mg_base.sql` es aditiva e idempotente; se aplica manualmente al `testdb` existente. Las reglas institucionales no confirmadas se almacenan como parámetros y no se aplican como validaciones bloqueantes.

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
- El estudiante dispone de `/modalidades-grado/mi-solicitud.php`: puede crear/editar borrador, enviar, consultar historial, corregir solicitudes observadas, reenviar y cancelar borradores u observadas.
- Solo se muestran modalidades activas y disponibles para su carrera. Toda acción verifica propiedad de la solicitud y estado de cuenta en backend.
- Enviar exige plan asignado, historial aprobado y todas las materias obligatorias aprobadas. Se guarda una captura de verificación y se listan pendientes/sin registro. La revisión administrativa vuelve a comprobar plan, historial, modalidad y estado activo del estudiante antes de aprobar y habilitar.
- Administración revisa en `/modalidades-grado/solicitudes.php`: iniciar revisión, observar, aprobar o rechazar. Observación/rechazo requieren comentario y todas las transiciones se registran en `mg_solicitud_historial`.
- La habilitación se registra separadamente en `mg_habilitaciones`; la inscripción formal se crea después desde Administración.

### Inscripción, cohorte y trabajo (HU-025)

Aplicar `db/037_mg_inscripciones_trabajos.sql` después de `db/036_mg_solicitudes.sql`.

- Administración solo puede formalizar una solicitud aprobada, habilitada, con cuenta de estudiante activa y elegibilidad académica vigente. La operación es transaccional y no duplica inscripciones activas.
- Cada inscripción se asocia a una cohorte activa. No hay límite de estudiantes por cohorte.
- Se crea un trabajo individual, un nuevo trabajo grupal o se incorpora al estudiante a un grupo activo compatible. La compatibilidad comprueba modalidad, cohorte, carrera y tema; backend vuelve a comprobar capacidad según `max_integrantes`.
- Los códigos se generan desde la modalidad, año de inicio de cohorte e ID del trabajo (por ejemplo `PG-2027-00001`). Cada trabajo empieza con un integrante y las incorporaciones quedan ligadas a una inscripción formal.
- El estudiante consulta su inscripción, cohorte, trabajo y cantidad de integrantes desde Mi Modalidad de Grado.

### Asignación de tutor (HU-026)

Aplicar `db/038_mg_tutor_assignments.sql` después de `db/037_mg_inscripciones_trabajos.sql`.

- Se reutiliza `tutores`; `mg_asignaciones_tutor` relaciona el tutor existente con un trabajo MG y conserva cada cambio como una asignación finalizada más una nueva asignación activa.
- La carga es la cantidad de estudiantes con inscripción e integrante activos bajo trabajos actualmente asignados al tutor, no el número de grupos.
- `tutor_max_estudiantes` solo bloquea cuando tiene valor positivo y `estado_evidencia = confirmado`. Si está pendiente/propuesto, la vista muestra cargas pero no impone un límite.
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
- Para hitos `informe`, el estudiante puede subir un PDF privado de hasta 5 MB. Las correcciones crean versiones nuevas; tutor asignado o Administración puede iniciar revisión, observar con comentario obligatorio o aprobar la versión vigente. La descarga comprueba la relación del usuario con el trabajo.
- Para otros hitos, el estudiante puede registrar un comentario y avance opcional. El vencimiento se calcula desde la fecha límite, no requiere cron.
- Sesiones de tutoría/taller/seguimiento se registran dentro de MG, separadas del módulo de Tutorías tradicional, y crean asistencia para los integrantes activos. Administración o el tutor asignado registra presentes, ausentes o justificados.
- El porcentaje de presencia se calcula sobre sesiones marcadas presente/ausente; las asistencias justificadas se muestran aparte y no entran en ese denominador. El porcentaje no bloquea el proceso en esta fase; el checklist de defensa lo compara con el mínimo si la modalidad requiere asistencia y este está configurado. MDG I y MDG II guardan estado, nota y observaciones propias, con historial, sin usar el módulo general de Evaluaciones.

### Checklist, tribunal, defensas y cierre (HU-029)

Aplicar `db/041_mg_defensas_cierre.sql` después de `db/040_mg_seguimiento.sql`.

- Las modalidades configuran si requieren MDG I/II, informe final, tribunal y defensa; máximo de defensas, avance mínimo, composición mínima del tribunal y si el tutor puede ser miembro. Los campos sugeridos permanecen nulos/no bloqueantes hasta ser configurados.
- Administración gestiona en `/modalidades-grado/defensas.php`. El checklist comprueba solo requisitos activos de modalidad: inscripción/cohorte, tutor requerido, etapas, informes, asistencia/avance configurados y tribunal si aplica.
- Los miembros se vinculan a usuarios existentes con perfil de tutor o roles de Coordinación/Auxiliar; no se crea una tabla de personas. Por configuración, puede impedirse que el tutor del trabajo forme parte de su tribunal.
- Cada defensa es un intento independiente. Reprogramar conserva su historial; cancelar no consume el límite configurado. Los resultados de defensa y el cierre final quedan auditados.
- El cierre es una decisión administrativa posterior al checklist y, si la modalidad exige defensa, al resultado terminal de su último intento. Cerrar finaliza trabajo, inscripciones, integrantes y asignación activa de tutor conservando sus registros.
- Mi Modalidad de Grado permite consultar tribunal, intentos, resultado y cierre. El promedio no genera graduación automática.

En HU-029, la asistencia deja de ser solo informativa para la habilitación de defensa cuando la modalidad la requiere y tiene un porcentaje mínimo configurado; no bloquea la solicitud inicial.

## Navegación simplificada

- Administración ve Resumen, Solicitudes, Cohortes, Seguimiento, Defensas y Configuración en el panel lateral. Inscripciones y Asignación de tutores se acceden desde Cohortes; el calendario y las plantillas se mantienen bajo ese mismo flujo.
- Configuración agrupa Parámetros, Modalidades, Oferta por carrera, Datos académicos y Planes por estudiante sin reemplazar sus rutas ni permisos.
- El Resumen conserva seis indicadores principales; los demás permanecen en un detalle desplegable. No se elimina ninguna función ni dato al simplificar la navegación.
