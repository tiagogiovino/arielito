CREATE DATABASE sistema_examenes;
USE sistema_examenes;

-- 1. ALUMNOS
CREATE TABLE alumno (
    id_alumno INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(50) NOT NULL,
    apellido VARCHAR(50) NOT NULL,
    dni VARCHAR(20) UNIQUE NOT NULL,
    curso VARCHAR(30)
);

-- 2. DOCENTES
CREATE TABLE docente (
    id_docente INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(50) NOT NULL,
    apellido VARCHAR(50) NOT NULL,
    dni VARCHAR(20) UNIQUE NOT NULL
);

-- 3. PRECEPTORES
CREATE TABLE preceptor (
    id_preceptor INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(50) NOT NULL,
    apellido VARCHAR(50) NOT NULL,
    dni VARCHAR(20) UNIQUE NOT NULL
);

-- 4. AULAS
CREATE TABLE aula (
    id_aula INT PRIMARY KEY AUTO_INCREMENT,
    numero VARCHAR(10) NOT NULL,
    capacidad INT
);

-- 5. MATERIAS
CREATE TABLE materia (
    id_materia INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL
);

-- 6. CORRELATIVAS
CREATE TABLE correlativa (
    id_materia INT,
    id_materia_correlativa INT,
    PRIMARY KEY (id_materia, id_materia_correlativa),
    FOREIGN KEY (id_materia) REFERENCES materia(id_materia),
    FOREIGN KEY (id_materia_correlativa) REFERENCES materia(id_materia)
);

-- 7. MESAS DE EXAMEN
CREATE TABLE mesa (
    id_mesa INT PRIMARY KEY AUTO_INCREMENT,
    fecha DATE NOT NULL,
    horario TIME NOT NULL,
    id_aula INT,
    id_docente INT,
    id_materia INT,
    FOREIGN KEY (id_aula) REFERENCES aula(id_aula),
    FOREIGN KEY (id_docente) REFERENCES docente(id_docente),
    FOREIGN KEY (id_materia) REFERENCES materia(id_materia)
);

-- 8. INSCRIPCIONES
CREATE TABLE inscripcion (
    id_inscripcion INT PRIMARY KEY AUTO_INCREMENT,
    fecha_inscripcion DATE NOT NULL,
    estado VARCHAR(30),
    id_alumno INT NOT NULL,
    id_mesa INT NOT NULL,
    FOREIGN KEY (id_alumno) REFERENCES alumno(id_alumno),
    FOREIGN KEY (id_mesa) REFERENCES mesa(id_mesa)
);

-- 9. NOTAS
CREATE TABLE nota (
    id_nota INT PRIMARY KEY AUTO_INCREMENT,
    calificacion DECIMAL(4,2),
    fecha DATE,
    id_inscripcion INT UNIQUE NOT NULL,
    FOREIGN KEY (id_inscripcion) REFERENCES inscripcion(id_inscripcion)
);

-- 10. COMPROBANTES
CREATE TABLE comprobante (
    id_comprobante INT PRIMARY KEY AUTO_INCREMENT,
    fecha_emision DATETIME NOT NULL,
    pdf VARCHAR(255),
    qr VARCHAR(255),
    id_inscripcion INT UNIQUE NOT NULL,
    FOREIGN KEY (id_inscripcion) REFERENCES inscripcion(id_inscripcion)
);

-- 11. VALIDACIÓN DE INGRESO
CREATE TABLE validacion_ingreso (
    id_validacion INT PRIMARY KEY AUTO_INCREMENT,
    fecha_hora DATETIME NOT NULL,
    resultado VARCHAR(30),
    id_comprobante INT NOT NULL,
    id_preceptor INT NOT NULL,
    FOREIGN KEY (id_comprobante) REFERENCES comprobante(id_comprobante),
    FOREIGN KEY (id_preceptor) REFERENCES preceptor(id_preceptor)
);
