#drop database if exists nutriassist_db;

create database if not exists nutriassist_db;
use nutriassist_db;

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
select * from usuarios;

ALTER TABLE Alimentos_Consumidos 
MODIFY COLUMN id_alimento INT NULL, -- Permite que sea NULO si viene de la IA
ADD COLUMN nombre_ia VARCHAR(150) NULL,
ADD COLUMN calorias_ia INT NULL,
ADD COLUMN proteina_ia DECIMAL(5,2) NULL,
ADD COLUMN carbs_ia DECIMAL(5,2) NULL,
ADD COLUMN grasas_ia DECIMAL(5,2) NULL;

ALTER TABLE Recetas
ADD COLUMN instrucciones TEXT, -- Aquí guardaremos los pasos en formato texto o JSON simple
ADD COLUMN calorias_totales INT DEFAULT 0,
ADD COLUMN proteina_total DECIMAL(5,2) DEFAULT 0,
ADD COLUMN carbs_total DECIMAL(5,2) DEFAULT 0,
ADD COLUMN grasas_total DECIMAL(5,2) DEFAULT 0,
ADD COLUMN etiquetas JSON NULL;

-- =========================================================
-- 1. INSERTAR ALIMENTOS BASE (Catálogo por 100g)
-- =========================================================
INSERT INTO Alimentos (id_alimento, nombre, calorias_por_100g, proteina_por_100g, carbs_por_100g, grasas_por_100g) VALUES 
(1, 'Pechuga de Pollo', 120, 22.5, 0, 2.6),
(2, 'Arroz Blanco', 130, 2.7, 28, 0.3),
(3, 'Brócoli', 35, 2.4, 7, 0.4),
(4, 'Avena en Hojuelas', 389, 16.9, 66.3, 6.9),
(5, 'Leche Entera', 61, 3.2, 4.8, 3.3),
(6, 'Plátano', 89, 1.1, 22.8, 0.3),
(7, 'Carne de Res', 250, 26, 0, 15),
(8, 'Tortilla de Maíz', 218, 5.7, 45, 2.8),
(9, 'Huevo', 155, 13, 1.1, 11),
(10, 'Espinaca', 23, 2.9, 3.6, 0.4),
(11, 'Atún', 116, 25.5, 0, 0.8),
(12, 'Aguacate', 160, 2, 8.5, 14.7),
(13, 'Tomate', 18, 0.9, 3.9, 0.2),
(14, 'Salmón', 208, 20, 0, 13),
(15, 'Espárragos', 20, 2.2, 3.9, 0.1),
(16, 'Pasta Cocida', 158, 5.8, 31, 0.9),
(17, 'Frijoles Cocidos', 132, 8.9, 23.7, 0.5),
(18, 'Tofu Firme', 144, 15.8, 2.8, 8.7),
(19, 'Manzana', 52, 0.3, 13.8, 0.2);

-- =========================================================
-- 2. INSERTAR 10 RECETAS (Con Macros Cacheados y Etiquetas JSON)
-- =========================================================
INSERT INTO Recetas (id_receta, titulo, instrucciones, imagen_url, tiempo_prep_min, permitir_ia_swap, calorias_totales, proteina_total, carbs_total, grasas_total, etiquetas) VALUES 
(1, 'Tazón de Pollo, Arroz y Brócoli', '1. Cocer el arroz. 2. Asar la pechuga de pollo. 3. Hervir el brócoli por 5 mins. 4. Servir todo en un tazón.', 'pollo_arroz.jpg', 20, TRUE, 431, 39.3, 59.5, 4.3, '["Medio"]'),
(2, 'Tacos de Asada', '1. Asar la carne de res sin aceite. 2. Calentar las tortillas. 3. Picar la carne y servir en las tortillas.', 'tacos_asada.png', 15, TRUE, 468, 31.7, 45.0, 17.8, '["Medio"]'),
(3, 'Avena Nocturna con Plátano', '1. Mezclar la avena con la leche en un frasco. 2. Refrigerar toda la noche. 3. Al día siguiente, agregar plátano en rodajas.', 'avena_platano.jpg', 5, TRUE, 421, 15.6, 75.2, 7.3, '["Economico", "Vegetariano"]'),
(4, 'Huevos Revueltos con Espinaca', '1. Batir los huevos. 2. Sofreír la espinaca. 3. Agregar los huevos y cocinar al gusto.', 'huevos_espinaca.jpg', 10, TRUE, 244, 20.9, 3.4, 16.7, '["Economico", "Keto", "+Carbs"]'),
(5, 'Ensalada Rápida de Atún y Aguacate', '1. Drenar el atún. 2. Picar el tomate y aguacate. 3. Mezclar todo en un tazón sin mayonesa.', 'ensalada_atun.jpg', 10, TRUE, 318, 39.0, 10.4, 15.7, '["Economico", "+Proteína"]'),
(6, 'Salmón al Horno con Espárragos', '1. Precalentar horno a 200°C. 2. Colocar salmón y espárragos en charola. 3. Hornear por 15 minutos.', 'salmon_esparragos.jpg', 20, TRUE, 332, 32.2, 3.9, 19.6, '["Costoso"]'),
(7, 'Pasta con Pechuga y Tomate', '1. Cocer la pasta. 2. Asar el pollo y picar el tomate. 3. Mezclar todo en un sartén por 2 mins.', 'pasta_pollo.jpg', 25, TRUE, 467, 30.6, 70.4, 4.6, '["Medio", "+Carbs"]'),
(8, 'Tazón Vegano de Tofu y Frijoles', '1. Cortar tofu en cubos y dorar en sartén. 2. Calentar frijoles. 3. Servir juntos.', 'tofu_frijoles.jpg', 15, TRUE, 344, 32.6, 39.3, 13.5, '["Economico", "Vegetariano", "Vegano"]'),
(9, 'Tostadas de Pollo Desmenuzado', '1. Desmenuzar pollo cocido. 2. Colocar sobre ostadas. 3. Agregar aguacate y tomate.', 'tostadas_pollo.jpg', 15, TRUE, 324, 25.4, 26.9, 10.1, '["Economico"]');

-- =========================================================
-- 3. VINCULAR INGREDIENTES CON CANTIDADES EXACTAS
-- =========================================================
-- Receta 1: Tazón Pollo (150g Pollo, 150g Arroz, 100g Brócoli)
INSERT INTO Ingredientes_Receta (id_receta, id_alimento, cantidad_gramos) VALUES 
(1, 1, 150), (1, 2, 150), (1, 3, 100);

-- Receta 2: Tacos de Asada (100g Carne, 100g Tortilla aprox 3 pzas)
INSERT INTO Ingredientes_Receta (id_receta, id_alimento, cantidad_gramos) VALUES 
(2, 7, 100), (2, 8, 100);

-- Receta 3: Avena Nocturna (50g Avena, 200g Leche, 100g Plátano)
INSERT INTO Ingredientes_Receta (id_receta, id_alimento, cantidad_gramos) VALUES 
(3, 4, 50), (3, 5, 200), (3, 6, 100);

-- Receta 4: Huevos (120g Huevo aprox 2 pzas, 100g Espinaca)
INSERT INTO Ingredientes_Receta (id_receta, id_alimento, cantidad_gramos) VALUES 
(4, 9, 120), (4, 10, 100);

-- Receta 5: Ensalada Atún (150g Atún, 100g Aguacate, 50g Tomate)
INSERT INTO Ingredientes_Receta (id_receta, id_alimento, cantidad_gramos) VALUES 
(5, 11, 150), (5, 12, 100), (5, 13, 50);

-- Receta 6: Salmón (150g Salmón, 100g Espárragos)
INSERT INTO Ingredientes_Receta (id_receta, id_alimento, cantidad_gramos) VALUES 
(6, 14, 150), (6, 15, 100);

-- Receta 7: Pasta con Pollo (150g Pasta, 100g Pollo, 50g Tomate)
INSERT INTO Ingredientes_Receta (id_receta, id_alimento, cantidad_gramos) VALUES 
(7, 16, 150), (7, 1, 100), (7, 13, 50);

-- Receta 8 Tazón Tofu (150g Tofu, 150g Frijoles)
INSERT INTO Ingredientes_Receta (id_receta, id_alimento, cantidad_gramos) VALUES 
(8, 18, 150), (8, 17, 150);

-- Receta 9: Tostadas Pollo (100g Pollo, 50g Tortilla, 50g Aguacate, 50g Tomate)
INSERT INTO Ingredientes_Receta (id_receta, id_alimento, cantidad_gramos) VALUES 
(9, 1, 100), (9, 8, 50), (9, 12, 50), (9, 13, 50);


ALTER TABLE Alimentos
ADD COLUMN fdc_id INT NULL UNIQUE;