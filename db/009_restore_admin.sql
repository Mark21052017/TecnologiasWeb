-- Restaura el perfil canónico del administrador sin introducir una contraseña predeterminada.
-- Para restablecer la contraseña, asigne manualmente un hash nuevo a @admin_password_hash.

USE testdb;
SET @admin_password_hash = NULL;

UPDATE roles
SET nombre_rol = 'administrador'
WHERE id_rol = 1 OR nombre_rol = 'administrador1';

UPDATE usuarios
SET id_rol = (SELECT id_rol FROM roles WHERE nombre_rol = 'administrador' LIMIT 1),
    nombre = 'Admin',
    apellido = 'Sistema',
    correo = 'admin@tutorias.local',
    usuario = 'admin',
    contrasena_hash = COALESCE(@admin_password_hash, contrasena_hash),
    telefono = NULL,
    estado = 'activo'
WHERE id_usuario = 1 OR usuario = 'admin';
