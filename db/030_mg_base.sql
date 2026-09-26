-- Base del subsistema Modalidades de Grado.
-- Aditivo e idempotente: no modifica tablas del flujo de Tutorías.
USE testdb;

INSERT INTO roles (nombre_rol)
SELECT 'coordinador_mg'
WHERE NOT EXISTS (SELECT 1 FROM roles WHERE nombre_rol = 'coordinador_mg');

INSERT INTO roles (nombre_rol)
SELECT 'auxiliar_mg'
WHERE NOT EXISTS (SELECT 1 FROM roles WHERE nombre_rol = 'auxiliar_mg');

CREATE TABLE IF NOT EXISTS mg_permisos (
    codigo VARCHAR(80) NOT NULL PRIMARY KEY,
    nombre VARCHAR(120) NOT NULL,
    descripcion VARCHAR(255) NULL,
    estado ENUM('activo', 'inactivo') NOT NULL DEFAULT 'activo',
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS mg_permisos_rol (
    id_rol INT NOT NULL,
    codigo_permiso VARCHAR(80) NOT NULL,
    permitido TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (id_rol, codigo_permiso),
    CONSTRAINT fk_mg_permisos_rol_rol
        FOREIGN KEY (id_rol) REFERENCES roles(id_rol) ON DELETE CASCADE,
    CONSTRAINT fk_mg_permisos_rol_permiso
        FOREIGN KEY (codigo_permiso) REFERENCES mg_permisos(codigo) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS mg_parametros (
    id_parametro INT AUTO_INCREMENT PRIMARY KEY,
    clave VARCHAR(100) NOT NULL UNIQUE,
    valor VARCHAR(255) NULL,
    tipo_dato ENUM('entero', 'decimal', 'texto') NOT NULL DEFAULT 'texto',
    descripcion VARCHAR(500) NOT NULL,
    fuente VARCHAR(255) NULL,
    estado_evidencia ENUM('confirmado', 'pendiente', 'propuesta') NOT NULL DEFAULT 'pendiente',
    actualizado_por INT NULL,
    fecha_actualizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_mg_parametros_usuario
        FOREIGN KEY (actualizado_por) REFERENCES usuarios(id_usuario) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS mg_modalidades (
    id_modalidad INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(60) NOT NULL UNIQUE,
    nombre VARCHAR(150) NOT NULL UNIQUE,
    requiere_tutor TINYINT(1) NOT NULL DEFAULT 0,
    estado ENUM('activa', 'inactiva') NOT NULL DEFAULT 'activa',
    creado_por INT NULL,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_mg_modalidades_usuario
        FOREIGN KEY (creado_por) REFERENCES usuarios(id_usuario) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS mg_cohortes (
    id_cohorte INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(40) NOT NULL UNIQUE,
    nombre VARCHAR(150) NOT NULL,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE NOT NULL,
    activa TINYINT(1) NOT NULL DEFAULT 1,
    creado_por INT NULL,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT chk_mg_cohortes_fechas CHECK (fecha_fin >= fecha_inicio),
    CONSTRAINT fk_mg_cohortes_usuario
        FOREIGN KEY (creado_por) REFERENCES usuarios(id_usuario) ON DELETE SET NULL,
    INDEX idx_mg_cohortes_activa_fechas (activa, fecha_inicio, fecha_fin)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS mg_calendario (
    id_hito INT AUTO_INCREMENT PRIMARY KEY,
    id_cohorte INT NOT NULL,
    etapa ENUM('previa', 'mg1', 'mg2', 'finalizado') NOT NULL,
    tipo VARCHAR(40) NOT NULL,
    nombre VARCHAR(180) NOT NULL,
    orden SMALLINT NOT NULL DEFAULT 0,
    fecha_limite DATE NULL,
    avance_esperado_pct DECIMAL(5,2) NULL,
    estado ENUM('activo', 'inactivo') NOT NULL DEFAULT 'activo',
    creado_por INT NULL,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT chk_mg_calendario_avance CHECK (avance_esperado_pct IS NULL OR avance_esperado_pct BETWEEN 0 AND 100),
    CONSTRAINT fk_mg_calendario_cohorte
        FOREIGN KEY (id_cohorte) REFERENCES mg_cohortes(id_cohorte) ON DELETE RESTRICT,
    CONSTRAINT fk_mg_calendario_usuario
        FOREIGN KEY (creado_por) REFERENCES usuarios(id_usuario) ON DELETE SET NULL,
    INDEX idx_mg_calendario_cohorte_etapa (id_cohorte, etapa, estado, orden),
    INDEX idx_mg_calendario_fecha (fecha_limite)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO modulos_sistema (clave, nombre, descripcion, orden, estado)
VALUES ('modalidades-grado', 'Modalidades de Grado', 'Gestión independiente de procesos de grado', 140, 'activo')
ON DUPLICATE KEY UPDATE
    nombre = VALUES(nombre),
    descripcion = VALUES(descripcion),
    orden = VALUES(orden),
    estado = 'activo';

INSERT INTO mg_permisos (codigo, nombre, descripcion) VALUES
    ('mg.configuracion.ver', 'Ver configuración MG', 'Acceder al panel de configuración de Modalidades de Grado'),
    ('mg.parametros.gestionar', 'Gestionar parámetros', 'Consultar y actualizar parámetros configurables de MG'),
    ('mg.modalidades.gestionar', 'Gestionar modalidades', 'Crear, editar y desactivar modalidades de grado'),
    ('mg.cohortes.ver', 'Consultar cohortes', 'Consultar cohortes de Modalidades de Grado'),
    ('mg.cohortes.gestionar', 'Gestionar cohortes', 'Crear, editar y desactivar cohortes sin eliminar historial'),
    ('mg.calendario.ver', 'Consultar calendario', 'Consultar hitos del calendario de una cohorte'),
    ('mg.calendario.gestionar', 'Gestionar calendario', 'Crear, editar y desactivar hitos del calendario'),
    ('mg.calendario.ver_propio', 'Consultar calendario propio', 'Consultar únicamente calendarios relacionados con expedientes propios')
ON DUPLICATE KEY UPDATE
    nombre = VALUES(nombre),
    descripcion = VALUES(descripcion),
    estado = 'activo';

INSERT IGNORE INTO mg_permisos_rol (id_rol, codigo_permiso, permitido)
SELECT r.id_rol, p.codigo, 1
FROM roles r
CROSS JOIN mg_permisos p
WHERE r.nombre_rol = 'coordinador_mg'
  AND p.codigo IN (
      'mg.configuracion.ver', 'mg.parametros.gestionar', 'mg.modalidades.gestionar',
      'mg.cohortes.ver', 'mg.cohortes.gestionar', 'mg.calendario.ver', 'mg.calendario.gestionar'
  );

INSERT IGNORE INTO mg_permisos_rol (id_rol, codigo_permiso, permitido)
SELECT r.id_rol, p.codigo, 1
FROM roles r
CROSS JOIN mg_permisos p
WHERE r.nombre_rol = 'auxiliar_mg'
  AND p.codigo IN ('mg.cohortes.ver', 'mg.calendario.ver');

INSERT IGNORE INTO mg_permisos_rol (id_rol, codigo_permiso, permitido)
SELECT r.id_rol, p.codigo, 1
FROM roles r
CROSS JOIN mg_permisos p
WHERE r.nombre_rol IN ('tutor', 'estudiante')
  AND p.codigo = 'mg.calendario.ver_propio';

-- El módulo aparece en la navegación; los permisos MG detallados se evalúan aparte.
INSERT INTO permisos_rol (id_rol, id_modulo, permitido)
SELECT r.id_rol, m.id_modulo, 1
FROM roles r
INNER JOIN modulos_sistema m ON m.clave = 'modalidades-grado'
WHERE r.nombre_rol IN ('coordinador_mg', 'auxiliar_mg', 'tutor', 'estudiante')
ON DUPLICATE KEY UPDATE permitido = VALUES(permitido);

INSERT IGNORE INTO mg_parametros (clave, valor, tipo_dato, descripcion, fuente, estado_evidencia) VALUES
    ('reuniones_min_semana_perfil', '2', 'entero', 'Cantidad recomendada de reuniones de tutoría por semana.', 'Prompt maestro; requiere validación institucional.', 'propuesta'),
    ('dias_alerta_sin_reunion', '10', 'entero', 'Días sin reunión para generar una alerta de seguimiento.', 'Prompt maestro; requiere validación institucional.', 'propuesta'),
    ('tutor_carga_recomendada', '3', 'entero', 'Cantidad recomendada de estudiantes MG por tutor.', 'Prompt maestro; requiere validación institucional.', 'propuesta'),
    ('tutor_max_estudiantes', NULL, 'entero', 'Límite institucional de estudiantes MG por tutor.', 'Pendiente de normativa institucional.', 'pendiente'),
    ('dias_anticipacion_tribunal', '14', 'entero', 'Anticipación recomendada para gestionar el tribunal.', 'Prompt maestro; requiere validación institucional.', 'propuesta'),
    ('tribunales_por_defensa_mg1', '2', 'entero', 'Cantidad propuesta de tribunales para defensa MG1.', 'Prompt maestro; requiere validación institucional.', 'propuesta'),
    ('tribunales_por_defensa_mg2', '2', 'entero', 'Cantidad de tribunales para defensa MG2.', 'Valor inicial del prompt; pendiente de normativa institucional.', 'pendiente'),
    ('min_interesados_examen', '12', 'entero', 'Mínimo de interesados para habilitar Examen de Grado.', 'Valor inicial del prompt; pendiente de normativa institucional.', 'pendiente'),
    ('promedio_excelencia', '90', 'decimal', 'Promedio mínimo para Graduación por Excelencia.', 'Valor inicial del prompt; pendiente de normativa institucional.', 'pendiente'),
    ('duracion_mg1_meses', '2', 'entero', 'Duración referencial de la etapa MG1 en meses.', 'Prompt maestro; requiere validación institucional.', 'propuesta'),
    ('duracion_mg2_meses', '4', 'entero', 'Duración referencial de la etapa MG2 en meses.', 'Prompt maestro; requiere validación institucional.', 'propuesta'),
    ('plazo_registro_reunion_dias', '7', 'entero', 'Plazo propuesto para registrar una reunión realizada.', 'Prompt maestro; requiere validación institucional.', 'propuesta');

INSERT IGNORE INTO mg_modalidades (codigo, nombre, requiere_tutor, estado) VALUES
    ('GRADUACION_EXCELENCIA', 'Graduación por Excelencia', 0, 'activa'),
    ('PROYECTO_GRADO', 'Proyecto de Grado', 1, 'activa'),
    ('TESIS', 'Tesis', 1, 'activa'),
    ('EXAMEN_GRADO', 'Examen de Grado', 0, 'activa'),
    ('TRABAJO_DIRIGIDO', 'Trabajo Dirigido', 1, 'activa');
