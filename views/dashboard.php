<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.html");
    exit();
}

$nombre_usuario = $_SESSION['usuario_nombre'] ?? 'Usuario';
$meta_calorias  = (int) ($_SESSION['meta_calorias'] ?? 2000);
$meta_proteina  = 150;
$meta_carbs     = 220;
$meta_grasas    = 70;

// ─ Fecha y saludo dinámico ────────────────────────────────────────────
$hora_actual = (int) date('H');
if ($hora_actual < 12)      $saludo = 'Buenos días';
elseif ($hora_actual < 19)  $saludo = 'Buenas tardes';
else                        $saludo = 'Buenas noches';

$dias_es_corto  = ['Dom','Lun','Mar','Mié','Jue','Vie','Sáb'];
$meses_es_corto = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
$label_hoy = $dias_es_corto[(int)date('w')] . ', ' . (int)date('j') . ' ' . $meses_es_corto[(int)date('n') - 1];

// ─── Consulta real: SUM de macros del día ─────────────────────────
require_once '../config/conexion.php';

$id_usuario = (int) $_SESSION['usuario_id'];
$fecha_hoy  = date('Y-m-d');

$calorias_consumidas = 0;
$pro_consumidas      = 0;
$carbs_consumidas    = 0;
$grasas_consumidas   = 0;

try {
    $stmt = $conn->prepare("
        SELECT
            COALESCE(SUM(ac.calorias_ia), 0) AS total_cal,
            COALESCE(SUM(ac.proteina_ia), 0) AS total_pro,
            COALESCE(SUM(ac.carbs_ia),    0) AS total_carbs,
            COALESCE(SUM(ac.grasas_ia),   0) AS total_grasas
        FROM Alimentos_Consumidos ac
        INNER JOIN Comidas c
            ON ac.id_comida = c.id_comida
        INNER JOIN Registros_Diarios rd
            ON c.id_registro = rd.id_registro
        WHERE rd.id_usuario = :uid
          AND rd.fecha      = :fecha
    ");
    $stmt->execute([':uid' => $id_usuario, ':fecha' => $fecha_hoy]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    $calorias_consumidas = (float) $row['total_cal'];
    $pro_consumidas      = (float) $row['total_pro'];
    $carbs_consumidas    = (float) $row['total_carbs'];
    $grasas_consumidas   = (float) $row['total_grasas'];

} catch (PDOException $e) {
    error_log('[dashboard] DB ERROR: ' . $e->getMessage());
}

$calorias_restantes = max(0, $meta_calorias - $calorias_consumidas);
$porcentaje_anillo  = ($meta_calorias > 0) ? min(100, ($calorias_consumidas / $meta_calorias) * 100) : 0;
$pro_p    = ($meta_proteina > 0) ? min(100, ($pro_consumidas   / $meta_proteina) * 100) : 0;
$carbs_p  = ($meta_carbs    > 0) ? min(100, ($carbs_consumidas / $meta_carbs)    * 100) : 0;
$grasas_p = ($meta_grasas   > 0) ? min(100, ($grasas_consumidas/ $meta_grasas)   * 100) : 0;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>NutrIAssist - Inicio</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/global.css">
    
    <style>
        .header-dashboard {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .user-greeting p { color: var(--color-text-gray); font-size: 0.9rem; margin-bottom: 0.2rem; }
        .user-greeting h1 { font-size: 1.5rem; }

        /* EL ANILLO CIRCULAR DE CALORÍAS */
        .ring-container {
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 2rem 0;
        }

        .calorie-ring {
            width: 200px;
            height: 200px;
            border-radius: 50%;
            /* Aquí está la magia: un gradiente cónico que se llena según el porcentaje de PHP */
            background: conic-gradient(
                var(--color-malachite) <?= $porcentaje_anillo ?>%, 
                var(--color-mint) <?= $porcentaje_anillo ?>% 100%
            );
            display: flex;
            justify-content: center;
            align-items: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }

        .ring-inner {
            width: 170px;
            height: 170px;
            background-color: var(--color-bg);
            border-radius: 50%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
        }

        .ring-inner h2 { font-size: 2.2rem; margin: 0; color: var(--color-text-dark); }
        .ring-inner p { font-size: 0.9rem; color: var(--color-text-gray); }

        /* LISTA PARA LOS MACRONUTRIENTES */
        .macros-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .macro-card-h {
            background-color: var(--color-bg);
            border: 1px solid var(--color-border);
            border-radius: 12px;
            padding: 1.25rem;
        }

        .macro-header-h {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.75rem;
        }

        .macro-title-h {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1rem;
            font-weight: 600;
            color: var(--color-text-dark);
        }

        .macro-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
        }

        .macro-val-h { font-size: 0.9rem; color: var(--color-text-gray); }

        .macro-bar-bg {
            width: 100%;
            height: 8px;
            background-color: var(--color-bg-app);
            border-radius: 99px;
            overflow: hidden;
        }

        .macro-bar-fill {
            height: 100%;
            border-radius: 99px;
        }

        /* CARD DE SUGERENCIA DE CENA */
        .section-title {
            font-size: 1.15rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--color-text-dark);
        }

        .suggestion-card {
            background-color: var(--color-bg);
            border: 1px solid var(--color-border);
            border-radius: 16px;
            display: flex;
            position: relative;
            overflow: hidden;
            height: 140px;
        }

        .suggestion-img {
            width: 140px;
            height: 100%;
            object-fit: cover;
            flex-shrink: 0;
        }

        .suggestion-info {
            padding: 1rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            flex-grow: 1;
            padding-right: 2.5rem; /* Para que el texto no se cruce con el botón */
        }

        .sug-meta {
            font-size: 0.75rem;
            color: var(--color-text-gray);
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }
        .sug-meta i { color: #facc15; font-style: normal; font-size: 0.9rem; } /* Estrellita amarillita */

        .sug-title {
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--color-text-dark);
            line-height: 1.2;
            margin: 0.25rem 0;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .sug-desc {
            font-size: 0.75rem;
            color: var(--color-text-gray);
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .sug-tags {
            display: flex;
            gap: 0.4rem;
            margin-top: 0.5rem;
        }
        
        .tag-light {
            padding: 0.35rem 0.6rem;
            border-radius: 6px;
            font-size: 0.7rem;
            font-weight: 600;
        }
        .tag-green { background-color: var(--color-mint); color: var(--color-malachite); }
        .tag-gray { background-color: var(--color-bg-app); color: var(--color-text-gray); }

        .fab-chat {
            position: absolute;
            bottom: 90px;
            right: 20px;
            width: 55px;
            height: 55px;
            background-color: var(--color-primary);
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            box-shadow: 0 4px 15px rgba(55, 246, 119, 0.4);
            cursor: pointer;
            color: var(--color-text-dark);
            text-decoration: none;
            transition: transform 0.2s, box-shadow 0.2s;
            z-index: 100;
        }
        .fab-chat:active { transform: scale(0.95); }

        /* Contenedor principal ajustado para el footer plano */
        .mobile-container { padding-bottom: 90px; }
    </style>
</head>
<body>

    <div class="mobile-container">
        
        <div class="header-dashboard">
            <div class="user-greeting">
                <p><?= htmlspecialchars($saludo) ?></p>
                <h1><?= htmlspecialchars($nombre_usuario) ?></h1>
            </div>
            <div style="text-align: right;">
                <p style="color: var(--color-malachite); font-size: 0.75rem; font-weight: 700;">HOY</p>
                <p style="color: var(--color-text-gray); font-size: 0.8rem;"><?= $label_hoy ?></p>
            </div>
        </div>

        <div class="ring-container">
            <div class="calorie-ring">
                <div class="ring-inner">
                    <p style="color: var(--color-malachite); font-size: 1.2rem; margin-bottom: 0.2rem;">🔥</p>
                    <h2><?= number_format($calorias_consumidas) ?></h2>
                    <p style="font-size: 0.8rem;">de <?= number_format($meta_calorias) ?> kcal</p>
                    <div style="background-color: var(--color-mint); color: var(--color-malachite); font-size: 0.75rem; font-weight: 600; padding: 0.3rem 0.6rem; border-radius: 12px; margin-top: 0.5rem; display: inline-block;">
                        <?= number_format($calorias_restantes) ?> restantes
                    </div>
                </div>
            </div>
        </div>

        <div class="macros-list">
            <!-- Proteínas -->
            <div class="macro-card-h">
                <div class="macro-header-h">
                    <div class="macro-title-h">
                        <span class="macro-dot" style="background-color: #3b82f6;"></span>
                        Proteínas
                    </div>
                    <div class="macro-val-h"><?= $pro_consumidas ?> / 150g</div>
                </div>
                <div class="macro-bar-bg">
                    <div class="macro-bar-fill" style="width: <?= $pro_p ?>%; background-color: #3b82f6;"></div>
                </div>
            </div>
            
            <!-- Carbohidratos -->
            <div class="macro-card-h">
                <div class="macro-header-h">
                    <div class="macro-title-h">
                        <span class="macro-dot" style="background-color: #f97316;"></span>
                        Carbohidratos
                    </div>
                    <div class="macro-val-h"><?= $carbs_consumidas ?> / 220g</div>
                </div>
                <div class="macro-bar-bg">
                    <div class="macro-bar-fill" style="width: <?= $carbs_p ?>%; background-color: #f97316;"></div>
                </div>
            </div>

            <!-- Grasas -->
            <div class="macro-card-h">
                <div class="macro-header-h">
                    <div class="macro-title-h">
                        <span class="macro-dot" style="background-color: #eab308;"></span>
                        Grasas
                    </div>
                    <div class="macro-val-h"><?= $grasas_consumidas ?> / 70g</div>
                </div>
                <div class="macro-bar-bg">
                    <div class="macro-bar-fill" style="width: <?= $grasas_p ?>%; background-color: #eab308;"></div>
                </div>
            </div>
        </div>

        <h2 class="section-title">Sugerencia para la Cena</h2>
        <div class="suggestion-card">
            <img src="https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=400&h=400&fit=crop" alt="Quinoa Bowl" class="suggestion-img">
            <div class="suggestion-info">
                <div class="sug-meta">
                    <i>★</i> 4.8 &nbsp;•&nbsp; 25 min
                </div>
                <h3 class="sug-title">Ensalada de Quinoa y Aguacate</h3>
                <p class="sug-desc">Una cena ligera, rica en fibra y grasas saludables. Perfecta para...</p>
                <div class="sug-tags">
                    <span class="tag-light tag-green">340 kcal</span>
                    <span class="tag-light tag-gray">Vegetariano</span>
                </div>
            </div>
        </div>

        <a href="chat_ia.php" class="fab-chat">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
        </a>

        <?php include 'includes/footer.php'; ?>

    </div>

</body>
</html>