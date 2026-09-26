-- Contexto academico para periodos, ofertas, postulaciones e inscripciones.
-- Ejecutar sobre testdb. Es seguro ejecutarlo mas de una vez.

USE testdb;

CREATE TABLE IF NOT EXISTS periodos_tutoria (
  id_periodo INT AUTO_INCREMENT PRIMARY KEY,
  nombre_periodo VARCHAR(100) NOT NULL,
  fecha_inicio DATE NOT NULL,
  fecha_fin DATE NOT NULL,
  inscripcion_inicio DATE NOT NULL,
  inscripcion_fin DATE NOT NULL,
  estado ENUM('borrador','publicado','cerrado','finalizado') NOT NULL DEFAULT 'borrador',
  fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_periodo_nombre_fechas (nombre_periodo, fecha_inicio, fecha_fin),
  CONSTRAINT chk_periodo_fechas CHECK (fecha_fin >= fecha_inicio AND inscripcion_fin >= inscripcion_inicio)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS bloques_horarios (
  id_bloque INT AUTO_INCREMENT PRIMARY KEY,
  nombre_bloque VARCHAR(80) NOT NULL,
  turno VARCHAR(30) NOT NULL DEFAULT 'manana',
  hora_inicio TIME NOT NULL,
  hora_fin TIME NOT NULL,
  estado ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',
  UNIQUE KEY uq_bloque_horas (hora_inicio, hora_fin),
  CONSTRAINT chk_bloque_horas CHECK (hora_fin > hora_inicio)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS aulas (
  id_aula INT AUTO_INCREMENT PRIMARY KEY,
  nombre_aula VARCHAR(100) NOT NULL UNIQUE,
  ubicacion VARCHAR(150),
  capacidad INT NULL,
  estado ENUM('activa','inactiva') NOT NULL DEFAULT 'activa',
  CONSTRAINT chk_aula_capacidad CHECK (capacidad IS NULL OR capacidad > 0)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ofertas_tutoria (
  id_oferta INT AUTO_INCREMENT PRIMARY KEY,
  id_periodo INT NOT NULL,
  id_materia INT NOT NULL,
  nombre_grupo VARCHAR(50) NOT NULL DEFAULT 'Grupo A',
  cupo INT NOT NULL DEFAULT 20,
  descripcion VARCHAR(500),
  estado ENUM('borrador','publicada','cerrada','finalizada','cancelada') NOT NULL DEFAULT 'borrador',
  fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_periodo) REFERENCES periodos_tutoria(id_periodo),
  FOREIGN KEY (id_materia) REFERENCES materias(id_materia),
  UNIQUE KEY uq_oferta_periodo_materia_grupo (id_periodo, id_materia, nombre_grupo),
  INDEX idx_oferta_estado_periodo (estado, id_periodo),
  CONSTRAINT chk_oferta_cupo CHECK (cupo > 0)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS oferta_horarios (
  id_oferta_horario INT AUTO_INCREMENT PRIMARY KEY,
  id_oferta INT NOT NULL,
  id_bloque INT NOT NULL,
  dia_semana ENUM('Lunes','Martes','Miercoles','Jueves','Viernes','Sabado') NOT NULL,
  id_aula INT NULL,
  FOREIGN KEY (id_oferta) REFERENCES ofertas_tutoria(id_oferta) ON DELETE CASCADE,
  FOREIGN KEY (id_bloque) REFERENCES bloques_horarios(id_bloque),
  FOREIGN KEY (id_aula) REFERENCES aulas(id_aula) ON DELETE SET NULL,
  UNIQUE KEY uq_oferta_dia_bloque (id_oferta, dia_semana, id_bloque),
  INDEX idx_oferta_horario_oferta (id_oferta)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS oferta_tutores (
  id_oferta_tutor INT AUTO_INCREMENT PRIMARY KEY,
  id_oferta INT NOT NULL,
  id_tutor INT NOT NULL,
  estado ENUM('pendiente','confirmada','rechazada') NOT NULL DEFAULT 'pendiente',
  fecha_solicitud DATETIME DEFAULT CURRENT_TIMESTAMP,
  fecha_revision DATETIME NULL,
  revisado_por INT NULL,
  FOREIGN KEY (id_oferta) REFERENCES ofertas_tutoria(id_oferta) ON DELETE CASCADE,
  FOREIGN KEY (id_tutor) REFERENCES tutores(id_tutor) ON DELETE CASCADE,
  FOREIGN KEY (revisado_por) REFERENCES usuarios(id_usuario) ON DELETE SET NULL,
  UNIQUE KEY uq_oferta_tutor (id_oferta, id_tutor),
  INDEX idx_oferta_tutor_estado (id_tutor, estado)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS oferta_tutor_horarios (
  id_oferta_tutor_horario INT AUTO_INCREMENT PRIMARY KEY,
  id_oferta_tutor INT NOT NULL,
  id_oferta_horario INT NOT NULL,
  FOREIGN KEY (id_oferta_tutor) REFERENCES oferta_tutores(id_oferta_tutor) ON DELETE CASCADE,
  FOREIGN KEY (id_oferta_horario) REFERENCES oferta_horarios(id_oferta_horario) ON DELETE CASCADE,
  UNIQUE KEY uq_tutor_oferta_horario (id_oferta_tutor, id_oferta_horario)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS inscripciones_tutoria (
  id_inscripcion INT AUTO_INCREMENT PRIMARY KEY,
  id_oferta INT NOT NULL,
  id_oferta_tutor INT NOT NULL,
  id_oferta_horario INT NOT NULL,
  id_estudiante INT NOT NULL,
  estado ENUM('inscrita','cancelada','finalizada') NOT NULL DEFAULT 'inscrita',
  fecha_inscripcion DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_oferta) REFERENCES ofertas_tutoria(id_oferta),
  FOREIGN KEY (id_oferta_tutor) REFERENCES oferta_tutores(id_oferta_tutor),
  FOREIGN KEY (id_oferta_horario) REFERENCES oferta_horarios(id_oferta_horario),
  FOREIGN KEY (id_estudiante) REFERENCES estudiantes(id_estudiante),
  UNIQUE KEY uq_estudiante_oferta (id_estudiante, id_oferta),
  INDEX idx_inscripcion_oferta_estado (id_oferta, estado),
  INDEX idx_inscripcion_tutor (id_oferta_tutor, estado)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS solicitudes_tutor (
  id_solicitud INT AUTO_INCREMENT PRIMARY KEY,
  id_usuario INT NOT NULL UNIQUE,
  estado ENUM('pendiente','aprobada','rechazada') NOT NULL DEFAULT 'pendiente',
  especialidad VARCHAR(150) NOT NULL,
  biografia TEXT,
  fecha_solicitud DATETIME DEFAULT CURRENT_TIMESTAMP,
  fecha_revision DATETIME NULL,
  revisado_por INT NULL,
  observaciones_revision VARCHAR(500),
  FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
  FOREIGN KEY (revisado_por) REFERENCES usuarios(id_usuario) ON DELETE SET NULL,
  INDEX idx_solicitud_tutor_estado (estado)
) ENGINE=InnoDB;

SET @sql = IF(
    EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'materias' AND column_name = 'nombre_materia_clave'),
    'SELECT 1',
    'ALTER TABLE materias ADD COLUMN nombre_materia_clave VARCHAR(150) GENERATED ALWAYS AS (LOWER(TRIM(nombre_materia))) STORED'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS (SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'materias' AND index_name = 'uq_materia_nombre_clave'),
    'SELECT 1',
    'ALTER TABLE materias ADD UNIQUE KEY uq_materia_nombre_clave (nombre_materia_clave)'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'tutorias' AND column_name = 'id_inscripcion'),
    'SELECT 1',
    'ALTER TABLE tutorias ADD COLUMN id_inscripcion INT NULL, ADD INDEX idx_tutoria_inscripcion (id_inscripcion), ADD CONSTRAINT fk_tutoria_inscripcion FOREIGN KEY (id_inscripcion) REFERENCES inscripciones_tutoria(id_inscripcion) ON DELETE SET NULL'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

INSERT INTO solicitudes_tutor (id_usuario, estado, especialidad, biografia)
SELECT u.id_usuario,
       CASE WHEN u.estado = 'activo' THEN 'aprobada' ELSE 'pendiente' END,
       COALESCE(t.especialidad, 'Sin especificar'),
       t.biografia
FROM usuarios u
INNER JOIN tutores t ON t.id_usuario = u.id_usuario
WHERE NOT EXISTS (SELECT 1 FROM solicitudes_tutor s WHERE s.id_usuario = u.id_usuario);

INSERT INTO modulos_sistema (clave, nombre, descripcion, orden) VALUES
  ('periodos', 'Periodos', 'Periodos y fechas de tutorias', 55),
  ('bloques', 'Bloques horarios', 'Bloques definidos por la universidad', 56),
  ('ofertas', 'Ofertas academicas', 'Materias habilitadas por periodo', 57),
  ('solicitudes_tutor', 'Solicitudes de tutor', 'Postulaciones de tutores', 58),
  ('inscripciones', 'Inscripciones', 'Inscripciones de estudiantes', 115)
ON DUPLICATE KEY UPDATE
  nombre = VALUES(nombre), descripcion = VALUES(descripcion), orden = VALUES(orden), estado = 'activo';

INSERT INTO permisos_rol (id_rol, id_modulo, permitido)
SELECT r.id_rol, m.id_modulo,
       CASE
         WHEN r.nombre_rol = 'administrador' THEN 1
         WHEN r.nombre_rol = 'tutor' AND m.clave IN ('ofertas', 'bloques', 'inscripciones', 'tutorias', 'evaluaciones') THEN 1
         WHEN r.nombre_rol = 'estudiante' AND m.clave IN ('ofertas', 'inscripciones', 'tutorias', 'evaluaciones') THEN 1
         ELSE 0
       END
FROM roles r
CROSS JOIN modulos_sistema m
WHERE m.clave IN ('periodos', 'bloques', 'ofertas', 'solicitudes_tutor', 'inscripciones')
ON DUPLICATE KEY UPDATE permitido = VALUES(permitido);

INSERT INTO periodos_tutoria (nombre_periodo, fecha_inicio, fecha_fin, inscripcion_inicio, inscripcion_fin, estado)
SELECT 'Diciembre 2026', '2026-12-01', '2026-12-31', '2026-09-01', '2026-11-30', 'publicado'
WHERE NOT EXISTS (SELECT 1 FROM periodos_tutoria WHERE nombre_periodo = 'Diciembre 2026');

INSERT INTO bloques_horarios (nombre_bloque, hora_inicio, hora_fin)
SELECT CONVERT(0x4D61C3B1616E61 USING utf8mb4), '07:30:00', '10:30:00' WHERE NOT EXISTS (SELECT 1 FROM bloques_horarios WHERE hora_inicio = '07:30:00' AND hora_fin = '10:30:00');
INSERT INTO bloques_horarios (nombre_bloque, hora_inicio, hora_fin)
SELECT CONVERT(0x4D6564696F64C3AD61 USING utf8mb4), '11:00:00', '14:00:00' WHERE NOT EXISTS (SELECT 1 FROM bloques_horarios WHERE hora_inicio = '11:00:00' AND hora_fin = '14:00:00');
INSERT INTO bloques_horarios (nombre_bloque, hora_inicio, hora_fin)
SELECT 'Tarde', '15:00:00', '18:00:00' WHERE NOT EXISTS (SELECT 1 FROM bloques_horarios WHERE hora_inicio = '15:00:00' AND hora_fin = '18:00:00');
INSERT INTO bloques_horarios (nombre_bloque, hora_inicio, hora_fin)
SELECT 'Noche', '19:00:00', '22:00:00' WHERE NOT EXISTS (SELECT 1 FROM bloques_horarios WHERE hora_inicio = '19:00:00' AND hora_fin = '22:00:00');

INSERT INTO aulas (nombre_aula, ubicacion, capacidad)
SELECT 'Aula 101', 'Bloque A', 30 WHERE NOT EXISTS (SELECT 1 FROM aulas WHERE nombre_aula = 'Aula 101');
INSERT INTO aulas (nombre_aula, ubicacion, capacidad)
SELECT 'Aula 102', 'Bloque A', 30 WHERE NOT EXISTS (SELECT 1 FROM aulas WHERE nombre_aula = 'Aula 102');
