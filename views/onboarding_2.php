<?php
// 1. Reanudamos la sesión
session_start();

// Si no hay sesión activa, lo regresamos al registro
if (!isset($_SESSION['usuario_id'])) {
    header("Location: registro.html");
    exit();
}

// 2. Atrapar el dato de la pantalla anterior y guardarlo en la sesión
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['objetivo'])) {
    $_SESSION['onboarding_objetivo'] = $_POST['objetivo'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>NutrIAssist - Sobre ti</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/global.css">
    
    <style>
        .progress-bar { width: 100%; height: 6px; background-color: var(--color-border); border-radius: 4px; margin-bottom: 2rem; overflow: hidden; }
        
        /* Barra al 66% (Paso 2 de 3) */
        .progress-fill { width: 66.66%; height: 100%; background-color: var(--color-primary); border-radius: 4px; transition: width 0.3s ease; }

        .header { text-align: center; margin-bottom: 2.5rem; }
        
        .sticky-footer { margin-top: auto; padding-top: 2rem; }

        /* Ajuste sutil para que el menú desplegable se vea igual que los inputs de texto */
        select.form-input {
            background-color: var(--color-bg);
            cursor: pointer;
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="%236B7280" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>');
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 1.2em;
        }
    </style>
</head>
<body>

    <div class="mobile-container">
        <div class="progress-bar">
            <div class="progress-fill"></div>
        </div>

        <div class="header">
            <h1>Sobre ti</h1>
            <p>Esta información es vital para calcular tus requerimientos calóricos con exactitud.</p>
        </div>

        <form action="onboarding_3.php" method="POST" style="display: flex; flex-direction: column; flex-grow: 1;">
            
            <label class="form-label" for="sexo">Sexo biológico</label>
            <select class="form-input" id="sexo" name="sexo" required>
                <option value="" disabled selected>Selecciona una opción</option>
                <option value="M">Masculino</option>
                <option value="F">Femenino</option>
            </select>

            <label class="form-label" for="edad">Edad (años)</label>
            <input class="form-input" type="number" id="edad" name="edad" placeholder="Ej. 20" min="15" max="100" required>

            <label class="form-label" for="altura">Altura (cm)</label>
            <input class="form-input" type="number" id="altura" name="altura" placeholder="Ej. 175" min="100" max="250" required>

            <label class="form-label" for="peso">Peso actual (kg)</label>
            <input class="form-input" type="number" id="peso" name="peso" placeholder="Ej. 70" min="30" max="300" step="0.1" required>

            <div class="sticky-footer">
                <button type="submit" class="btn-primary">Continuar</button>
            </div>
        </form>
    </div>

</body>
</html>