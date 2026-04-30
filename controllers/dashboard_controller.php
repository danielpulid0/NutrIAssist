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
date_default_timezone_set('America/Mexico_City');
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

// ─── LÓGICA DE SUGERENCIA DINÁMICA DE RECETA ──────────────────────
$lowest_macro = min($pro_p, $carbs_p, $grasas_p);
$keyword = "";

if ($lowest_macro == $pro_p) {
    $keyword = '+Proteína';
} elseif ($lowest_macro == $carbs_p) {
    $keyword = '+Carbs';
} else {
    $keyword = 'Keto'; // Para grasas bajas asume Keto u otra
}

try {
    // Buscar una receta que contenga el tag necesario
    $stmt_sug = $conn->prepare("SELECT * FROM Recetas WHERE etiquetas LIKE :kw ORDER BY RAND() LIMIT 1");
    $stmt_sug->execute([':kw' => "%\"$keyword\"%"]);
    $receta_sugerida = $stmt_sug->fetch(PDO::FETCH_ASSOC);

    if (!$receta_sugerida) {
        $stmt_rand = $conn->query("SELECT * FROM Recetas ORDER BY RAND() LIMIT 1");
        $receta_sugerida = $stmt_rand->fetch(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    $receta_sugerida = null;
}

// ─── LÓGICA DE RACHA (STREAK) ──────────────────────────────────────────
$racha_actual = 0;
$racha_activa_hoy = false;

try {
    $stmt_racha = $conn->prepare("SELECT racha_dias, fecha_ultima_conexion FROM Usuarios WHERE id_usuario = :uid");
    $stmt_racha->execute([':uid' => $id_usuario]);
    $user_data = $stmt_racha->fetch(PDO::FETCH_ASSOC);

    $racha_actual = (int)($user_data['racha_dias'] ?? 0);
    $ultima_fecha = $user_data['fecha_ultima_conexion'];
    $hoy = date('Y-m-d');

    if ($ultima_fecha) {
        $fecha_u = new DateTime($ultima_fecha);
        $fecha_h = new DateTime($hoy);
        $diff = $fecha_h->diff($fecha_u)->days;

        if ($diff == 0) {
            // Ya entró hoy
            $racha_activa_hoy = true;
        } elseif ($diff <= 3) {
            // Entró ayer o hace menos de 3 días (racha continúa)
            $racha_actual++;
            $racha_activa_hoy = true;
            $stmt_upd = $conn->prepare("UPDATE Usuarios SET racha_dias = :r, fecha_ultima_conexion = :f WHERE id_usuario = :uid");
            $stmt_upd->execute([':r' => $racha_actual, ':f' => $hoy, ':uid' => $id_usuario]);
        } else {
            // Pasaron más de 3 días, se apaga el fuego (según el usuario, se resetea)
            $racha_actual = 1;
            $racha_activa_hoy = true;
            $stmt_upd = $conn->prepare("UPDATE Usuarios SET racha_dias = 1, fecha_ultima_conexion = :f WHERE id_usuario = :uid");
            $stmt_upd->execute([':f' => $hoy, ':uid' => $id_usuario]);
        }
    } else {
        // Primera racha
        $racha_actual = 1;
        $racha_activa_hoy = true;
        $stmt_upd = $conn->prepare("UPDATE Usuarios SET racha_dias = 1, fecha_ultima_conexion = :f WHERE id_usuario = :uid");
        $stmt_upd->execute([':f' => $hoy, ':uid' => $id_usuario]);
    }
} catch (PDOException $e) {
    error_log('[streak] DB ERROR: ' . $e->getMessage());
}
?>

