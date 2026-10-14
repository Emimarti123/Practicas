-- Esquema de la base de datos "escuela" (desde cero)
-- Uso: mysql -u root -p escuela < esquema.sql
-- OJO: borra las tablas existentes y sus datos.

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS calificaciones;
DROP TABLE IF EXISTS plan_estudios;
DROP TABLE IF EXISTS materias;
DROP TABLE IF EXISTS usuarios;
DROP TABLE IF EXISTS carreras;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE carreras (
    id     INT NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    PRIMARY KEY (id)
);

-- Alumnos, docentes y administradores viven en la misma tabla; "rol" los distingue.
-- id = número de control (alumnos y docentes)
CREATE TABLE usuarios (
    id                 INT NOT NULL,
    nombre             VARCHAR(100) NOT NULL,
    email              VARCHAR(150) NOT NULL,
    password           VARCHAR(255) NOT NULL,
    rol                ENUM('alumno', 'maestro', 'admin') NOT NULL DEFAULT 'alumno',
    estado             ENUM('pendiente', 'activo', 'rechazado') NOT NULL DEFAULT 'pendiente',
    carrera_id         INT DEFAULT NULL,  -- solo alumnos
    email_verificado   TINYINT(1) DEFAULT 0,
    token_verification VARCHAR(100) DEFAULT NULL,
    created_at         TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY (email),
    FOREIGN KEY (carrera_id) REFERENCES carreras (id)
);

-- Cada materia tiene un solo maestro (NULL = sin asignar)
CREATE TABLE materias (
    id         VARCHAR(20) NOT NULL,
    nombre     VARCHAR(100) NOT NULL,
    maestro_id INT DEFAULT NULL,
    PRIMARY KEY (id),
    FOREIGN KEY (maestro_id) REFERENCES usuarios (id)
);

-- Qué materias lleva cada carrera (una materia puede estar en varias carreras)
CREATE TABLE plan_estudios (
    carrera_id INT NOT NULL,
    materia_id VARCHAR(20) NOT NULL,
    PRIMARY KEY (carrera_id, materia_id),
    FOREIGN KEY (carrera_id) REFERENCES carreras (id),
    FOREIGN KEY (materia_id) REFERENCES materias (id)
);

-- Una fila = alumno inscrito en una materia; calificacion NULL = sin calificar
CREATE TABLE calificaciones (
    id           INT NOT NULL AUTO_INCREMENT,
    usuario_id   INT NOT NULL,
    materia_id   VARCHAR(20) NOT NULL,
    calificacion DECIMAL(5,2) DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY (usuario_id, materia_id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios (id),
    FOREIGN KEY (materia_id) REFERENCES materias (id)
);

-- ---------------------------------------------------------------
-- Datos de prueba: 6 carreras con 7 materias cada una
-- ---------------------------------------------------------------

INSERT INTO carreras (id, nombre) VALUES
    (1, 'Ingeniería en Sistemas Computacionales'),
    (2, 'Arquitectura'),
    (3, 'Ingeniería Civil'),
    (4, 'Optometría'),
    (5, 'Ingeniería Mecatrónica'),
    (6, 'Diseño Gráfico');

INSERT INTO materias (id, nombre) VALUES
    -- Compartidas entre varias carreras
    ('ING101', 'Inglés I'),
    ('CAL101', 'Cálculo diferencial'),
    ('PRG101', 'Fundamentos de programación'),
    ('DIB101', 'Dibujo técnico'),
    ('HIS101', 'Historia del arte'),
    ('MCO101', 'Materiales de construcción'),
    ('QUI101', 'Química general'),
    -- Sistemas
    ('MDI101', 'Matemáticas discretas'),
    ('BD101',  'Fundamentos de bases de datos'),
    ('RED101', 'Redes de computadoras'),
    ('SO101',  'Sistemas operativos'),
    -- Arquitectura
    ('TAR101', 'Teoría de la arquitectura'),
    ('GEO101', 'Geometría descriptiva'),
    ('TDA101', 'Taller de diseño arquitectónico I'),
    -- Civil
    ('EST101', 'Estática'),
    ('TOP101', 'Topografía'),
    -- Optometría
    ('ANO101', 'Anatomía ocular'),
    ('OPG101', 'Óptica geométrica'),
    ('FIO101', 'Fisiología ocular'),
    ('BIO101', 'Bioquímica'),
    ('OPC101', 'Fundamentos de optometría clínica'),
    -- Mecatrónica
    ('ELA101', 'Electrónica analógica'),
    ('MEC101', 'Mecánica clásica'),
    ('CIR101', 'Circuitos eléctricos'),
    -- Diseño gráfico
    ('DFH101', 'Dibujo de figura humana'),
    ('TIP101', 'Tipografía'),
    ('COL101', 'Teoría del color'),
    ('FOT101', 'Fotografía'),
    ('DDI101', 'Diseño digital');

INSERT INTO plan_estudios (carrera_id, materia_id) VALUES
    -- 1. Sistemas
    (1, 'ING101'), (1, 'CAL101'), (1, 'PRG101'), (1, 'MDI101'), (1, 'BD101'), (1, 'RED101'), (1, 'SO101'),
    -- 2. Arquitectura
    (2, 'ING101'), (2, 'DIB101'), (2, 'HIS101'), (2, 'MCO101'), (2, 'TAR101'), (2, 'GEO101'), (2, 'TDA101'),
    -- 3. Civil
    (3, 'ING101'), (3, 'CAL101'), (3, 'DIB101'), (3, 'MCO101'), (3, 'QUI101'), (3, 'EST101'), (3, 'TOP101'),
    -- 4. Optometría
    (4, 'ING101'), (4, 'QUI101'), (4, 'ANO101'), (4, 'OPG101'), (4, 'FIO101'), (4, 'BIO101'), (4, 'OPC101'),
    -- 5. Mecatrónica
    (5, 'ING101'), (5, 'CAL101'), (5, 'PRG101'), (5, 'DIB101'), (5, 'ELA101'), (5, 'MEC101'), (5, 'CIR101'),
    -- 6. Diseño gráfico
    (6, 'ING101'), (6, 'HIS101'), (6, 'DFH101'), (6, 'TIP101'), (6, 'COL101'), (6, 'FOT101'), (6, 'DDI101');
