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
- `/asignaciones/`: materias asignadas a tutores.
- `/disponibilidad/`: horarios de atencion de tutores.
- `/tutorias/`: solicitudes y estados de tutorias.
- `/evaluaciones/`: evaluaciones de tutorias realizadas.
- `/accesos/`: reporte de auditoria para administradores.
- `/permisos/`: configuracion de accesos por rol y excepciones por usuario.
- `/materias-disponibles/`, `/tutores-disponibles/` y `/horarios-disponibles/`: consultas de solo lectura para estudiantes.
- `/postular-tutor.php`: postulación pública para cuentas tutor pendientes de aprobación.
- `/mi-perfil-tutor/` y `/mis-materias/`: espacio privado del tutor.

La operación de tutorías usa las tablas existentes sin migraciones adicionales. Los tutores administran sus materias y horarios; los estudiantes solo pueden solicitar sesiones dentro de un horario registrado. El sistema evita solapamientos de disponibilidad y de tutorías pendientes o confirmadas, controla las transiciones `pendiente -> confirmada -> realizada` y permite evaluar una sesión realizada una sola vez.

El registro publico esta disponible en `/register.php` y crea solamente cuentas de estudiante. La cuenta y su perfil academico se crean en una transaccion con estado `pendiente`; un administrador debe aprobarla desde **Cuentas de acceso** antes del primer inicio de sesion.

Los roles iniciales son `administrador`, `tutor` y `estudiante`. El administrador conserva acceso total para no bloquear la configuracion. Los permisos de los otros roles se heredan desde `permisos_rol` y pueden modificarse por usuario desde `permisos_usuario`; si no existe una excepcion, se aplica el permiso del rol.

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
