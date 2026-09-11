# Sistema web de apoyo academico para tutorias

Aplicacion inicial en PHP nativo con PDO, sesiones y MariaDB/MySQL.

El primer modulo funcional es la administracion de usuarios. Luego de iniciar sesion como administrador, se accede desde:

```text
/TecnologiasWeb/php/usuarios/
```

El boton `Desactivar` realiza una baja logica cambiando el estado a `inactivo`; no elimina el historial del usuario.

## Estructura

- `public/`: unico directorio que debe publicar Apache.
- `app/`: configuracion, logica, modelos y vistas.
- `database/`: esquema y datos iniciales de `testdb`.
- `practica1/`: practica original conservada del repositorio.

## Configuracion local o del servidor

1. Copiar `.env.example` como `.env` en la raiz del proyecto.
2. Configurar `DB_USER=biblioteca_user` y su contrasena real.
3. No subir `.env` a GitHub.

El usuario `biblioteca_user` ya tiene permisos sobre `testdb`. La contrasena actual debe cambiarse porque fue expuesta durante la configuracion. La aplicacion no debe usar `admin_db`.

## Base de datos

La base `testdb` debe existir antes de importar los scripts:

```bash
# Usar una cuenta administrativa para crear tablas y relaciones.
mysql -u administrador_mysql -p testdb < database/001_schema.sql
# Usar el usuario de la aplicacion para cargar datos permitidos.
mysql -u biblioteca_user -p testdb < database/002_seed.sql
```

Antes de activar el login, generar un hash real y descomentar el `INSERT` del administrador en `database/002_seed.sql`:

```bash
php -r "echo password_hash('cambiar-esta-clave', PASSWORD_DEFAULT), PHP_EOL;"
```

## Apache en Ubuntu

El repositorio se ubicara en:

```text
/var/www/html/TecnologiasWeb/php
```

La configuracion incluida en `deploy/apache/tecnologiasweb.conf` publica solamente `public/` mediante un `Alias`. De esta forma la aplicacion se abre en:

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
sudo mkdir -p /var/www/html/TecnologiasWeb/php
sudo chown -R "$USER":"$USER" /var/www/html/TecnologiasWeb/php
git clone https://github.com/Mark21052017/TecnologiasWeb.git /var/www/html/TecnologiasWeb/php
```

Configurar el archivo `/var/www/html/TecnologiasWeb/php/.env` antes de iniciar la aplicacion.

Despues de publicar cambios desde GitHub:

```bash
cd /var/www/html/TecnologiasWeb/php
git pull origin main
```

Los cambios de estructura de la base se aplican ejecutando el script SQL de migracion correspondiente. Git no modifica automaticamente MySQL.
