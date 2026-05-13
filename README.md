# NutrIAssist

Asistente nutricional inteligente potenciado por inteligencia artificial generativa mediante Google Gemini. Proyecto desarrollado para la asignatura de Tecnologías Emergentes para el Desarrollo de Soluciones.

---

## Descripción

NutrIAssist es una Aplicación Web Progresiva (PWA) de nutrición personal que integra IA generativa con una base de datos relacional para ofrecer las siguientes funcionalidades:

- Registro de alimentos mediante voz o texto a través de un asistente virtual.
- Diario nutricional con navegación cronológica y visualización de registros.
- Seguimiento detallado de macronutrientes: calorías, proteínas, carbohidratos y grasas.
- Catálogo de recetas saludables con sistema de sustitución inteligente de ingredientes.
- Panel de control (Dashboard) con métricas de consumo diario en tiempo real.
- Gestión de perfil de usuario con objetivos biométricos personalizados.
- Compatibilidad multiplataforma e instalación como aplicación nativa (PWA).

---

## Stack Tecnológico

| Capa | Tecnología |
|---|---|
| Frontend | HTML5, CSS3 (Vanilla), JavaScript (ES6+) |
| Backend | PHP 8.x |
| Base de Datos | MySQL 8.x (vía PDO) |
| Inteligencia Artificial (Chat) | Google Gemini API (modelo gemini-2.0-flash) |
| Inteligencia Artificial (Voz) | Google Gemini API (Text-to-Speech) |
| Información Nutricional | USDA FoodData Central API |
| Entorno de Desarrollo | XAMPP (Apache + MySQL) |
| PWA | Web App Manifest y Service Worker |

---

## Arquitectura de Datos (Decisiones de Diseño Senior)
Durante la evolución del sistema, se tomaron decisiones conscientes de **desnormalización intencionada** para optimizar el rendimiento y la integración con Inteligencia Artificial, soportando exitosamente auditorías de normalización:
1. **Patrón Document-Relational (JSON):** Se utilizan campos JSON (ej. `etiquetas` en `Recetas`) para metadatos volátiles, evitando uniones (`JOINs`) innecesarias para datos que no requieren integridad referencial estricta.
2. **Materialized View Logic (Triggers):** Se almacenan los macronutrientes totales pre-calculados en la tabla `Recetas` (violación intencionada de 3FN para optimizar lectura masiva). Para proteger la integridad, el sistema utiliza **TRIGGERS SQL** (`tr_actualizar_macros_*`) que recalculan los totales automáticamente ante cualquier cambio en los ingredientes de una receta.
3. **Restricción XOR Estricta:** La convivencia entre alimentos del catálogo (IDs) y alimentos detectados al vuelo por la IA (Textos libres) en el diario de consumo está asegurada a nivel motor SQL mediante un `CHECK CONSTRAINT`. Esto garantiza que un registro proviene exclusivamente de la IA o del Catálogo, previniendo redundancia.

---

## Estructura de Directorios

```
nutriassist/
│
├── index.php                      # Punto de entrada y enrutamiento inicial
├── manifest.json                  # Configuración de la PWA (identidad y visual)
├── sw.js                          # Service Worker para gestión de caché y modo offline
├── SQlBD.sql                      # Script de inicialización de la base de datos
├── .gitignore                     # Configuración de exclusión para Git
│
├── api/                           # Endpoints de servicios y lógica de IA
│   ├── api_alimentos.php          # Integración con USDA y normalización de nombres
│   ├── gamma_api.php              # Integración directa con Google Gemini API
│   ├── guardar_comida_ia.php      # Persistencia de datos interpretados por la IA
│   ├── guardar_comida_manual.php  # Registro de entradas manuales del usuario
│   ├── guardar_comida_lista.php   # Registro desde el buscador de alimentos
│   ├── ia_swap.php                # Lógica de sustitución de ingredientes mediante IA
│   └── reporte_semanal_api.php    # Generación de reportes de progreso
│
├── config/                        # Archivos de configuración del sistema
│   ├── conexion.php               # Configuración de la conexión PDO a MySQL
│   └── keys.php                   # Definición de API Keys y constantes de modelos
│
├── controllers/                   # Lógica de flujo y manejo de peticiones
│   ├── chat_ia_controller.php     # Controlador para la vista del asistente
│   ├── dashboard_controller.php   # Lógica del panel principal y métricas
│   ├── diario_controller.php      # Gestión de la vista del diario nutricional
│   ├── perfil_controller.php      # Carga de datos del perfil de usuario
│   ├── recetas_controller.php     # Gestión del catálogo de recetas
│   ├── receta_detalle_controller.php # Controlador para la vista detalle
│   ├── procesar_login.php         # Flujo de autenticación
│   ├── procesar_registro.php      # Flujo de creación de cuentas
│   ├── procesar_onboarding.php    # Flujo de datos biométricos iniciales
│   ├── procesar_perfil.php        # Flujo de actualización de perfil
│   └── logout.php                 # Cierre de sesión
│
├── models/                        # Clases de datos (Entidades del sistema)
│   ├── Usuario.php                # Lógica de persistencia de usuarios
│   ├── Alimento.php               # Representación de items nutricionales
│   ├── Comida.php                 # Entidad de registros de consumo
│   ├── Diario.php                 # Lógica de agrupación diaria
│   └── Receta.php                 # Estructura de preparaciones y tags
│
├── views/                         # Capa de presentación (UI/UX)
│   ├── login.html                 # Pantalla de acceso
│   ├── registro.html              # Pantalla de registro
│   ├── onboarding_1..3.php        # Proceso de configuración inicial
│   ├── dashboard.php              # Dashboard principal
│   ├── chat_ia.php                # Interfaz del asistente IA
│   ├── diario.php                 # Registro histórico
│   ├── recetas.php                # Buscador de recetas
│   ├── receta_detalle.php         # Detalle y sustitución IA
│   ├── perfil.php                 # Perfil y preferencias
│   └── includes/                  # Fragmentos reutilizables (Header, Footer)
│
├── utils/                         # Utilidades y helpers transversales
│   └── Auth.php                   # Sistema centralizado de sesiones y seguridad
│
└── assets/                        # Recursos estáticos
    ├── css/                       # Estilos (Modo Oscuro, Dashboard, etc.)
    ├── js/                        # Lógica de cliente y Service Worker helper
    └── img/                       # Logos, iconos PWA y fotos de recetas
```

---

## Instalación y Configuración

### Requisitos del Sistema
- XAMPP v8.0 o superior (Apache y MySQL activos).
- Clave de API de Google AI Studio (Gemini).
- Conexión a internet para el funcionamiento de los servicios de IA.

### Procedimiento de Instalación

1.  **Clonación del Repositorio**
    ```bash
    git clone https://github.com/danielpulid0/NutrIAssist.git
    # El proyecto debe ubicarse en el directorio htdocs de su instalación de XAMPP.
    ```

2.  **Configuración de la Base de Datos**
    - Acceda a phpMyAdmin (http://localhost/phpmyadmin).
    - Cree una base de datos nueva.
    - Importe el archivo `SQlBD.sql` para generar las tablas y datos iniciales.

3.  **Configuración de Credenciales de Seguridad**
    Debe crear el archivo `config/keys.php` manualmente, ya que por motivos de seguridad no se incluye en el repositorio. La estructura recomendada es:

    ```php
    <?php
    define('GEMINI_API_KEY',    'SU_CLAVE_AQUI');
    define('GEMINI_MODELO_CHAT','gemini-2.0-flash');
    define('GEMINI_MODELO_TTS', 'gemini-2.5-flash-preview-tts');
    define('USDA_API_KEY',      'DEMO_KEY'); // Opcional: Clave del USDA
    ```

    Verifique en `config/conexion.php` que las credenciales de acceso a MySQL correspondan a su entorno local.

4.  **Acceso a la Aplicación**
    Inicie el servidor Apache y MySQL desde el panel de XAMPP y navegue a: `http://localhost/nutriassist`

---

## Base de Datos

El sistema utiliza una base de datos relacional con integridad referencial. Las entidades principales incluyen:

- **Usuarios**: Almacena credenciales y perfiles biométricos.
- **Registros_Diarios**: Centraliza la actividad de consumo por fecha.
- **Alimentos_Consumidos**: Registra los ingredientes específicos mediante el patrón de Snapshot, permitiendo guardar datos calculados por la IA de forma persistente.
- **Recetas**: Catálogo de preparaciones con información nutricional y etiquetas de filtrado.
- **Usuario_Restriccion**: Gestión de intolerancias médicas y preferencias dietéticas.

---

## Seguridad y Variables de Entorno

El archivo `config/keys.php` está incluido en el `.gitignore` para prevenir la exposición de claves privadas en el repositorio público. Es responsabilidad del administrador del sistema mantener estas claves protegidas.

---

## Módulos del Sistema

### Asistente con Inteligencia Artificial
Proporciona una interfaz conversacional procesada mediante Google Gemini que identifica alimentos, calcula porciones y extrae información nutricional de forma automática a partir de lenguaje natural. Incluye funcionalidades de Text-to-Speech para mejorar la accesibilidad.

### Diario y Metas nutricionales
Permite al usuario visualizar su progreso diario frente a sus metas calculadas. El diario incluye un selector de fechas dinámico con soporte para gestos táctiles.

### Gestión de Recetas y Sustitución de Ingredientes
El sistema ofrece alternativas saludables a ingredientes comunes de una receta basándose en el historial médico del usuario y la similitud nutricional, utilizando modelos de IA para generar las sugerencias.

---

## Equipo de Desarrollo
Proyecto académico desarrollado para el curso de Tecnologías Emergentes.

---

## Licencia
Este proyecto es para uso estrictamente académico. Todos los derechos reservados © 2026.