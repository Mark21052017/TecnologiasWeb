-- Permisos heredados por rol con excepciones individuales.
-- Ejecutar sobre testdb despues de 001_schema.sql y 002_seed.sql.

USE testdb;

CREATE TABLE IF NOT EXISTS modulos_sistema (
  id_modulo INT AUTO_INCREMENT PRIMARY KEY,
  clave VARCHAR(50) NOT NULL UNIQUE,
  nombre VARCHAR(100) NOT NULL,
  descripcion VARCHAR(200),
  orden SMALLINT NOT NULL DEFAULT 0,
  estado ENUM('activo','inactivo') NOT NULL DEFAULT 'activo'
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS permisos_rol (
  id_rol INT NOT NULL,
  id_modulo INT NOT NULL,
  permitido TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (id_rol, id_modulo),
  FOREIGN KEY (id_rol) REFERENCES roles(id_rol) ON DELETE CASCADE,
  FOREIGN KEY (id_modulo) REFERENCES modulos_sistema(id_modulo) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS permisos_usuario (
  id_usuario INT NOT NULL,
  id_modulo INT NOT NULL,
  permitido TINYINT(1) NOT NULL,
  PRIMARY KEY (id_usuario, id_modulo),
  FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
  FOREIGN KEY (id_modulo) REFERENCES modulos_sistema(id_modulo) ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT INTO modulos_sistema (clave, nombre, descripcion, orden) VALUES
  ('dashboard', 'Dashboard', 'Resumen general del sistema', 10),
  ('usuarios', 'Usuarios', 'Cuentas y estados de acceso', 20),
  ('roles', 'Roles', 'Roles del sistema', 30),
  ('permisos', 'Permisos', 'Acceso a modulos por rol y usuario', 40),
  ('carreras', 'Carreras', 'Catalogo de carreras', 50),
  ('materias', 'Materias', 'Catalogo de materias', 60),
  ('estudiantes', 'Estudiantes', 'Perfiles de estudiantes', 70),
  ('tutores', 'Tutores', 'Perfiles de tutores', 80),
  ('asignaciones', 'Asignaciones', 'Materias asignadas a tutores', 90),
  ('disponibilidad', 'Disponibilidad', 'Horarios de atencion', 100),
  ('tutorias', 'Tutorias', 'Solicitudes y sesiones', 110),
  ('evaluaciones', 'Evaluaciones', 'Evaluaciones de tutorias', 120),
  ('accesos', 'Registro de accesos', 'Auditoria de inicios de sesion', 130)
ON DUPLICATE KEY UPDATE
  nombre = VALUES(nombre), descripcion = VALUES(descripcion), orden = VALUES(orden), estado = 'activo';

INSERT IGNORE INTO permisos_rol (id_rol, id_modulo, permitido)
SELECT r.id_rol, m.id_modulo,
       CASE
         WHEN r.nombre_rol = 'administrador' THEN 1
         WHEN r.nombre_rol = 'tutor' AND m.clave IN ('dashboard', 'disponibilidad', 'tutorias', 'evaluaciones') THEN 1
         WHEN r.nombre_rol = 'estudiante' AND m.clave IN ('dashboard', 'tutorias', 'evaluaciones') THEN 1
         ELSE 0
       END
FROM roles r
CROSS JOIN modulos_sistema m;
