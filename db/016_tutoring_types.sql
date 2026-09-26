-- Tipos de tutoria definidos por la universidad.
-- Ejecutar sobre testdb. Es seguro ejecutarlo mas de una vez.

USE testdb;

CREATE TABLE IF NOT EXISTS tipos_tutoria (
  id_tipo_tutoria INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  descripcion VARCHAR(500),
  estado ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',
  UNIQUE KEY uq_tipo_tutoria_nombre (nombre)
) ENGINE=InnoDB;

INSERT INTO tipos_tutoria (nombre, descripcion, estado)
SELECT 'grado', 'Tutorias para estudiantes de programas de grado.', 'activo'
WHERE NOT EXISTS (
  SELECT 1 FROM tipos_tutoria WHERE LOWER(TRIM(nombre)) = 'grado'
);

INSERT INTO tipos_tutoria (nombre, descripcion, estado)
SELECT 'posgrado', 'Tutorias para estudiantes de programas de posgrado.', 'activo'
WHERE NOT EXISTS (
  SELECT 1 FROM tipos_tutoria WHERE LOWER(TRIM(nombre)) = 'posgrado'
);

INSERT INTO modulos_sistema (clave, nombre, descripcion, orden, estado)
VALUES ('tipos_tutoria', CONVERT(0x5469706F73206465205475746F72C3AD61 USING utf8mb4), 'Tipos de tutoria del catalogo academico', 65, 'activo')
ON DUPLICATE KEY UPDATE
  nombre = VALUES(nombre), descripcion = VALUES(descripcion), orden = VALUES(orden), estado = 'activo';

INSERT INTO permisos_rol (id_rol, id_modulo, permitido)
SELECT r.id_rol, m.id_modulo,
       CASE WHEN r.nombre_rol = 'administrador' THEN 1 ELSE 0 END
FROM roles r
INNER JOIN modulos_sistema m ON m.clave = 'tipos_tutoria'
WHERE r.nombre_rol IN ('administrador', 'tutor', 'estudiante')
ON DUPLICATE KEY UPDATE permitido = VALUES(permitido);
