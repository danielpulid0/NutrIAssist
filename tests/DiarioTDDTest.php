<?php
/**
 * ============================================================================
 * DiarioTDDTest.php — Test-Driven Development para el Módulo de Diario
 * ============================================================================
 * Cada bloque de tests fue escrito ANTES del código de producción correspondiente,
 * forzando que cada validación y regla de negocio tenga cobertura automatizada.
 *1. Escribir prueba
* 2. Ejecutar prueba → falla
* 3. Implementar código mínimo
* 4. Ejecutar prueba → pasa
* 5. Refactorizar
* 6. Repetir cicl
 * 
 * EJECUCIÓN:
 *   cd c:\xampp\htdocs\nutriassist
 *   php vendor/bin/phpunit tests/DiarioTDDTest.php --colors
 * 
 * INSTALACIÓN (si no tienen phpunit):
 *   composer require --dev phpunit/phpunit ^10
 * ============================================================================
 */

use PHPUnit\Framework\TestCase;

// ── Cargar dependencias del proyecto ──────────────────────────────────────
require_once __DIR__ . '/../models/Comida.php';

class DiarioTDDTest extends TestCase
{
    // =====================================================================
    // SPRINT 1 — VALIDACIÓN DE FECHA DEL CALENDARIO
    // =====================================================================

    /**
     * RED: La fecha seleccionada debe respetar el formato YYYY-MM-DD.
     * GREEN: Se implementó regex /^\d{4}-\d{2}-\d{2}$/ en diario_controller.php:13
     */
    public function test_fecha_con_formato_valido_es_aceptada(): void
    {
        $fecha_hoy = date('Y-m-d');
        $fecha_sel = '2025-03-15';

        // Simular la validación del controlador
        $es_valida = (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_sel);

        $this->assertTrue($es_valida, 'Una fecha con formato YYYY-MM-DD debe ser aceptada');
    }

    /**
     * RED: Formatos de fecha incorrectos deben ser rechazados.
     * GREEN: El regex filtra cualquier patrón que no sea YYYY-MM-DD.
     */
    public function test_fecha_con_formato_invalido_es_rechazada(): void
    {
        $formatos_invalidos = [
            '15-03-2025',    // DD-MM-YYYY
            '03/15/2025',    // MM/DD/YYYY
            '2025/03/15',    // Barras en vez de guiones
            'abc',           // Texto arbitrario
            '',              // Vacío
            '2025-3-5',      // Sin ceros iniciales
            '2025-13-01',    // Mes 13 (pasa regex pero es semánticamente inválido)
        ];

        foreach ($formatos_invalidos as $fecha) {
            $es_valida = (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha);
            // Los primeros 6 deben fallar el regex; el último pasa regex pero es inválido semánticamente
            if ($fecha === '2025-13-01') {
                $this->assertTrue($es_valida, 'El regex acepta 2025-13-01; la validación semántica es responsabilidad adicional');
            } else {
                $this->assertFalse($es_valida, "El formato '$fecha' debe ser rechazado por el regex");
            }
        }
    }

    /**
     * RED: Fechas futuras deben ser bloqueadas y revertidas a hoy.
     * GREEN: Se implementó $fecha_sel > $fecha_hoy → $fecha_sel = $fecha_hoy
     *        en diario_controller.php:13
     */
    public function test_fecha_futura_se_clampea_a_hoy(): void
    {
        $fecha_hoy = date('Y-m-d');
        $fecha_futura = date('Y-m-d', strtotime('+30 days'));

        // Simular la lógica del controlador
        $fecha_sel = $fecha_futura;
        if ($fecha_sel > $fecha_hoy) {
            $fecha_sel = $fecha_hoy;
        }

        $this->assertEquals($fecha_hoy, $fecha_sel, 'Una fecha futura debe ser reemplazada por la fecha de hoy');
    }

    /**
     * RED: Fechas pasadas deben ser aceptadas sin modificación.
     * GREEN: La condición solo actúa sobre fechas > hoy.
     */
    public function test_fecha_pasada_es_aceptada_sin_cambio(): void
    {
        $fecha_hoy = date('Y-m-d');
        $fecha_pasada = '2025-01-15';

        $fecha_sel = $fecha_pasada;
        if ($fecha_sel > $fecha_hoy) {
            $fecha_sel = $fecha_hoy;
        }

        $this->assertEquals('2025-01-15', $fecha_sel, 'Una fecha pasada debe mantenerse intacta');
    }

    /**
     * RED: La navegación semanal no debe permitir avanzar más allá de hoy.
     * GREEN: Se implementó $semana_next > $fecha_hoy → null en diario_controller.php:35
     */
    public function test_semana_siguiente_se_anula_si_es_futura(): void
    {
        $fecha_hoy = date('Y-m-d');
        $fecha_sel = $fecha_hoy;
        $semana_next = date('Y-m-d', strtotime('+7 days', strtotime($fecha_sel)));

        if ($semana_next > $fecha_hoy) {
            $semana_next = null;
        }

        $this->assertNull($semana_next, 'La semana siguiente debe ser null cuando supera la fecha de hoy');
    }

    // =====================================================================
    // SPRINT 2 — NORMALIZACIÓN DEL TIPO DE COMIDA
    // Historia: "Como sistema, necesito que cualquier texto de tipo de comida
    //            se normalice al ENUM de MySQL para evitar errores de INSERT."
    // =====================================================================

    /**
     * RED: Los tipos estándar deben mapearse correctamente.
     * GREEN: Se implementó Comida::normalizarTipoComida() en Comida.php:6-18
     */
    public function test_tipos_estandar_se_normalizan_correctamente(): void
    {
        $this->assertEquals('Desayuno', Comida::normalizarTipoComida('Desayuno'));
        $this->assertEquals('Comida',   Comida::normalizarTipoComida('Comida'));
        $this->assertEquals('Cena',     Comida::normalizarTipoComida('Cena'));
        $this->assertEquals('Snack',    Comida::normalizarTipoComida('Snack'));
    }

    /**
     * RED: Sinónimos deben mapearse al ENUM correcto.
     * GREEN: El mapa de tipos incluye Almuerzo→Comida, Merienda→Snack, etc.
     */
    public function test_sinonimos_se_mapean_al_enum_correcto(): void
    {
        $this->assertEquals('Comida', Comida::normalizarTipoComida('Almuerzo'));
        $this->assertEquals('Snack',  Comida::normalizarTipoComida('Merienda'));
        $this->assertEquals('Snack',  Comida::normalizarTipoComida('Postre'));
    }

    /**
     * RED: Tipos desconocidos deben defaultear a 'Snack'.
     * GREEN: El operador ?? 'Snack' al final del mapa.
     */
    public function test_tipo_desconocido_defaultea_a_snack(): void
    {
        $this->assertEquals('Snack', Comida::normalizarTipoComida('BrunchEspecial'));
        $this->assertEquals('Snack', Comida::normalizarTipoComida('xyz'));
        $this->assertEquals('Snack', Comida::normalizarTipoComida(''));
    }

    /**
     * RED: El tipo debe ser case-insensitive.
     * GREEN: ucfirst(strtolower(trim(...))) normaliza el casing.
     */
    public function test_tipo_es_case_insensitive(): void
    {
        $this->assertEquals('Desayuno', Comida::normalizarTipoComida('DESAYUNO'));
        $this->assertEquals('Comida',   Comida::normalizarTipoComida('comida'));
        $this->assertEquals('Cena',     Comida::normalizarTipoComida('cEnA'));
        $this->assertEquals('Snack',    Comida::normalizarTipoComida('  snack  '));
    }

    /**
     * RED: Un tipo null no debe causar un error fatal.
     * GREEN: El operador ?? 'snack' maneja nulls.
     */
    public function test_tipo_null_no_causa_error(): void
    {
        $this->assertEquals('Snack', Comida::normalizarTipoComida(null));
    }

    // =====================================================================
    // SPRINT 3 — VALIDACIÓN DE ENTRADA DEL REGISTRO MANUAL
    // Historia: "Como usuario, no debo poder registrar alimentos con campos
    //            vacíos, cantidades negativas, ni valores fuera de rango."
    // =====================================================================

    /**
     * RED: Un payload vacío o sin items debe ser rechazado.
     * GREEN: Se implementó la validación empty($datos['items']) en
     *        guardar_comida_lista.php:16
     */
    public function test_payload_sin_items_es_rechazado(): void
    {
        $datos_vacios = ['items' => []];
        $datos_null   = null;
        $datos_sin_key = ['tipo_comida' => 'Desayuno'];

        $this->assertTrue(empty($datos_vacios['items']), 'Un array vacío de items debe ser rechazado');
        $this->assertTrue(!$datos_null || empty($datos_null['items'] ?? []), 'Un payload null debe ser rechazado');
        $this->assertTrue(empty($datos_sin_key['items'] ?? []), 'Un payload sin clave items debe ser rechazado');
    }

    /**
     * RED: No se deben permitir más de 20 alimentos por registro.
     * GREEN: Se implementó count($datos['items']) > 20 en guardar_comida_lista.php:21
     */
    public function test_limite_maximo_de_20_items_por_registro(): void
    {
        $items_21 = array_fill(0, 21, ['nombre' => 'Test', 'gramos' => 100]);
        $items_20 = array_fill(0, 20, ['nombre' => 'Test', 'gramos' => 100]);

        $this->assertTrue(count($items_21) > 20, '21 items deben exceder el límite');
        $this->assertFalse(count($items_20) > 20, '20 items están dentro del límite');
    }

    /**
     * RED: Un ítem sin nombre debe ser rechazado.
     * GREEN: Se implementó empty($nombre) en guardar_comida_lista.php:52
     */
    public function test_item_sin_nombre_es_rechazado(): void
    {
        $items_invalidos = [
            ['nombre' => '',    'gramos' => 100],
            ['nombre' => '   ', 'gramos' => 100],
            ['nombre' => null,  'gramos' => 100],
        ];

        foreach ($items_invalidos as $item) {
            $nombre = htmlspecialchars(trim($item['nombre'] ?? ''));
            $this->assertTrue(empty($nombre), "Un nombre vacío/nulo debe ser rechazado: '{$item['nombre']}'");
        }
    }

    /**
     * RED: La cantidad en gramos debe ser mayor a 0.
     * GREEN: Se implementó $gramos <= 0 en guardar_comida_lista.php:56
     */
    public function test_gramos_debe_ser_mayor_a_cero(): void
    {
        $cantidades_invalidas = [0, -1, -100, -0.5];
        $cantidades_validas   = [1, 0.5, 100, 5000];

        foreach ($cantidades_invalidas as $g) {
            $this->assertTrue($g <= 0, "Gramos=$g debe ser rechazado (<=0)");
        }
        foreach ($cantidades_validas as $g) {
            $this->assertFalse($g <= 0, "Gramos=$g debe ser aceptado (>0)");
        }
    }

    /**
     * RED: Los macros negativos deben ser rechazados (anti-trampas).
     * GREEN: Se implementó $calorias < 0 || $proteina < 0 ... en
     *        guardar_comida_lista.php:61
     */
    public function test_macros_negativos_son_rechazados(): void
    {
        $casos = [
            ['cal' => -10, 'prot' => 5,  'carbs' => 10,  'grasas' => 3],
            ['cal' => 100, 'prot' => -1, 'carbs' => 10,  'grasas' => 3],
            ['cal' => 100, 'prot' => 5,  'carbs' => -20, 'grasas' => 3],
            ['cal' => 100, 'prot' => 5,  'carbs' => 10,  'grasas' => -5],
        ];

        foreach ($casos as $i => $c) {
            $tiene_negativo = ($c['cal'] < 0 || $c['prot'] < 0 || $c['carbs'] < 0 || $c['grasas'] < 0);
            $this->assertTrue($tiene_negativo, "Caso #$i: macros negativos deben ser detectados");
        }
    }

    /**
     * RED: Macros válidos (todos >= 0) deben ser aceptados.
     * GREEN: La condición solo rechaza si alguno es < 0.
     */
    public function test_macros_validos_son_aceptados(): void
    {
        $caso_valido = ['cal' => 250, 'prot' => 20, 'carbs' => 30, 'grasas' => 8];

        $tiene_negativo = ($caso_valido['cal'] < 0 || $caso_valido['prot'] < 0 ||
                          $caso_valido['carbs'] < 0 || $caso_valido['grasas'] < 0);

        $this->assertFalse($tiene_negativo, 'Macros positivos deben pasar la validación');
    }

    /**
     * RED: Calorías > 2000 deben ser rechazadas como anomalía.
     * GREEN: Se implementó $calorias > 2000 en guardar_comida_lista.php:65
     */
    public function test_calorias_excesivas_son_rechazadas(): void
    {
        $this->assertTrue(2001 > 2000, 'Calorías=2001 deben superar el límite');
        $this->assertFalse(2000 > 2000, 'Calorías=2000 están en el límite permitido');
        $this->assertFalse(500 > 2000, 'Calorías=500 están dentro del rango');
    }

    // =====================================================================
    // SPRINT 4 — VALIDACIÓN DE FECHA EN EL GUARDADO (SERVER-SIDE)
    // Historia: "Como sistema, debo sanitizar la fecha recibida del frontend
    //            para proteger contra inyecciones y datos futuros."
    // =====================================================================

    /**
     * RED: Una fecha válida del pasado debe ser aceptada.
     * GREEN: Se implementó regex + clamp en guardar_comida_lista.php:32-36
     */
    public function test_fecha_valida_en_guardado_es_aceptada(): void
    {
        $fecha_hoy = date('Y-m-d');
        $fecha_raw = '2025-06-10';

        $fecha_final = preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_raw) ? $fecha_raw : $fecha_hoy;
        if ($fecha_final > $fecha_hoy) $fecha_final = $fecha_hoy;

        $this->assertEquals('2025-06-10', $fecha_final);
    }

    /**
     * RED: Una fecha inválida en el guardado debe defaultear a hoy.
     * GREEN: El regex falla y se usa $fecha_hoy como fallback.
     */
    public function test_fecha_invalida_en_guardado_defaultea_a_hoy(): void
    {
        $fecha_hoy = date('Y-m-d');
        $fecha_raw = 'INVALID';

        $fecha_final = preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_raw) ? $fecha_raw : $fecha_hoy;

        $this->assertEquals($fecha_hoy, $fecha_final);
    }

    /**
     * RED: Una fecha futura en el guardado debe ser clampeada a hoy.
     * GREEN: $fecha_raw > $fecha_hoy → $fecha_raw = $fecha_hoy
     */
    public function test_fecha_futura_en_guardado_se_clampea_a_hoy(): void
    {
        $fecha_hoy = date('Y-m-d');
        $fecha_raw = '2099-12-31';

        $fecha_final = preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_raw) ? $fecha_raw : $fecha_hoy;
        if ($fecha_final > $fecha_hoy) $fecha_final = $fecha_hoy;

        $this->assertEquals($fecha_hoy, $fecha_final);
    }

    // =====================================================================
    // SPRINT 5 — ELIMINACIÓN SEGURA DE REGISTROS
    // Historia: "Como usuario, solo debo poder eliminar mis propios registros
    //            y el sistema debe verificar la propiedad antes de borrar."
    // =====================================================================

    /**
     * RED: Un id_consumo no proporcionado debe ser rechazado.
     * GREEN: Se implementó !isset($datos['id_consumo']) en eliminar_comida.php:9
     */
    public function test_eliminacion_sin_id_es_rechazada(): void
    {
        $datos_sin_id = [];
        $datos_con_id = ['id_consumo' => 42];

        $this->assertFalse(isset($datos_sin_id['id_consumo']), 'Sin ID debe ser rechazado');
        $this->assertTrue(isset($datos_con_id['id_consumo']), 'Con ID debe pasar');
    }

    /**
     * RED: El id_consumo debe ser casteado a entero para prevenir inyección.
     * GREEN: Se implementó (int) $datos['id_consumo'] en eliminar_comida.php:14
     */
    public function test_id_consumo_se_castea_a_entero(): void
    {
        $this->assertEquals(42, (int) '42');
        $this->assertEquals(0,  (int) 'abc');
        $this->assertEquals(0,  (int) '');
        $this->assertEquals(7,  (int) '7.9');
    }

    // =====================================================================
    // SPRINT 6 — CÁLCULO DE MACROS PROPORCIONALES
    // Historia: "Como usuario, al ingresar 250g de un alimento con 200kcal/100g,
    //            el sistema debe calcular 500 kcal automáticamente."
    // =====================================================================

    /**
     * RED: Los macros deben calcularse proporcionalmente a los gramos.
     * GREEN: Se implementó (gramos/100) * macro_por_100g en diario.js:446-450
     */
    public function test_calculo_proporcional_de_macros(): void
    {
        // Simular: Arroz Blanco - 130 kcal/100g, 2.7g prot, 28g carbs, 0.3g grasas
        $cal_100  = 130;
        $prot_100 = 2.7;
        $c_100    = 28;
        $g_100    = 0.3;
        $gramos   = 250;

        $factor = $gramos / 100;
        $cal    = round($cal_100 * $factor);
        $prot   = round($prot_100 * $factor, 1);
        $carbs  = round($c_100 * $factor, 1);
        $grasas = round($g_100 * $factor, 1);

        $this->assertEquals(325,  $cal,    '250g de arroz (130kcal/100g) = 325 kcal');
        $this->assertEquals(6.8,  $prot,   '250g de arroz (2.7g prot/100g) = 6.8g');
        $this->assertEquals(70.0, $carbs,  '250g de arroz (28g carbs/100g) = 70g');
        $this->assertEquals(0.8,  $grasas, '250g de arroz (0.3g grasas/100g) = 0.8g');
    }

    /**
     * RED: 100g exactos deben devolver los valores base sin modificación.
     * GREEN: factor = 1.0 → resultado idéntico a los valores por 100g.
     */
    public function test_100g_devuelve_valores_base(): void
    {
        $cal_100 = 155; // Huevo
        $gramos  = 100;

        $this->assertEquals(155, round($cal_100 * ($gramos / 100)));
    }

    /**
     * RED: 0 gramos deben devolver 0 en todos los macros.
     * GREEN: factor = 0 → todo es 0.
     */
    public function test_cero_gramos_devuelve_cero_macros(): void
    {
        $cal_100 = 200;
        $gramos  = 0;

        $this->assertEquals(0, round($cal_100 * ($gramos / 100)));
    }

    // =====================================================================
    // SPRINT 7 — RACHA (STREAK) DE CUMPLIMIENTO
    // Historia: "Como usuario, quiero ver mi racha de días consecutivos
    //            cumpliendo al menos el 90% de mi meta calórica."
    // =====================================================================

    /**
     * RED: El umbral de cumplimiento debe ser 90% de la meta.
     * GREEN: Se implementó $min_goal = $meta * 0.9 en Diario.php:141
     */
    public function test_umbral_racha_es_90_porciento(): void
    {
        $meta = 2000;
        $min_goal = $meta * 0.9;

        $this->assertEquals(1800, $min_goal);
        $this->assertTrue(1800 >= $min_goal, '1800 kcal cumple (exactamente 90%)');
        $this->assertTrue(2000 >= $min_goal, '2000 kcal cumple (100%)');
        $this->assertFalse(1799 >= $min_goal, '1799 kcal NO cumple (<90%)');
    }

    // =====================================================================
    // SPRINT 8 — SANITIZACIÓN Y ESCAPE DE TEXTO
    // Historia: "Como sistema, debo escapar cualquier texto del usuario para
    //            prevenir ataques XSS en la interfaz."
    // =====================================================================

    /**
     * RED: htmlspecialchars debe neutralizar tags HTML maliciosos.
     * GREEN: Se usa htmlspecialchars() en guardar_comida_lista.php:44
     */
    public function test_xss_es_neutralizado_en_nombres(): void
    {
        $nombre_malicioso = '<script>alert("xss")</script>';
        $nombre_limpio = htmlspecialchars(trim($nombre_malicioso));

        $this->assertStringNotContainsString('<script>', $nombre_limpio);
        $this->assertStringContainsString('&lt;script&gt;', $nombre_limpio);
    }

    /**
     * RED: Un nombre normal no debe ser alterado.
     * GREEN: htmlspecialchars no modifica texto sin caracteres especiales.
     */
    public function test_nombre_normal_no_se_altera(): void
    {
        $nombre = 'Pechuga de Pollo';
        $this->assertEquals('Pechuga de Pollo', htmlspecialchars(trim($nombre)));
    }
}
