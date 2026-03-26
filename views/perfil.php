<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.html");
    exit();
}
require_once '../config/conexion.php';

$user_id = $_SESSION['usuario_id'];

// 1. MIGRACIÓN SILENCIOSA (Asegurar que las columnas existan en la base de datos de la fase actual)
try {
    $conn->query("SELECT sexo FROM Usuarios LIMIT 1");
} catch (PDOException $e) {
    $conn->exec("ALTER TABLE Usuarios ADD COLUMN sexo ENUM('Hombre', 'Mujer') DEFAULT 'Hombre'");
    $conn->exec("ALTER TABLE Usuarios ADD COLUMN meta_principal ENUM('Perder Grasa', 'Ganar Músculo', 'Mantener Peso') DEFAULT 'Perder Grasa'");
}

// 2. OBTENER DATOS DEL USUARIO
$stmt = $conn->prepare("SELECT * FROM Usuarios WHERE id_usuario = :id");
$stmt->bindParam(':id', $user_id);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$edad = 24; // Default
if (!empty($user['fecha_nacimiento']) && $user['fecha_nacimiento'] != '0000-00-00') {
    $nacimiento = new DateTime($user['fecha_nacimiento']);
    $hoy = new DateTime();
    $edad = $hoy->diff($nacimiento)->y;
}

$peso = (float)$user['peso_kg'] > 0 ? (float)$user['peso_kg'] : 75;
$altura = (int)$user['altura_cm'] > 0 ? (int)$user['altura_cm'] : 178;
$sexo = $user['sexo'] ?? 'Hombre';
$meta = $user['meta_principal'] ?? 'Perder Grasa';
$actividad = $user['id_nivel_actividad'] ?? 2; // Supongamos 2 = Moderado

// 3. OBTENER RESTRICCIONES
$stmt_rest = $conn->prepare("
    SELECT r.id_restriccion, r.nombre 
    FROM Restricciones_Medicas r
    JOIN Usuario_Restriccion ur ON r.id_restriccion = ur.id_restriccion
    WHERE ur.id_usuario = :id
");
$stmt_rest->bindParam(':id', $user_id);
$stmt_rest->execute();
$restricciones = $stmt_rest->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>NutrIAssist - Mi Perfil</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/global.css">
    <style>
        .mobile-container { padding: 0; background-color: var(--color-bg); padding-bottom: 90px; }
        
        .header-top {
            text-align: center;
            padding: 1.25rem;
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--color-text-dark);
            border-bottom: 1px solid var(--color-border);
            position: sticky;
            top: 0;
            background-color: var(--color-bg);
            z-index: 50;
        }

        .perfil-body { padding: 1.5rem; }

        .section-title {
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--color-text-dark);
            margin-bottom: 1rem;
            margin-top: 1.5rem;
        }
        .section-title:first-child { margin-top: 0; }

        /* GRID BIOMETRICO */
        .bio-card {
            background-color: var(--color-bg-light);
            border: 1px solid var(--color-mint);
            border-radius: 12px;
            padding: 1.25rem;
        }
        
        .bio-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .input-group { display: flex; flex-direction: column; }
        .input-label { font-size: 0.75rem; color: var(--color-text-gray); margin-bottom: 0.3rem; }
        
        .input-box {
            display: flex;
            align-items: center;
            background-color: var(--color-bg);
            border: 1px solid var(--color-border);
            border-radius: 8px;
            padding: 0 0.75rem;
            height: 44px;
        }
        .input-box input, .input-box select {
            border: none;
            background: transparent;
            width: 100%;
            height: 100%;
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--color-text-dark);
            outline: none;
        }
        .input-box span { font-size: 0.75rem; color: var(--color-text-gray); margin-left: 0.5rem; pointer-events: none; }
        
        select { appearance: none; }
        
        .select-wrapper { position: relative; width: 100%; height: 100%; }
        .select-wrapper select { width: 100%; padding-right: 1.5rem; }
        .select-wrapper::after {
            content: '▼';
            font-size: 0.6rem;
            color: var(--color-text-gray);
            position: absolute;
            right: 0;
            top: 50%;
            transform: translateY(-50%);
            pointer-events: none;
        }

        /* METAS GLOBALES */
        .meta-card {
            display: flex;
            align-items: center;
            padding: 1rem;
            border: 1px solid var(--color-border);
            border-radius: 12px;
            margin-bottom: 0.75rem;
            cursor: pointer;
            transition: all 0.2s;
        }
        .meta-card.active {
            background-color: var(--color-mint);
            border-color: var(--color-malachite);
        }

        .meta-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--color-bg-app);
            display: flex;
            justify-content: center;
            align-items: center;
            margin-right: 1rem;
            color: var(--color-text-gray);
        }
        .meta-card.active .meta-icon {
            background-color: #86efac;
            color: var(--color-malachite);
        }

        .meta-info { flex-grow: 1; }
        .meta-info h4 { font-size: 0.95rem; font-weight: 600; color: var(--color-text-dark); margin-bottom: 0.1rem; }
        .meta-info p { font-size: 0.75rem; color: var(--color-text-gray); }

        .meta-radio {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            border: 2px solid var(--color-border);
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .meta-card.active .meta-radio {
            border-color: var(--color-malachite);
            background-color: var(--color-malachite);
        }
        .meta-card.active .meta-radio::after {
            content: '✓';
            color: white;
            font-size: 0.7rem;
            font-weight: 900;
        }

        /* RESTRICCIONES */
        .restricciones-list {
            border: 1px solid var(--color-border);
            border-radius: 12px;
            overflow: hidden;
        }
        .restriccion-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            border-bottom: 1px solid var(--color-border);
            font-size: 0.9rem;
            font-weight: 500;
        }
        .restriccion-item:last-child { border-bottom: none; }
        .btn-remove {
            background: none;
            border: none;
            color: var(--color-text-dark);
            font-size: 1.1rem;
            cursor: pointer;
        }
        .add-row {
            display: flex; margin: 0; padding: 0; width: 100%; border-top: 1px solid var(--color-border);
        }
        .add-input {
            flex-grow: 1; border: none; padding: 1rem; font-size: 0.9rem; outline: none;
        }
        .add-btn {
            background: none; border: none; padding: 0 1rem; font-size: 1.2rem; cursor: pointer; color: var(--color-text-dark);
        }

        /* PREMIUM & INFO */
        .premium-banner {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: var(--color-mint);
            padding: 1rem 1.25rem;
            border-radius: 12px;
            margin-top: 1.5rem;
            font-weight: 600;
            font-size: 0.9rem;
            color: var(--color-text-dark);
            text-decoration: none;
        }

        .info-box {
            background-color: var(--color-mint);
            border: 1px solid var(--color-malachite);
            border-radius: 12px;
            padding: 1rem;
            margin-top: 1.5rem;
            display: flex;
            gap: 1rem;
        }
        .info-icon { color: var(--color-malachite); flex-shrink: 0; }
        .info-text h4 { font-size: 0.85rem; font-weight: 700; margin-bottom: 0.25rem; }
        .info-text p { font-size: 0.7rem; color: var(--color-text-gray); line-height: 1.4; }

        /* ACCIONES FINALES */
        .form-actions { margin-top: 2rem; display: flex; flex-direction: column; gap: 1rem; }
        
        .btn-logout {
            width: 100%;
            background-color: transparent;
            color: #ef4444;
            border: 1px solid #ef4444;
            padding: 1rem;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1rem;
            text-align: center;
            text-decoration: none;
        }
        
    </style>
</head>
<body>

    <div class="mobile-container">
        
        <div class="header-top">
            Mi Perfil y Metas
        </div>

        <form action="../controllers/procesar_perfil.php" method="POST" class="perfil-body">
            
            <h2 class="section-title">Datos Biométricos</h2>
            
            <div class="bio-card">
                <div class="bio-grid">
                    <div class="input-group">
                        <label class="input-label">Edad</label>
                        <div class="input-box">
                            <input type="number" name="edad" value="<?= $edad ?>" required>
                            <span>años</span>
                        </div>
                    </div>
                    <div class="input-group">
                        <label class="input-label">Peso</label>
                        <div class="input-box">
                            <input type="number" name="peso" value="<?= $peso ?>" step="0.1" required>
                            <span>kg</span>
                        </div>
                    </div>
                    <div class="input-group">
                        <label class="input-label">Altura</label>
                        <div class="input-box">
                            <input type="number" name="altura" value="<?= $altura ?>" required>
                            <span>cm</span>
                        </div>
                    </div>
                    <div class="input-group">
                        <label class="input-label">Sexo</label>
                        <div class="input-box">
                            <div class="select-wrapper">
                                <select name="sexo">
                                    <option value="Hombre" <?= $sexo == 'Hombre' ? 'selected' : '' ?>>Hombre</option>
                                    <option value="Mujer" <?= $sexo == 'Mujer' ? 'selected' : '' ?>>Mujer</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="input-group">
                    <label class="input-label">Nivel de Actividad</label>
                    <div class="input-box">
                        <div class="select-wrapper">
                            <select name="actividad">
                                <option value="1" <?= $actividad == 1 ? 'selected' : '' ?>>Sedentario (Poco o nulo ejercicio)</option>
                                <option value="2" <?= $actividad == 2 ? 'selected' : '' ?>>Moderado (Ejercicio 3-5 días/sem)</option>
                                <option value="3" <?= $actividad == 3 ? 'selected' : '' ?>>Activo (Ejercicio 6-7 días/sem)</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <h2 class="section-title">Meta Principal</h2>
            
            <!-- Hidden input para meta -->
            <input type="hidden" name="meta_principal" id="meta_input" value="<?= htmlspecialchars($meta) ?>">

            <label class="meta-card <?= $meta == 'Perder Grasa' ? 'active' : '' ?>" onclick="selectMeta(this, 'Perder Grasa')">
                <div class="meta-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2c0 0-5.5 6.5-5.5 11.5a5.5 5.5 0 1 0 11 0C17.5 8.5 12 2 12 2z"></path></svg>
                </div>
                <div class="meta-info">
                    <h4>Perder Grasa</h4>
                    <p>Déficit calórico controlado</p>
                </div>
                <div class="meta-radio"></div>
            </label>

            <label class="meta-card <?= $meta == 'Ganar Músculo' ? 'active' : '' ?>" onclick="selectMeta(this, 'Ganar Músculo')">
                <div class="meta-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18.8 4A6.3 6.3 0 0 1 20 8.2v11.5a1.5 1.5 0 0 1-1.5 1.5H5.5A1.5 1.5 0 0 1 4 19.7V8.2A6.3 6.3 0 0 1 5.2 4"></path><path d="M14 15h.01"></path><path d="M10 15h.01"></path></svg>
                </div>
                <div class="meta-info">
                    <h4>Ganar Músculo</h4>
                    <p>Superávit enfocado en proteína</p>
                </div>
                <div class="meta-radio"></div>
            </label>

            <label class="meta-card <?= $meta == 'Mantener Peso' ? 'active' : '' ?>" onclick="selectMeta(this, 'Mantener Peso')">
                <div class="meta-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19h16"></path><path d="M12 4v15"></path><path d="M6 10l6-6 6 6"></path></svg>
                </div>
                <div class="meta-info">
                    <h4>Mantener Peso</h4>
                    <p>Equilibrio saludable</p>
                </div>
                <div class="meta-radio"></div>
            </label>

            <h2 class="section-title">Restricciones Médicas / Alérgenos</h2>
            
            <div class="restricciones-list">
                <?php foreach($restricciones as $rest): ?>
                <div class="restriccion-item">
                    <?= htmlspecialchars($rest['nombre']) ?>
                    <a href="../controllers/procesar_perfil.php?eliminar_rest=<?= $rest['id_restriccion'] ?>" class="btn-remove">✕</a>
                </div>
                <?php endforeach; ?>
                
                <div class="add-row">
                    <input type="text" name="nueva_restriccion" class="add-input" placeholder="Agregar nueva restricción...">
                    <button type="submit" class="add-btn">⊕</button>
                </div>
            </div>

            <a href="#" class="premium-banner">
                Hazte premium <span>›</span>
            </a>

            <div class="info-box">
                <div class="info-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                </div>
                <div class="info-text">
                    <h4>Importante</h4>
                    <p>Este sistema es una herramienta de apoyo y no sustituye el diagnóstico de un profesional de la nutrición o medicina.</p>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-primary" style="margin-top: 0;">Guardar Cambios</button>
                <a href="../controllers/logout.php" class="btn-logout">Cerrar sesión</a>
            </div>

        </form>

        <?php include 'includes/footer.php'; ?>

    </div>

    <script>
        function selectMeta(element, value) {
            // Remove active from all
            document.querySelectorAll('.meta-card').forEach(el => el.classList.remove('active'));
            // Add active to clicked
            element.classList.add('active');
            // Update hidden input
            document.getElementById('meta_input').value = value;
        }
    </script>
</body>
</html>
