# NutrIAssist
Repositorio proyecto Tecnologías Emergentes para el Desarrollo de Soluciones. (NutrIAssist).

/nutriassist
 │
 ├── /assets               <-- (Lo que ve el cliente)
 │   ├── /css              <-- (Tus estilos)
 │   ├── /js               <-- (Tus scripts: app.js, fetch_gemma.js)
 │   └── /img              <-- (Tu logo del Aguacate Inteligente, íconos SVG)
 │
 ├── /config               <-- (Configuraciones críticas)
 │   └── conexion.php      <-- (Tus credenciales de MySQL)
 │
 ├── /controllers          <-- (El cerebro del Backend)
 │   ├── auth.php          <-- (Lógica de login/registro)
 │   └── gemma_api.php     <-- (Donde ocultarás tu API Key y harás la petición)
 │
 ├── /views                <-- (Tus pantallas HTML/PHP)
 │   ├── login.php
 │   ├── dashboard.php
 │   └── chat_ia.php
 │
 └── index.php             <-- (El punto de entrada, redirig al login o dashboard)