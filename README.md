# NutrIAssist

> Asistente nutricional inteligente potenciado por IA generativa (Google Gemini).  
> Proyecto para la materia **Tecnologías Emergentes para el Desarrollo de Soluciones**.

---

## 📋 Descripción

NutrIAssist es una **Progressive Web App (PWA)** de nutrición personal que combina IA generativa con una base de datos relacional para ofrecer:

- Registro de comidas por voz o texto mediante chat con IA
- Diario alimenticio con navegación por fechas
- Seguimiento de macronutrientes (calorías, proteína, carbohidratos, grasas)
- Catálogo de recetas saludables con sustitución inteligente de ingredientes
- Dashboard con métricas diarias en tiempo real
- Perfil de usuario con metas personalizadas
- Instalable como app nativa (PWA) en móvil y escritorio

---

## Stack Tecnológico

| Capa | Tecnología |
|---|---|
| **Frontend** | HTML5, CSS3 (Vanilla), JavaScript (ES6+) |
| **Backend** | PHP 8.x |
| **Base de datos** | MySQL 8.x (vía PDO) |
| **IA — Chat** | Google Gemini API (`gemini-2.0-flash`) |
| **IA — TTS** | Google Gemini API (Text-to-Speech) |
| **Catálogo nutricional** | USDA FoodData Central API |
| **Servidor local** | XAMPP (Apache + MySQL) |
| **PWA** | Web App Manifest + Service Worker |

---

## Estructura de Carpetas

```
nutriassist/
│
├── index.php                      ← Punto de entrada: redirige a login o dashboard
├── manifest.json                  ← Configuración PWA (nombre, íconos, colores)
├── sw.js                          ← Service Worker (caché híbrida offline-first)
├── SQlBD.sql                      ← Script completo de la base de datos
├── .gitignore                     ← Excluye config/keys.php y archivos sensibles
│
├── config/                        ← Configuraciones críticas (NO versionar keys.php)
│   ├── conexion.php               ← Instancia PDO de MySQL
│   └── keys.php                   ← API Keys (Gemini, USDA) — NO subir a GitHub
│
├── controllers/                   ← Lógica de negocio (Backend PHP)
│   ├── auth.php                   ← Middleware de autenticación de sesión
│   ├── procesar_login.php         ← Valida credenciales y abre sesión
│   ├── procesar_registro.php      ← Crea cuenta de nuevo usuario
│   ├── procesar_onboarding.php    ← Guarda perfil biométrico inicial
│   ├── logout.php                 ← Destruye la sesión
│   │
│   ├── dashboard_controller.php   ← Calcula macros del día para el dashboard
│   ├── diario_controller.php      ← Obtiene registros de comidas por fecha
│   ├── chat_ia_controller.php     ← Proxy entre el frontend y gamma_api.php
│   ├── gamma_api.php              ← Construcción de prompt + llamada a Gemini Chat
│   ├── gemini_tts.php             ← Llamada a Gemini TTS para respuesta en audio
│   │
│   ├── guardar_comida_ia.php      ← Persiste alimentos detectados por la IA (Snapshot)
│   ├── guardar_comida_manual.php  ← Persiste alimentos ingresados manualmente
│   ├── guardar_comida_lista.php   ← Persiste alimentos seleccionados de un listado
│   │
│   ├── api_alimentos.php          ← Búsqueda FULLTEXT local + caché desde USDA API
│   ├── ia_swap.php                ← Sustitución inteligente de ingredientes (Gemini)
│   │
│   ├── recetas_controller.php     ← Consulta y filtra recetas desde la DB
│   ├── receta_detalle_controller.php ← Datos completos de una receta + ingredientes
│   ├── perfil_controller.php      ← Lee y actualiza el perfil del usuario
│   └── procesar_perfil.php        ← Procesa formulario de edición de perfil
│
├── views/                         ← Pantallas visibles al usuario (HTML/PHP)
│   ├── login.html                 ← Pantalla de inicio de sesión
│   ├── registro.html              ← Formulario de registro de cuenta
│   ├── onboarding_1.php           ← Paso 1: datos personales
│   ├── onboarding_2.php           ← Paso 2: nivel de actividad y restricciones
│   ├── onboarding_3.php           ← Paso 3: meta principal
│   ├── dashboard.php              ← Pantalla principal con resumen del día
│   ├── chat_ia.php                ← Asistente de IA con chat y TTS
│   ├── diario.php                 ← Diario de comidas con calendario semanal
│   ├── recetas.php                ← Catálogo de recetas con búsqueda y filtros
│   ├── receta_detalle.php         ← Detalle de receta con swap de ingredientes
│   ├── perfil.php                 ← Perfil del usuario y configuración
│   └── includes/
│       ├── footer.php             ← Barra de navegación inferior (compartida)
│       └── header.php             ← Meta tags y estilos comunes (compartidos)
│
└── assets/                        ← Recursos estáticos públicos
    ├── css/
    │   ├── global.css             ← Design tokens, variables CSS y componentes base
    │   ├── dashboard.css          ← Estilos de la pantalla principal
    │   ├── chat_ia.css            ← Estilos del asistente IA y burbujas de chat
    │   ├── diario.css             ← Estilos del diario y calendario semanal
    │   ├── recetas.css            ← Estilos del grid de recetas
    │   ├── receta_detalle.css     ← Estilos del detalle de receta y modal swap
    │   └── perfil.css             ← Estilos de la pantalla de perfil
    ├── js/
    │   ├── chat_ia.js             ← Lógica del chat: streaming, TTS, historial
    │   ├── diario.js              ← Calendario semanal, swipe, modales y fetch
    │   ├── receta_detalle.js      ← Checkboxes, swap IA y registro al diario
    │   └── perfil.js              ← Lógica del formulario de perfil
    └── img/
        ├── icons/                 ← Íconos PWA (192x192, 512x512)
        └── recetas/               ← Imágenes de las recetas (jpg/png)
```

---

## Instalación local

### Requisitos previos
- [XAMPP](https://www.apachefriends.org/) (PHP 8.x + Apache + MySQL)
- Cuenta en [Google AI Studio](https://aistudio.google.com/) para obtener la API Key de Gemini
- (Opcional) API Key de [USDA FoodData Central](https://fdc.nal.usda.gov/api-guide.html)

### Pasos

**1. Clonar el repositorio**
```bash
git clone https://github.com/danielpulid0/NutrIAssist.git
# Mover la carpeta a htdocs de XAMPP
# Windows: C:\xampp\htdocs\nutriassist
```

**2. Crear la base de datos**
1. Iniciar XAMPP (Apache + MySQL)
2. Abrir phpMyAdmin: `http://localhost/phpmyadmin`
3. Crear y ejecutar el script `SQlBD.sql`

**3. Configurar credenciales**

Crear el archivo `config/keys.php` (este archivo **no se sube a GitHub**):

```php
<?php
// config/keys.php
define('GEMINI_API_KEY',    'AIzaSy...');          // Tu API Key de Google Gemini
define('GEMINI_MODELO_CHAT','gemini-2.0-flash');   // Modelo para chat e IA Swap
define('GEMINI_MODELO_TTS', 'gemini-2.5-flash-preview-tts'); // Modelo para voz
define('USDA_API_KEY',      'DEMO_KEY');            // O tu clave real de USDA
```

Verificar que `config/conexion.php` tenga las credenciales correctas de MySQL:

```php
$host = 'localhost';
$db   = 'nutriassist_db';
$user = 'root';   // Usuario de XAMPP por defecto
$pass = '';       // Contraseña vacía en XAMPP por defecto
```

**4. Acceder a la aplicación**

Abrir en el navegador: `http://localhost/nutriassist`

---

## Base de datos

El archivo `SQlBD.sql` contiene el esquema completo. Tablas principales:

| Tabla | Descripción |
|---|---|
| `Usuarios` | Datos de cuenta y biométricos |
| `Nivel_Actividad` | Catálogo de niveles (sedentario, activo, etc.) |
| `Restricciones_Medicas` | Catálogo de intolerancias y dietas |
| `Usuario_Restriccion` | Relación N:M usuario ↔ restricciones |
| `Registros_Diarios` | Un registro por usuario por día |
| `Comidas` | Agrupador de tipo de comida (Desayuno/Comida/Cena/Snack) |
| `Alimentos_Consumidos` | Alimentos registrados (soporta datos de IA y catálogo) |
| `Alimentos` | Catálogo nutricional base (calorías/100g) |
| `Recetas` | Recetas con macros cacheados y etiquetas JSON |
| `Ingredientes_Receta` | Ingredientes con cantidades por receta |

### Patrón Snapshot (IA)
`Alimentos_Consumidos` acepta tanto alimentos del catálogo (`id_alimento`) como datos inferidos por la IA (`nombre_ia`, `calorias_ia`, `proteina_ia`, `carbs_ia`, `grasas_ia`). El campo `id_alimento` permite `NULL` para entradas de IA pura.

---

## Variables de entorno / Configuración sensible

| Variable | Archivo | Descripción |
|---|---|---|
| `GEMINI_API_KEY` | `config/keys.php` | API Key de Google Gemini |
| `GEMINI_MODELO_CHAT` | `config/keys.php` | Modelo de chat (gemini-2.0-flash) |
| `GEMINI_MODELO_TTS` | `config/keys.php` | Modelo de voz |
| `USDA_API_KEY` | `config/keys.php` | API Key de USDA (opcional, usa DEMO_KEY si no se tiene) |

> ⚠️ `config/keys.php` está en `.gitignore` y **nunca debe subirse al repositorio**.

---

## Módulos y funcionalidades

### Chat con IA
- Comunicación en tiempo real con Google Gemini
- Contexto personalizado: edad, peso, metas, restricciones médicas
- Detección automática de alimentos en el mensaje del usuario
- Botón de confirmación para guardar macros en la base de datos
- Texto a voz (TTS) para las respuestas del asistente
- Historial de conversación persistente en sesión

### Diario de Comidas
- Calendario semanal deslizable (swipe táctil + trackpad)
- Navegación a meses anteriores con flecha
- Cards expandibles por tipo de comida (Desayuno / Comida / Cena / Snack)
- Registro manual de alimentos con modal de entrada
- Datos en tiempo real desde la base de datos

### Catálogo de Recetas
- Grid de 2 columnas con imagen, calorías, tiempo y costo
- Búsqueda por nombre y filtros por etiqueta
- Detalle de receta con instrucciones numeradas paso a paso
- Checkboxes interactivos por ingrediente
- **Swap inteligente de ingredientes** con Gemini (2-3 opciones con % similitud)
- Registro directo de la receta al diario

###  Anti-duplicados en catálogo
Tres capas de defensa contra entradas duplicadas en la tabla `Alimentos`:
1. **Canonicalización IA**: Gemini normaliza el nombre antes de buscar
2. **FULLTEXT Search**: MySQL ignora stop words ("de", "el", "la")
3. **fdc_id UNIQUE**: La clave externa de USDA evita duplicados matemáticamente

###  PWA
- Instalable en Android, iOS y escritorio
- Caché offline con Service Worker (estrategia híbrida)
- Manifest con íconos, tema verde y pantalla de splash

---

##  Equipo

Proyecto desarrollado como parte del curso de **Tecnologías Emergentes para el Desarrollo de Soluciones**.

---

##  Licencia

Uso académico. Todos los derechos reservados © 2026.