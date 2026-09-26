# Sistema web de apoyo academico para tutorias

Aplicacion inicial en PHP nativo con PDO, sesiones y MariaDB/MySQL.

El sistema incluye administracion de usuarios, catalogos academicos, perfiles, disponibilidad, tutorias, evaluaciones y auditoria. Luego de iniciar sesion, los modulos se encuentran en:

```text
/TecnologiasWeb/php/usuarios/
```

El boton `Desactivar` realiza una baja logica cambiando el estado a `inactivo`; no elimina el historial del usuario.

## Estructura

- `php/`: puntos de entrada principales de la aplicacion.
- `usuarios/`: puntos de entrada del CRUD de usuarios.
- `controller/`: controladores MVC.
- `models/`: modelos y consultas PDO.
- `views/`: plantillas HTML.
- `includes/`: bootstrap, autenticacion y conexion.
- `css/` y `js/`: recursos del navegador.
- `db/`: esquema, semillas y migraciones de `testdb`.
- `deploy/`: configuracion de Apache para Ubuntu.

Modulos disponibles:

- `/usuarios/`, `/roles/`, `/carreras/` y `/materias/`: administracion general.
- `/estudiantes/` y `/tutores/`: perfiles academicos y profesionales.
- `/asignaciones/`: compatibilidad administrativa con asignaciones legacy; el flujo nuevo usa ofertas y selección directa.
- `/disponibilidad/`: turnos universitarios seleccionados por tutores confirmados.
- `/tutorias/`: sesiones legacy y tutorias asignadas mediante inscripciones.
- `/evaluaciones/`: evaluaciones de tutorias realizadas.
- `/accesos/`: reporte de auditoria para administradores.
- `/permisos/`: configuracion de accesos por rol y excepciones por usuario.
- `/materias-disponibles/`: ofertas con tutores, horarios, aulas y cupos para estudiantes.
- `/tutores-disponibles/` y `/horarios-disponibles/`: rutas legacy que redirigen al catalogo integrado.
- `/periodos/`, `/turnos/`, `/aulas/` y `/ofertas/`: planificacion academica administrada por la universidad; cada periodo pertenece a un tipo de tutoria y cada oferta define su turno, frecuencia, calendario, paralelo y aula.
- `/solicitudes-tutor/`: revision administrativa de cuentas tutor pendientes de aprobación.
- `/inscripciones/`: seguimiento de inscripciones por estudiante, tutor y administrador.
- `/postular-tutor.php`: postulación pública para cuentas tutor pendientes de aprobación.
- `/mi-perfil/`: perfil unificado de tutor y estudiante con foto de perfil.
- `/materias-ofertadas/`: espacio del tutor para seleccionar materias ofertadas (antes `/mis-materias/`, redirige aquí).
- `/modalidades-grado/`: subsistema independiente para configuración inicial de Modalidades de Grado; sus roles y permisos granulares son propios y no transforman las tutorías existentes.

## Docker local

Docker local usa un contenedor Apache/PHP y un contenedor MySQL 8.4. Para conservar una copia de la base actual, coloque un respaldo de `testdb` como `docker/mysql/init/00-testdb.sql`; ese archivo queda fuera de GitHub. Configure las credenciales en `.env.docker` a partir de `.env.docker.example` y ejecute:

```powershell
docker compose --env-file .env.docker up -d --build
```

La aplicación queda disponible en `http://localhost:8080/`. El volumen `mysql_data` conserva la base entre reinicios. Los scripts de inicialización solo se ejecutan cuando el volumen se crea por primera vez.

La planificacion sigue el orden Tipo de tutoria -> Periodo -> Ofertas: cada periodo pertenece a un solo tipo y define su rango de actividad e inscripcion. Cada oferta academica hereda el tipo de tutoria del periodo y define la materia, el turno, la frecuencia, el calendario de fechas reales (sin domingos), el paralelo y un aula que se aplica a todas las fechas seleccionadas; los dias de horario de la oferta se derivan automaticamente de esas fechas. Una materia puede repetirse en distintos turnos o con varios paralelos dentro del mismo turno, pero nunca dos veces con el mismo periodo + materia + turno + paralelo; dos ofertas no pueden compartir aula, dia y turno en el mismo periodo. La universidad publica la oferta; el tutor la selecciona directamente en **Materias ofertadas** antes de que inicie el periodo y configura sus horarios; el estudiante revisa tutor, horario, aula y cupos antes de inscribirse. Las sesiones solo pueden programarse en las fechas habilitadas para su oferta.

La disponibilidad de aulas de una oferta también considera tutorías futuras pendientes o confirmadas que estén vinculadas a una inscripción con aula estructurada, dentro del mismo periodo, día y turno, y cuyo horario coincida. Las tutorías históricas independientes que solo tienen un texto libre de lugar no se interpretan automáticamente como un aula.

El registro universitario se genera automaticamente como `RU-0001`, `RU-0002`, etc. mediante una secuencia transaccional en la base de datos; no se acepta ni se edita desde formularios.

El registro publico esta disponible en `/register.php` y crea solamente cuentas de estudiante. La cuenta y su perfil academico se crean en una transaccion con estado `pendiente`; un administrador debe aprobarla desde **Cuentas de acceso** antes del primer inicio de sesion.

Los roles base son `administrador`, `tutor` y `estudiante`; Modalidades de Grado agrega `coordinador_mg` y `auxiliar_mg`. El administrador conserva acceso técnico total. MG utiliza permisos granulares propios (`mg_permisos`, `mg_permisos_rol`) además de la verificación de módulo general; el alcance a expedientes propios se añadirá al implementar expedientes y asignaciones.

## Modalidades de Grado

MG es un subsistema separado de `/tutorias/`; no reutiliza `tutorias`, bloques horarios ni estados de sesiones. La primera migración aditiva `db/030_mg_base.sql` crea roles, permisos granulares, parámetros configurables, modalidades, cohortes y hitos de calendario. Las pantallas de configuración están bajo `/modalidades-grado/`.

Las migraciones de este repositorio siguen `db/001_*.sql` a `db/029_*.sql`; las nuevas migraciones MG continúan desde `030`. No existe `database/init.sql`: se conservan las migraciones manuales de `db/`. El dump local `docker/mysql/init/00-testdb.sql` es una instantánea de inicialización, no el historial de migraciones.

Para aplicar la migración base al MySQL que corre en Docker, desde la raíz del proyecto:

```powershell
docker cp .\db\030_mg_base.sql tecnologiasweb-db:/tmp/030_mg_base.sql
docker exec tecnologiasweb-db sh -c 'mysql --protocol=socket -uroot -p"$MYSQL_ROOT_PASSWORD" testdb < /tmp/030_mg_base.sql'
docker exec tecnologiasweb-db rm -f /tmp/030_mg_base.sql
```

Puede ejecutarse otra vez para comprobar idempotencia. Los hitos con tipo `informe` determinan la cantidad de informes de una cohorte. Los valores con evidencia `pendiente` o `propuesta` no deben convertirse en restricciones.

Para cargar cohortes e hitos claramente identificados como demostración en un entorno local, aplicar `db/mg_demo_data.sql`. El seed es repetible, crea dos cohortes y muestra cómo puede variar la cantidad de informes por cohorte; no se ejecuta automáticamente ni debe confundirse con datos institucionales.

## Configuracion local o del servidor

1. Copiar `.env.example` como `.env` en la raiz del proyecto.
2. Configurar `DB_USER=biblioteca_user` y su contrasena real.
3. No subir `.env` a GitHub.

El usuario `biblioteca_user` ya tiene permisos sobre `testdb`. La contrasena actual debe cambiarse porque fue expuesta durante la configuracion. La aplicacion no debe usar `admin_db`.

## Base de datos

La base `testdb` debe existir antes de importar los scripts:

```bash
# Usar una cuenta administrativa para crear tablas y relaciones.
mysql -u administrador_mysql -p testdb < db/001_schema.sql
# Usar el usuario de la aplicacion para cargar datos permitidos.
mysql -u biblioteca_user -p testdb < db/002_seed.sql
mysql -u biblioteca_user -p testdb < db/003_permissions.sql
mysql -u biblioteca_user -p testdb < db/004_student_registration.sql
mysql -u biblioteca_user -p testdb < db/005_student_catalog_permissions.sql
mysql -u biblioteca_user -p testdb < db/006_tutor_permissions.sql
mysql -u biblioteca_user -p testdb < db/011_academic_offers.sql
mysql -u biblioteca_user -p testdb < db/012_demo_academic_offers.sql
mysql -u biblioteca_user -p testdb < db/013_auto_student_registration.sql
mysql -u biblioteca_user -p testdb < db/014_unique_career_name.sql
mysql -u biblioteca_user -p testdb < db/015_institutional_schedule_blocks.sql
mysql -u biblioteca_user -p testdb < db/016_tutoring_types.sql
mysql -u biblioteca_user -p testdb < db/017_tutoring_type_name.sql
mysql -u biblioteca_user -p testdb < db/018_user_profile_photos.sql
mysql -u biblioteca_user -p testdb < db/019_offer_type_and_season.sql
mysql -u biblioteca_user -p testdb < db/020_offer_turno_paralelo.sql
mysql -u biblioteca_user -p testdb < db/021_consistent_demo_records.sql
mysql -u biblioteca_user -p testdb < db/022_room_catalog.sql
mysql -u biblioteca_user -p testdb < db/023_period_turnos_and_group_defaults.sql
mysql -u biblioteca_user -p testdb < db/024_period_programming_catalogs.sql
mysql -u biblioteca_user -p testdb < db/025_turnos_replace_blocks.sql
mysql -u biblioteca_user -p testdb < db/026_period_turno_catalog_relation.sql
mysql -u biblioteca_user -p testdb < db/027_period_type_calendar.sql
mysql -u biblioteca_user -p testdb < db/028_offer_calendar.sql
mysql -u biblioteca_user -p testdb < db/029_period_tutoring_type.sql
mysql -u biblioteca_user -p testdb < db/030_mg_base.sql
```

Antes de activar el login, generar un hash real y descomentar el `INSERT` del administrador en `db/002_seed.sql`:

```bash
php -r "echo password_hash('cambiar-esta-clave', PASSWORD_DEFAULT), PHP_EOL;"
```

## Apache en Ubuntu

La raiz del repositorio se ubicara en:

```text
/var/www/html/TecnologiasWeb
```

La configuracion incluida en `deploy/apache/tecnologiasweb.conf` publica `php/`, `usuarios/`, `css/` y `js/` mediante `Alias`. Las carpetas internas permanecen protegidas. De esta forma la aplicacion se abre en:

```text
http://192.168.102.130/TecnologiasWeb/php/
```

Instalar la configuracion despues de clonar el repositorio:

```bash
sudo cp deploy/apache/tecnologiasweb.conf /etc/apache2/conf-available/tecnologiasweb.conf
sudo a2enconf tecnologiasweb
sudo apache2ctl configtest
sudo systemctl reload apache2
```

Si el repositorio aun no existe en el servidor:

```bash
sudo mkdir -p /var/www/html/TecnologiasWeb
sudo chown -R "$USER":"$USER" /var/www/html/TecnologiasWeb
git clone https://github.com/Mark21052017/TecnologiasWeb.git /var/www/html/TecnologiasWeb
```

Configurar el archivo `/var/www/html/TecnologiasWeb/.env` antes de iniciar la aplicacion.

Despues de publicar cambios desde GitHub:

```bash
cd /var/www/html/TecnologiasWeb
git pull origin main
```

Los cambios de estructura de la base se aplican ejecutando el script SQL de migracion correspondiente. Git no modifica automaticamente MySQL.

## Servidor PHP local en Windows

El archivo `.env` local usa el puerto `3307`, que corresponde al tunel SSH hacia MySQL de Ubuntu:

```powershell
ssh -N -L 3307:127.0.0.1:3306 marco_r@192.168.102.130
```

En otra terminal, desde la raiz del proyecto, iniciar PHP con el router:

```powershell
C:\php\php.exe -S 127.0.0.1:8000 router.php
```

La aplicacion local se abre en `http://127.0.0.1:8000/` y el CRUD en `http://127.0.0.1:8000/usuarios/`.
