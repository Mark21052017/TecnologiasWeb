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

## Próxima secuencia

```text
HU-023 importación CSV
HU-024 expedientes e historial de etapas
HU-025/026 asignación e historial de Tutor
HU-027 plantillas y carta de asignación
```

Cada fase debe conservar los flujos y datos del módulo `/tutorias/`.
