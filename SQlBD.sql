create database if not exists nutriassist;
use nutriassist;

-- ==========================================
-- 1. CATÁLOGOS BASE (Sin llaves foráneas)
-- ==========================================

CREATE TABLE Nivel_Actividad (
    id_nivel_actividad INT AUTO_INCREMENT PRIMARY KEY,
    descripcion VARCHAR(50) NOT NULL,
    multiplicador_biometrico DECIMAL(4,3) NOT NULL
);

CREATE TABLE Restricciones_Medicas (
    id_restriccion INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE Alimentos (
    id_alimento INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    calorias_por_100g INT NOT NULL,
    proteina_por_100g DECIMAL(5,2) NOT NULL,
    carbs_por_100g DECIMAL(5,2) NOT NULL,
    grasas_por_100g DECIMAL(5,2) NOT NULL
);

-- ==========================================
-- 2. MÓDULO DE USUARIOS
-- ==========================================

CREATE TABLE Usuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    fecha_nacimiento DATE NOT NULL,
    peso_kg DECIMAL(5,2) NOT NULL,
    altura_cm INT NOT NULL,
    id_nivel_actividad INT,
    FOREIGN KEY (id_nivel_actividad) REFERENCES Nivel_Actividad(id_nivel_actividad)
);

CREATE TABLE Usuario_Restriccion (
    id_usuario INT,
    id_restriccion INT,
    PRIMARY KEY (id_usuario, id_restriccion),
    FOREIGN KEY (id_usuario) REFERENCES Usuarios(id_usuario) ON DELETE CASCADE,
    FOREIGN KEY (id_restriccion) REFERENCES Restricciones_Medicas(id_restriccion) ON DELETE CASCADE
);

-- ==========================================
-- 3. MÓDULO OPERATIVO (DIARIO)
-- ==========================================

CREATE TABLE Registros_Diarios (
    id_registro INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    fecha DATE NOT NULL,
    UNIQUE (id_usuario, fecha), -- Un usuario solo puede tener un registro por día
    FOREIGN KEY (id_usuario) REFERENCES Usuarios(id_usuario) ON DELETE CASCADE
);

CREATE TABLE Comidas (
    id_comida INT AUTO_INCREMENT PRIMARY KEY,
    id_registro INT NOT NULL,
    tipo ENUM('Desayuno', 'Comida', 'Cena', 'Snack') NOT NULL,
    FOREIGN KEY (id_registro) REFERENCES Registros_Diarios(id_registro) ON DELETE CASCADE
);

CREATE TABLE Alimentos_Consumidos (
    id_consumo INT AUTO_INCREMENT PRIMARY KEY,
    id_comida INT NOT NULL,
    id_alimento INT NOT NULL,
    cantidad_gramos DECIMAL(7,2) NOT NULL,
    FOREIGN KEY (id_comida) REFERENCES Comidas(id_comida) ON DELETE CASCADE,
    FOREIGN KEY (id_alimento) REFERENCES Alimentos(id_alimento)
);

-- ==========================================
-- 4. MÓDULO DE RECETAS
-- ==========================================

CREATE TABLE Recetas (
    id_receta INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(150) NOT NULL,
    imagen_url VARCHAR(255),
    tiempo_prep_min INT,
    permitir_ia_swap BOOLEAN DEFAULT TRUE
);

CREATE TABLE Ingredientes_Receta (
    id_ingrediente INT AUTO_INCREMENT PRIMARY KEY,
    id_receta INT NOT NULL,
    id_alimento INT NOT NULL,
    cantidad_gramos DECIMAL(7,2) NOT NULL,
    FOREIGN KEY (id_receta) REFERENCES Recetas(id_receta) ON DELETE CASCADE,
    FOREIGN KEY (id_alimento) REFERENCES Alimentos(id_alimento)
);