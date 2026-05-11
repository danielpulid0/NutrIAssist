<?php
$page_title = 'NutrIAssist - Términos y Condiciones';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/global.css?v=3">
    <link rel="stylesheet" href="../assets/css/terminos.css">
</head>
<body>
    <div class="mobile-container">
        <div class="terms-body">
            <a href="javascript:history.back()" class="back-link">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                Volver
            </a>

            <div class="terms-header">
                <h1>Términos y Condiciones</h1>
                <p>Última actualización: Mayo 2026</p>
            </div>

            <div class="terms-section">
                <h3>1. Descargo de Responsabilidad Médica</h3>
                <p>NutrIAssist es una herramienta informativa basada en inteligencia artificial. <strong>No es un dispositivo médico ni sustituye el consejo, diagnóstico o tratamiento de un profesional de la salud.</strong></p>
                <p>Consulta siempre con un médico o nutricionista colegiado antes de realizar cambios significativos en tu dieta o rutina de ejercicio, especialmente si tienes condiciones médicas preexistentes.</p>
            </div>

            <div class="terms-section">
                <h3>2. Uso de Inteligencia Artificial</h3>
                <p>Nuestras recomendaciones de comidas y análisis nutricionales son generados mediante modelos de IA. Aunque nos esforzamos por la precisión, la tecnología puede cometer errores o proporcionar datos inexactos. El usuario asume la responsabilidad de verificar la idoneidad de las sugerencias.</p>
            </div>

            <div class="terms-section">
                <h3>3. Privacidad y Datos Biométricos</h3>
                <p>Para funcionar correctamente, la aplicación procesa datos como tu peso, edad, sexo y nivel de actividad. Estos datos se utilizan exclusivamente para personalizar tu experiencia y no son compartidos con terceros con fines comerciales.</p>
            </div>

            <div class="terms-section">
                <h3>4. Limitación de Responsabilidad</h3>
                <p>NutrIAssist no se hace responsable de reacciones alérgicas, efectos secundarios o cualquier problema de salud derivado del seguimiento de las sugerencias de la aplicación.</p>
            </div>

            <div class="terms-section">
                <h3>5. Aceptación de Términos</h3>
                <p>Al utilizar esta aplicación, declaras haber leído y aceptado estos términos en su totalidad.</p>
            </div>

            <div class="terms-footer-btn-container">
                <button onclick="history.back()" class="btn-primary">He leído y acepto</button>
            </div>
        </div>
    </div>
    <script src="../assets/js/theme.js"></script>
</body>
</html>
