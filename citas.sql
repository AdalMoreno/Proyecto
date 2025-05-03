CREATE DATABASE IF NOT EXISTS sistema_medico;
USE sistema_medico;

--Tabla Usuario (Base para Pacientes y Doctores)
CREATE TABLE Usuario (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    contrasena VARCHAR(255) NOT NULL,  -- Contraseña encriptada
    tipo ENUM('Paciente', 'Doctor') NOT NULL
);

--Tabla Paciente (Relacionada con Usuario)
CREATE TABLE Paciente (
    id_paciente INT PRIMARY KEY,
    fecha_nacimiento DATE NOT NULL,
    telefono VARCHAR(15) NOT NULL,
    direccion VARCHAR(255) NOT NULL,
    FOREIGN KEY (id_paciente) REFERENCES Usuario(id_usuario) ON DELETE CASCADE
);

--Tabla Doctor (Relacionada con Usuario)
CREATE TABLE Doctor (
    id_doctor INT PRIMARY KEY,
    especialidad VARCHAR(100) NOT NULL,
    telefono VARCHAR(15) NOT NULL,
    direccion VARCHAR(255) NOT NULL,
    FOREIGN KEY (id_doctor) REFERENCES Usuario(id_usuario) ON DELETE CASCADE
);

--Tabla de Citas Médicas (Pacientes agendan con Doctores)
CREATE TABLE Citas (
    id_cita INT AUTO_INCREMENT PRIMARY KEY,
    id_paciente INT NOT NULL,
    id_doctor INT NOT NULL,
    fecha_cita DATETIME NOT NULL,  -- Fecha y hora de la cita
    estado ENUM('Pendiente', 'Confirmada', 'Cancelada', 'Completada') DEFAULT 'Pendiente',
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_paciente) REFERENCES Paciente(id_paciente) ON DELETE CASCADE,
    FOREIGN KEY (id_doctor) REFERENCES Doctor(id_doctor) ON DELETE CASCADE
);

-- Tabla Historial Médico (Información médica de los pacientes)
CREATE TABLE HistorialMedico (
    id_historial INT AUTO_INCREMENT PRIMARY KEY,
    id_paciente INT NOT NULL,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    diagnostico TEXT NOT NULL,
    tratamiento TEXT NOT NULL,
    notas TEXT,  -- Notas médicas adicionales
    FOREIGN KEY (id_paciente) REFERENCES Paciente(id_paciente) ON DELETE CASCADE
);
CREATE TABLE HorarioDoctor (
    id_horario INT AUTO_INCREMENT PRIMARY KEY,
    id_doctor INT NOT NULL,
    dia ENUM('Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo') NOT NULL,
    hora_inicio TIME NOT NULL,
    hora_fin TIME NOT NULL,
    FOREIGN KEY (id_doctor) REFERENCES Doctor(id_doctor) ON DELETE CASCADE
);


-- Tabla Usuario (Base para Pacientes y Doctores)
CREATE TABLE Usuario (
    id_usuario SERIAL PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    contrasena VARCHAR(255) NOT NULL,  -- Contraseña encriptada
    tipo VARCHAR(10) NOT NULL CHECK (tipo IN ('Paciente', 'Doctor'))
);

-- Tabla Paciente (Relacionada con Usuario)
CREATE TABLE Paciente (
    id_paciente INT PRIMARY KEY,
    fecha_nacimiento DATE NOT NULL,
    telefono VARCHAR(15) NOT NULL,
    direccion VARCHAR(255) NOT NULL,
    FOREIGN KEY (id_paciente) REFERENCES Usuario(id_usuario) ON DELETE CASCADE
);

-- Tabla Doctor (Relacionada con Usuario)
CREATE TABLE Doctor (
    id_doctor INT PRIMARY KEY,
    especialidad VARCHAR(100) NOT NULL,
    telefono VARCHAR(15) NOT NULL,
    direccion VARCHAR(255) NOT NULL,
    FOREIGN KEY (id_doctor) REFERENCES Usuario(id_usuario) ON DELETE CASCADE
);

-- Tabla de Citas Médicas (Pacientes agendan con Doctores)
CREATE TABLE Citas (
    id_cita SERIAL PRIMARY KEY,
    id_paciente INT NOT NULL,
    id_doctor INT NOT NULL,
    fecha_cita TIMESTAMP NOT NULL,  -- Fecha y hora de la cita
    estado VARCHAR(15) DEFAULT 'Pendiente' CHECK (estado IN ('Pendiente', 'Confirmada', 'Cancelada', 'Completada')),
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_paciente) REFERENCES Paciente(id_paciente) ON DELETE CASCADE,
    FOREIGN KEY (id_doctor) REFERENCES Doctor(id_doctor) ON DELETE CASCADE
);

-- Tabla Historial Médico (Información médica de los pacientes)
CREATE TABLE HistorialMedico (
    id_historial SERIAL PRIMARY KEY,
    id_paciente INT NOT NULL,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    diagnostico TEXT NOT NULL,
    tratamiento TEXT NOT NULL,
    notas TEXT,
    FOREIGN KEY (id_paciente) REFERENCES Paciente(id_paciente) ON DELETE CASCADE
);

-- Tabla HorarioDoctor
CREATE TABLE HorarioDoctor (
    id_horario SERIAL PRIMARY KEY,
    id_doctor INT NOT NULL,
    dia VARCHAR(10) NOT NULL CHECK (dia IN ('Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo')),
    hora_inicio TIME NOT NULL,
    hora_fin TIME NOT NULL,
    FOREIGN KEY (id_doctor) REFERENCES Doctor(id_doctor) ON DELETE CASCADE
);
