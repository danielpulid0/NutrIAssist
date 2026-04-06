-- ==========================================
-- SCRIPT COMPLETO NUTRIASSIST DB
-- ==========================================

-- ==========================================
-- 1. DROP Y CREATE DATABASE
-- ==========================================
DROP DATABASE IF EXISTS nutriassist_db;
CREATE DATABASE IF NOT EXISTS nutriassist_db;
USE nutriassist_db;

-- ==========================================
-- 2. CATÁLOGOS BASE (Sin llaves foráneas)
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
    grasas_por_100g DECIMAL(5,2) NOT NULL,
    fdc_id INT NULL UNIQUE
);

-- ==========================================
-- 3. MÓDULO DE USUARIOS
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
-- 4. MÓDULO OPERATIVO (DIARIO)
-- ==========================================

CREATE TABLE Registros_Diarios (
    id_registro INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    fecha DATE NOT NULL,
    UNIQUE KEY (id_usuario, fecha),
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
    id_alimento INT NULL,
    cantidad_gramos DECIMAL(7,2) NOT NULL,
    nombre_ia VARCHAR(150) NULL,
    calorias_ia INT NULL,
    proteina_ia DECIMAL(5,2) NULL,
    carbs_ia DECIMAL(5,2) NULL,
    grasas_ia DECIMAL(5,2) NULL,
    FOREIGN KEY (id_comida) REFERENCES Comidas(id_comida) ON DELETE CASCADE,
    FOREIGN KEY (id_alimento) REFERENCES Alimentos(id_alimento)
);

-- ==========================================
-- 5. MÓDULO DE RECETAS
-- ==========================================

CREATE TABLE Recetas (
    id_receta INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(150) NOT NULL,
    imagen_url VARCHAR(255),
    tiempo_prep_min INT,
    permitir_ia_swap BOOLEAN DEFAULT TRUE,
    instrucciones TEXT,
    calorias_totales INT DEFAULT 0,
    proteina_total DECIMAL(5,2) DEFAULT 0,
    carbs_total DECIMAL(5,2) DEFAULT 0,
    grasas_total DECIMAL(5,2) DEFAULT 0,
    etiquetas JSON NULL
);

CREATE TABLE Ingredientes_Receta (
    id_ingrediente INT AUTO_INCREMENT PRIMARY KEY,
    id_receta INT NOT NULL,
    id_alimento INT NOT NULL,
    cantidad_gramos DECIMAL(7,2) NOT NULL,
    FOREIGN KEY (id_receta) REFERENCES Recetas(id_receta) ON DELETE CASCADE,
    FOREIGN KEY (id_alimento) REFERENCES Alimentos(id_alimento)
);

-- ==========================================
-- 6. INSERCIÓN DE DATOS
-- ==========================================

-- 6.1 NIVELES DE ACTIVIDAD
INSERT INTO Nivel_Actividad (descripcion, multiplicador_biometrico) VALUES 
('Sedentario', 1.2),
('Ligero', 1.375),
('Moderado', 1.55),
('Activo', 1.725),
('Muy Activo', 1.9);

-- 6.2 ALIMENTOS (35 registros)
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
(19, 'Manzana', 52, 0.3, 13.8, 0.2),
(20, 'Carne de Cerdo (Lomo)', 143, 21.0, 0.0, 6.0),
(21, 'Pechuga de Pavo', 114, 23.6, 0.0, 1.5),
(22, 'Papa Cocida', 87, 1.9, 20.1, 0.1),
(23, 'Camote Cocido', 90, 2.0, 21.0, 0.2),
(24, 'Quinoa Cocida', 120, 4.4, 21.3, 1.9),
(25, 'Lentejas Cocidas', 116, 9.0, 20.0, 0.4),
(26, 'Pan Integral (Rebanada)', 247, 13.0, 41.0, 3.4),
(27, 'Cebolla Blanca', 40, 1.1, 9.0, 0.1),
(28, 'Pimiento Morrón', 20, 0.9, 4.6, 0.2),
(29, 'Calabacita', 17, 1.2, 3.1, 0.3),
(30, 'Champiñones', 22, 3.1, 3.3, 0.3),
(31, 'Zanahoria', 41, 0.9, 9.6, 0.2),
(32, 'Lechuga', 15, 1.4, 2.9, 0.2),
(33, 'Queso Panela', 293, 17.0, 3.0, 23.0),
(34, 'Aceite de Oliva', 884, 0.0, 0.0, 100.0),
(35, 'Garbanzos Cocidos', 164, 8.9, 27.4, 2.6);

-- 6.3 RECETAS (49 registros)
INSERT INTO Recetas (id_receta, titulo, instrucciones, imagen_url, tiempo_prep_min, permitir_ia_swap, calorias_totales, proteina_total, carbs_total, grasas_total, etiquetas) VALUES 
(1, 'Tazón de Pollo, Arroz y Brócoli', '1. Cocer el arroz. 2. Asar la pechuga de pollo. 3. Hervir el brócoli por 5 mins. 4. Servir todo en un tazón.', 'https://images.unsplash.com/photo-1490645935967-10de6ba17061?w=600&h=400&fit=crop', 20, TRUE, 431, 39.3, 59.5, 4.3, '["Medio", "+Carbs"]'),
(2, 'Tacos de Asada', '1. Asar la carne de res sin aceite. 2. Calentar las tortillas. 3. Picar la carne y servir en las tortillas.', 'https://images.unsplash.com/photo-1490645935967-10de6ba17061?w=600&h=400&fit=crop', 15, TRUE, 468, 31.7, 45.0, 17.8, '["Medio", "+Carbs"]'),
(3, 'Avena Nocturna con Plátano', '1. Mezclar la avena con la leche en un frasco. 2. Refrigerar toda la noche. 3. Al día siguiente, agregar plátano en rodajas.', 'https://images.unsplash.com/photo-1490645935967-10de6ba17061?w=600&h=400&fit=crop', 5, TRUE, 421, 15.6, 75.2, 7.3, '["Economico", "Vegetariano", "+Carbs"]'),
(4, 'Huevos Revueltos con Espinaca', '1. Batir los huevos. 2. Sofreír la espinaca. 3. Agregar los huevos y cocinar al gusto.', 'https://images.unsplash.com/photo-1490645935967-10de6ba17061?w=600&h=400&fit=crop', 10, TRUE, 244, 20.9, 3.4, 16.7, '["Economico", "Keto", "+Proteína"]'),
(5, 'Ensalada de Atún', '1. Drenar el atún. 2. Picar el tomate y aguacate. 3. Mezclar todo en un tazón sin mayonesa.', 'https://images.unsplash.com/photo-1490645935967-10de6ba17061?w=600&h=400&fit=crop', 10, TRUE, 318, 39.0, 10.4, 15.7, '["Economico", "Keto", "+Proteína"]'),
(6, 'Salmón al Horno con Espárragos', '1. Precalentar horno a 200°C. 2. Colocar salmón y espárragos en charola. 3. Hornear por 15 minutos.', 'https://images.unsplash.com/photo-1490645935967-10de6ba17061?w=600&h=400&fit=crop', 20, TRUE, 332, 32.2, 3.9, 19.6, '["Costoso", "Keto", "+Proteína"]'),
(7, 'Pasta con Pechuga y Tomate', '1. Cocer la pasta. 2. Asar el pollo y picar el tomate. 3. Mezclar todo en un sartén por 2 mins.', 'https://images.unsplash.com/photo-1490645935967-10de6ba17061?w=600&h=400&fit=crop', 25, TRUE, 467, 30.6, 70.4, 4.6, '["Medio", "+Carbs"]'),
(8, 'Tazón Vegano de Tofu y Frijoles', '1. Cortar tofu en cubos y dorar en sartén. 2. Calentar frijoles. 3. Servir juntos.', 'https://images.unsplash.com/photo-1490645935967-10de6ba17061?w=600&h=400&fit=crop', 15, TRUE, 344, 32.6, 39.3, 13.5, '["Economico", "Vegano", "+Carbs"]'),
(9, 'Tostadas de Pollo', '1. Desmenuzar pollo cocido. 2. Colocar sobre ostadas. 3. Agregar aguacate y tomate.', 'https://images.unsplash.com/photo-1490645935967-10de6ba17061?w=600&h=400&fit=crop', 15, TRUE, 324, 25.4, 26.9, 10.1, '["Economico", "+Carbs"]'),
(10, 'Fajitas de Pollo', '1. Cortar pollo, cebolla y pimiento en tiras. 2. Sofreír todo junto. 3. Servir caliente.', 'https://images.unsplash.com/photo-1490645935967-10de6ba17061?w=600&h=400&fit=crop', 20, TRUE, 220, 35.0, 10.0, 4.5, '["Medio", "Keto", "+Proteína"]'),
(11, 'Lomo de Cerdo con Papa', '1. Hornear el lomo de cerdo. 2. Hervir las papas y zanahorias. 3. Servir.', 'https://images.unsplash.com/photo-1490645935967-10de6ba17061?w=600&h=400&fit=crop', 35, TRUE, 345, 34.0, 35.0, 9.0, '["Medio", "+Carbs"]'),
(12, 'Ensalada de Quinoa y Atún', '1. Mezclar quinoa, atún drenado y tomate picado. 2. Sazonar al gusto.', 'https://images.unsplash.com/photo-1490645935967-10de6ba17061?w=600&h=400&fit=crop', 10, TRUE, 310, 42.0, 33.0, 3.5, '["Medio", "+Proteína"]'),
(13, 'Sopa de Lentejas', '1. Hervir lentejas con cebolla y zanahoria picada. 2. Sazonar y servir.', 'https://images.unsplash.com/photo-1490645935967-10de6ba17061?w=600&h=400&fit=crop', 30, TRUE, 255, 19.0, 43.0, 1.0, '["Economico", "Vegetariano", "+Carbs"]'),
(14, 'Sándwich de Pavo y Panela', '1. Tostar el pan. 2. Armar con pavo, queso panela y rebanadas de tomate.', 'https://images.unsplash.com/photo-1490645935967-10de6ba17061?w=600&h=400&fit=crop', 5, TRUE, 320, 26.0, 42.0, 8.0, '["Medio", "+Carbs"]'),
(15, 'Huevos Rancheros Fit', '1. Freír huevos con mínimo aceite. 2. Servir sobre tortillas con salsa de tomate.', 'https://images.unsplash.com/photo-1490645935967-10de6ba17061?w=600&h=400&fit=crop', 15, TRUE, 310, 18.0, 25.0, 15.0, '["Economico", "Vegetariano", "+Carbs"]'),
(16, 'Bowl de Garbanzos y Brócoli', '1. Asar garbanzos y brócoli al horno con un toque de aceite. 2. Servir juntos.', 'https://images.unsplash.com/photo-1490645935967-10de6ba17061?w=600&h=400&fit=crop', 25, TRUE, 320, 15.0, 45.0, 8.0, '["Economico", "Vegano", "+Carbs"]'),
(17, 'Pollo a la Plancha con Camote', '1. Asar pechuga. 2. Cortar camote en cubos y hornear junto con espárragos.', 'https://images.unsplash.com/photo-1490645935967-10de6ba17061?w=600&h=400&fit=crop', 30, TRUE, 335, 36.0, 42.0, 4.0, '["Medio", "+Carbs"]'),
(18, 'Ensalada de Pollo', '1. Picar lechuga, pollo asado y queso panela. 2. Mezclar en un tazón.', 'https://images.unsplash.com/photo-1490645935967-10de6ba17061?w=600&h=400&fit=crop', 10, TRUE, 260, 38.0, 5.0, 10.0, '["Medio", "Keto", "+Proteína"]'),
(19, 'Pasta Boloñesa Fit', '1. Cocer pasta. 2. Sofreír carne de res molida con tomate picado. 3. Mezclar.', 'https://images.unsplash.com/photo-1490645935967-10de6ba17061?w=600&h=400&fit=crop', 25, TRUE, 450, 35.0, 48.0, 16.0, '["Medio", "+Carbs"]'),
(20, 'Pimientos Rellenos de Res', '1. Cortar pimientos a la mitad. 2. Rellenar con carne de res guisada y hornear.', 'https://images.unsplash.com/photo-1490645935967-10de6ba17061?w=600&h=400&fit=crop', 35, TRUE, 300, 30.0, 10.0, 15.0, '["Medio", "Keto", "+Proteína"]'),
(21, 'Tacos de Cerdo Encebollado', '1. Asar cerdo con cebolla. 2. Servir en tortillas de maíz.', 'https://images.unsplash.com/photo-1490645935967-10de6ba17061?w=600&h=400&fit=crop', 15, TRUE, 370, 28.0, 35.0, 10.0, '["Economico", "+Carbs"]'),
(22, 'Salmón con Quinoa y Calabacita', '1. Asar salmón y calabacita. 2. Servir sobre una cama de quinoa cocida.', 'https://images.unsplash.com/photo-1490645935967-10de6ba17061?w=600&h=400&fit=crop', 25, TRUE, 440, 36.0, 35.0, 20.0, '["Costoso", "+Proteína"]'),
(23, 'Omelet de Champiñones y Espinaca', '1. Batir huevos. 2. Rellenar con champiñones y espinaca sofritos.', 'https://images.unsplash.com/photo-1490645935967-10de6ba17061?w=600&h=400&fit=crop', 10, TRUE, 250, 20.0, 5.0, 16.0, '["Economico", "Vegetariano", "Keto", "+Proteína"]'),
(24, 'Tazón Vegano de Tofu y Quinoa', '1. Dorar tofu. 2. Mezclar con quinoa y brócoli cocido.', 'https://images.unsplash.com/photo-1490645935967-10de6ba17061?w=600&h=400&fit=crop', 20, TRUE, 370, 22.0, 35.0, 15.0, '["Medio", "Vegano", "+Carbs"]'),
(25, 'Hamburguesa de Pavo', '1. Formar carne de pavo. 2. Servir sobre una sola rebanada de pan con lechuga.', 'https://images.unsplash.com/photo-1490645935967-10de6ba17061?w=600&h=400&fit=crop', 15, TRUE, 280, 30.0, 25.0, 5.0, '["Medio", "+Proteína"]'),
(26, 'Pechuga Rellena de Espinaca', '1. Abrir pechuga, rellenar con espinaca y panela. 2. Hornear o asar tapado.', 'https://images.unsplash.com/photo-1490645935967-10de6ba17061?w=600&h=400&fit=crop', 25, TRUE, 290, 45.0, 2.0, 10.0, '["Medio", "Keto", "+Proteína"]'),
(27, 'Croquetas de Atún y Papa', '1. Hacer puré de papa, mezclar con atún y huevo. 2. Formar tortitas y asar.', 'https://images.unsplash.com/photo-1490645935967-10de6ba17061?w=600&h=400&fit=crop', 30, TRUE, 320, 30.0, 30.0, 8.0, '["Economico", "+Proteína"]'),
(28, 'Picadillo de Res con Verduras', '1. Sofreír carne de res. 2. Agregar papa y zanahoria en cubos con poca agua.', 'https://images.unsplash.com/photo-1490645935967-10de6ba17061?w=600&h=400&fit=crop', 30, TRUE, 410, 32.0, 32.0, 16.0, '["Medio", "+Carbs"]'),
(29, 'Ceviche Clásico de Atún', '1. Picar atún, tomate y cebolla. 2. Marinar en limón y servir.', 'https://images.unsplash.com/photo-1490645935967-10de6ba17061?w=600&h=400&fit=crop', 15, TRUE, 180, 38.0, 8.0, 1.0, '["Economico", "Keto", "+Proteína"]'),
(30, 'Calabacitas Rellenas de Panela', '1. Ahuecar calabacitas. 2. Rellenar con panela y tomate, asar al horno.', 'https://images.unsplash.com/photo-1490645935967-10de6ba17061?w=600&h=400&fit=crop', 25, TRUE, 170, 15.0, 10.0, 11.0, '["Economico", "Vegetariano", "Keto", "+Proteína"]'),
(31, 'Arroz Frito Saludable', '1. Usar arroz frío. 2. Sofreír con pollo en cubos y zanahoria.', 'https://images.unsplash.com/photo-1490645935967-10de6ba17061?w=600&h=400&fit=crop', 15, TRUE, 380, 26.0, 50.0, 4.0, '["Economico", "+Carbs"]'),
(32, 'Burrito Bowl', '1. Servir arroz, frijoles y carne de res asada en un tazón.', 'https://images.unsplash.com/photo-1490645935967-10de6ba17061?w=600&h=400&fit=crop', 15, TRUE, 450, 32.0, 50.0, 16.0, '["Medio", "+Carbs"]'),
(33, 'Ensalada de Pollo', '1. Mezclar lechuga, pollo asado y manzana en cubos.', 'https://images.unsplash.com/photo-1490645935967-10de6ba17061?w=600&h=400&fit=crop', 10, TRUE, 210, 34.0, 15.0, 3.0, '["Medio", "+Proteína"]'),
(34, 'Tostadas de Tofu Guisado', '1. Desmoronar tofu con tomate. 2. Servir sobre tostadas de maíz horneadas.', 'https://images.unsplash.com/photo-1490645935967-10de6ba17061?w=600&h=400&fit=crop', 15, TRUE, 260, 18.0, 30.0, 9.0, '["Economico", "Vegano", "+Carbs"]'),
(35, 'Bistec Encebollado', '1. Asar res con abundante cebolla blanca. 2. Servir jugoso.', 'https://images.unsplash.com/photo-1490645935967-10de6ba17061?w=600&h=400&fit=crop', 15, TRUE, 390, 39.0, 6.0, 22.0, '["Medio", "Keto", "+Proteína"]'),
(36, 'Puré de Camote con Cerdo', '1. Asar lomo de cerdo. 2. Servir con puré de camote y espárragos.', 'https://images.unsplash.com/photo-1490645935967-10de6ba17061?w=600&h=400&fit=crop', 30, TRUE, 330, 33.0, 35.0, 6.0, '["Medio", "+Carbs"]'),
(37, 'Sándwich de Huevo y Aguacate', '1. Cocer huevo duro o estrellado. 2. Servir en pan integral con aguacate.', 'https://images.unsplash.com/photo-1490645935967-10de6ba17061?w=600&h=400&fit=crop', 10, TRUE, 320, 18.0, 28.0, 15.0, '["Economico", "Vegetariano", "+Carbs"]'),
(38, 'Tacos de Salmón', '1. Asar salmón y desmenuzar. 2. Servir en tortillas de maíz.', 'https://images.unsplash.com/photo-1490645935967-10de6ba17061?w=600&h=400&fit=crop', 15, TRUE, 340, 23.0, 25.0, 14.0, '["Costoso", "+Carbs"]'),
(39, 'Albóndigas de Pavo con Arroz', '1. Formar albóndigas de pavo molido. 2. Cocer en caldo de tomate y servir con arroz.', 'https://images.unsplash.com/photo-1490645935967-10de6ba17061?w=600&h=400&fit=crop', 35, TRUE, 380, 28.0, 55.0, 3.0, '["Medio", "+Carbs"]'),
(40, 'Tazón de Lentejas y Huevo', '1. Calentar lentejas. 2. Coronar con huevo cocido o estrellado y espinaca.', 'https://images.unsplash.com/photo-1490645935967-10de6ba17061?w=600&h=400&fit=crop', 15, TRUE, 300, 23.0, 35.0, 6.0, '["Economico", "Vegetariano", "+Carbs"]'),
(41, 'Pasta Rápida con Pollo', '1. Cocer pasta. 2. Sofreír champiñones y pollo, mezclar todo.', 'https://images.unsplash.com/photo-1490645935967-10de6ba17061?w=600&h=400&fit=crop', 20, TRUE, 410, 37.0, 50.0, 4.0, '["Medio", "+Carbs"]'),
(42, 'Wraps de Lechuga con Cerdo', '1. Guisar cerdo con zanahoria rallada. 2. Usar hojas de lechuga como tortilla.', 'https://images.unsplash.com/photo-1490645935967-10de6ba17061?w=600&h=400&fit=crop', 15, TRUE, 220, 32.0, 6.0, 9.0, '["Medio", "Keto", "+Proteína"]'),
(43, 'Quesadillas de Champiñón', '1. Calentar tortillas. 2. Derretir panela con champiñones guisados dentro.', 'https://images.unsplash.com/photo-1490645935967-10de6ba17061?w=600&h=400&fit=crop', 10, TRUE, 330, 20.0, 30.0, 14.0, '["Economico", "Vegetariano", "+Carbs"]'),
(44, 'Pollo Campestre con Arroz', '1. Guisar pollo con cebolla y tomate. 2. Servir con guarnición de arroz.', 'https://images.unsplash.com/photo-1490645935967-10de6ba17061?w=600&h=400&fit=crop', 25, TRUE, 330, 35.0, 35.0, 4.0, '["Economico", "+Proteína"]'),
(45, 'Atún con Garbanzos', '1. Drenar atún y enjuagar garbanzos. 2. Mezclar en frío con tomate.', 'https://images.unsplash.com/photo-1490645935967-10de6ba17061?w=600&h=400&fit=crop', 5, TRUE, 310, 36.0, 30.0, 4.0, '["Economico", "+Proteína"]'),
(46, 'Salmón Empapelado', '1. Envolver salmón, calabacita y pimiento en aluminio. 2. Hornear por 20 mins.', 'https://images.unsplash.com/photo-1490645935967-10de6ba17061?w=600&h=400&fit=crop', 25, TRUE, 330, 31.0, 6.0, 20.0, '["Costoso", "Keto", "+Proteína"]'),
(47, 'Ensalada Caprese Fit', '1. Rebanar tomate y queso panela. 2. Alternar rebanadas con aceite de oliva.', 'https://images.unsplash.com/photo-1490645935967-10de6ba17061?w=600&h=400&fit=crop', 5, TRUE, 240, 18.0, 6.0, 16.0, '["Medio", "Vegetariano", "Keto", "+Proteína"]'),
(48, 'Tacos de Tofu Asado', '1. Dorar tofu en rebanadas delgadas. 2. Servir en tortilla con aguacate.', 'https://images.unsplash.com/photo-1490645935967-10de6ba17061?w=600&h=400&fit=crop', 15, TRUE, 340, 19.0, 28.0, 16.0, '["Economico", "Vegano", "+Carbs"]'),
(49, 'Pechuga Empanizada con Avena', '1. Pasar pechuga por huevo batido y cubrir con avena. 2. Hornear o freír en aire.', 'https://images.unsplash.com/photo-1490645935967-10de6ba17061?w=600&h=400&fit=crop', 25, TRUE, 330, 38.0, 25.0, 7.0, '["Medio", "+Proteína"]');

-- 6.4 INGREDIENTES POR RECETA
-- Recetas 1-9
INSERT INTO Ingredientes_Receta (id_receta, id_alimento, cantidad_gramos) VALUES 
(1, 1, 150), (1, 2, 150), (1, 3, 100),
(2, 7, 100), (2, 8, 100),
(3, 4, 50), (3, 5, 200), (3, 6, 100),
(4, 9, 120), (4, 10, 100),
(5, 11, 150), (5, 12, 100), (5, 13, 50),
(6, 14, 150), (6, 15, 100),
(7, 16, 150), (7, 1, 100), (7, 13, 50),
(8, 18, 150), (8, 17, 150),
(9, 1, 100), (9, 8, 50), (9, 12, 50), (9, 13, 50);

-- Recetas 10-19
INSERT INTO Ingredientes_Receta (id_receta, id_alimento, cantidad_gramos) VALUES 
(10, 1, 150), (10, 28, 100), (10, 27, 50),
(11, 20, 150), (11, 22, 100), (11, 31, 50),
(12, 24, 100), (12, 11, 150), (12, 13, 100),
(13, 25, 150), (13, 31, 50), (13, 27, 50),
(14, 26, 60), (14, 21, 100), (14, 33, 40),
(15, 9, 100), (15, 8, 50), (15, 13, 50),
(16, 35, 150), (16, 3, 100), (16, 34, 5),
(17, 1, 150), (17, 23, 100), (17, 15, 100),
(18, 1, 120), (18, 32, 100), (18, 33, 30),
(19, 16, 100), (19, 7, 120), (19, 13, 100);

-- Recetas 20-29
INSERT INTO Ingredientes_Receta (id_receta, id_alimento, cantidad_gramos) VALUES 
(20, 28, 150), (20, 7, 100), (20, 33, 30),
(21, 20, 120), (21, 8, 70), (21, 27, 50),
(22, 14, 150), (22, 24, 100), (22, 29, 100),
(23, 9, 120), (23, 30, 100), (23, 10, 50),
(24, 18, 120), (24, 24, 100), (24, 3, 100),
(25, 21, 120), (25, 26, 40), (25, 32, 50),
(26, 1, 150), (26, 10, 50), (26, 33, 30),
(27, 11, 100), (27, 22, 100), (27, 9, 50),
(28, 7, 120), (28, 22, 100), (28, 31, 50),
(29, 11, 150), (29, 13, 100), (29, 27, 50);

-- Recetas 30-39
INSERT INTO Ingredientes_Receta (id_receta, id_alimento, cantidad_gramos) VALUES 
(30, 29, 150), (30, 33, 50), (30, 13, 50),
(31, 2, 120), (31, 1, 100), (31, 31, 50),
(32, 2, 100), (32, 17, 100), (32, 7, 100),
(33, 1, 150), (33, 32, 100), (33, 19, 100),
(34, 18, 120), (34, 13, 100), (34, 8, 50),
(35, 7, 150), (35, 27, 100), (35, 34, 5),
(36, 20, 150), (36, 23, 100), (36, 15, 100),
(37, 9, 100), (37, 26, 60), (37, 12, 50),
(38, 14, 100), (38, 8, 70), (38, 32, 50),
(39, 21, 120), (39, 2, 80), (39, 13, 100);

-- Recetas 40-49
INSERT INTO Ingredientes_Receta (id_receta, id_alimento, cantidad_gramos) VALUES 
(40, 25, 150), (40, 9, 50), (40, 10, 50),
(41, 16, 120), (41, 1, 120), (41, 30, 100),
(42, 20, 150), (42, 32, 100), (42, 31, 50),
(43, 8, 70), (43, 33, 50), (43, 30, 100),
(44, 1, 150), (44, 2, 100), (44, 27, 50),
(45, 11, 100), (45, 35, 100), (45, 13, 100),
(46, 14, 150), (46, 29, 100), (46, 28, 50),
(47, 13, 150), (47, 33, 60), (47, 34, 5),
(48, 18, 120), (48, 8, 50), (48, 12, 50),
(49, 1, 150), (49, 4, 30), (49, 9, 30);

-- user prueba

