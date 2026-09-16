-- Registro publico de estudiantes y aprobacion administrativa.
-- Ejecutar sobre testdb despues de 003_permissions.sql.

USE testdb;

ALTER TABLE usuarios
  MODIFY estado ENUM('pendiente','activo','inactivo') NOT NULL DEFAULT 'activo';
