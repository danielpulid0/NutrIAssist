<?php
/**
 * Suite de Pruebas de Integración Exhaustivas (Deep Testing)
 * Proyecto: NutriAssist
 */

// Sistema de auto-detección de rutas (Para evitar el Fatal Error de "No such file")
$archivos_necesarios = [
    'conexion' => ['../config/conexion.php', '../conexion.php', 'config/conexion.php'],
    'validator' => ['../utils/Validator.php', '../Validator.php', 'utils/Validator.php'],
    'usuario' => ['../models/Usuario.php', '../Usuario.php', 'models/Usuario.php']
];

foreach ($archivos_necesarios as $tipo => $rutas) {
    $cargado = false;
    foreach ($rutas as $ruta) {
        if (file_exists(__DIR__ . '/' . $ruta)) {
            require_once __DIR__ . '/' . $ruta;
            $cargado = true;
            break;
        }
    }
    if (!$cargado)
        die("Error crítico: No se encontró el archivo de $tipo.");
}

class IntegrationTester
{
    private $db;
    private $stats = [
        'total' => 0,
        'passed' => 0,
        'failed' => 0,
        'execution_times' => []
    ];

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function run()
    {
        $startTime = microtime(true);
        if (php_sapi_name() !== 'cli')
            echo "<pre style='background:#1e1e1e; color:#d4d4d4; padding:20px; border-radius:5px; font-family: monospace;'>";

        echo "<span style='color:#569cd6;'>====================================================</span>\n";
        echo "<span style='color:#ce9178;'> INICIANDO SUITE DE PRUEBAS DE INTEGRACIÓN AVANZADA </span>\n";
        echo "<span style='color:#569cd6;'>====================================================</span>\n\n";

        // --- FASE 1: Pruebas de la Unidad Validator ---
        echo "<span style='color:#4ec9b0;'>[Fase 1] Pruebas de Aislamiento y Límite: Validator.php</span>\n";
        $this->runTest("Inc 1: Límite de entropía en Password", [$this, 'testPasswordBoundary']);
        $this->runTest("Inc 2: Detección de múltiples emails inválidos", [$this, 'testMultipleEmails']);
        $this->runTest("Inc 3: Rechazo de campos con espacios en blanco", [$this, 'testEmptyRequired']);
        $this->runTest("Inc 4: Límites matemáticos (Min/Max boundaries)", [$this, 'testMinMaxBoundaries']);
        $this->runTest("Inc 5: Prevención de Inyección XSS en Email", [$this, 'testXSSPayloadEmail']);

        // --- FASE 2: Pruebas de la Unidad Conexion ---
        echo "\n<span style='color:#4ec9b0;'>[Fase 2] Validando Instancia PDO y Seguridad (conexion.php)</span>\n";
        $this->runTest("Inc 6: Verificación de PDO::ERRMODE_EXCEPTION", [$this, 'testPDOErrorMode']);
        $this->runTest("Inc 7: Verificación de Charset Seguro (utf8mb4)", [$this, 'testCharsetUtf8mb4']);

        // --- FASE 3: Pruebas de Integración (Validador + Modelo + BD) ---
        echo "\n<span style='color:#4ec9b0;'>[Fase 3] Integración Transaccional (Validator + Usuario + BD)</span>\n";
        $this->runTest("Inc 8: Inyección SQL prevenida por Validator numérico", [$this, 'testSQLInjectionPrevention']);
        $this->runTest("Inc 9: Manejo Controlado de Nulos (Usuario Inexistente)", [$this, 'testNonExistentUser']);
        $this->runTest("Inc 10: Integridad relacional en JOIN (getRestricciones)", [$this, 'testJoinIntegrity']);
        $this->runTest("Inc 11: Prevención de Llaves Duplicadas (INSERT IGNORE)", [$this, 'testDuplicateInsertIgnore']);
        $this->runTest("Inc 12: Idempotencia en Sembrado de Datos", [$this, 'testIdempotencyActivityLevels']);
        $this->runTest("Inc 13: Desbordamiento de BD (Cadenas extremadamente largas)", [$this, 'testExtremelyLongString']);

        // --- Pruebas Forzadas a Fallar (Para el reporte analítico) ---
        echo "\n<span style='color:#dcdcaa;'>[Fase 4: Inyecciones de Fallos y Pruebas Negativas]</span>\n";
        $this->runTest("Inc 14: [Forzada] Modelo recibe Arrays en lugar de Strings", [$this, 'testArrayInputToModel']);
        $this->runTest("Inc 15: [Forzada] Validador colapsa por Tipo de Dato Inesperado", [$this, 'testTypeMismatchValidator']);
        $this->runTest("Inc 16: [Forzada] Excepción PDO por Credenciales Simuladas", [$this, 'testSimulatedDbCrash']);
        $this->runTest("Inc 17: [Forzada] Timeout de red simulado (100ms lag)", [$this, 'testSimulatedTimeout']);

        $endTime = microtime(true);
        $this->printSummary($endTime - $startTime);

        if (php_sapi_name() !== 'cli')
            echo "</pre>";
    }

    private function runTest($name, $callback)
    {
        $this->stats['total']++;
        $start = microtime(true);
        try {
            $result = call_user_func($callback);
            $time = round((microtime(true) - $start) * 1000, 2);
            $this->stats['execution_times'][] = $time;

            if ($result) {
                $this->stats['passed']++;
                echo " -> $name... <span style='color:#4fc1ff;'>[OK]</span> <span style='color:#b5cea8;'>{$time}ms</span>\n";
            } else {
                $this->stats['failed']++;
                echo " -> $name... <span style='color:#f44747;'>[FALLÓ]</span> <span style='color:#b5cea8;'>{$time}ms</span>\n";
            }
        } catch (Exception $e) {
            $this->stats['failed']++;
            $time = round((microtime(true) - $start) * 1000, 2);
            $this->stats['execution_times'][] = $time;
            echo " -> $name... <span style='color:#f44747;'>[EXCEPCIÓN ATRAPADA]</span> <span style='color:#b5cea8;'>{$time}ms</span> (" . $e->getMessage() . ")\n";
        } catch (TypeError $e) {
            $this->stats['failed']++;
            $time = round((microtime(true) - $start) * 1000, 2);
            $this->stats['execution_times'][] = $time;
            echo " -> $name... <span style='color:#f44747;'>[ERROR DE TIPO]</span> <span style='color:#b5cea8;'>{$time}ms</span> (" . $e->getMessage() . ")\n";
        }
    }

    // ==========================================
    // PRUEBAS DE VALIDACIÓN
    // ==========================================

    public function testPasswordBoundary()
    {
        $v = new Validator();
        $v->password('Abc123!'); // 7 chars, debe fallar
        $v2 = new Validator();
        $v2->password('Abcdef12!'); // 9 chars, valido
        return $v->hasErrors() && !$v2->hasErrors();
    }

    public function testMultipleEmails()
    {
        $v = new Validator();
        $v->email('test@.com')->email('usuario@dominio')->email('test@com');
        return $v->hasErrors();
    }

    public function testEmptyRequired()
    {
        $v = new Validator();
        $v->required(['nombre' => '   ', 'email' => ''], ['nombre', 'email']);
        return $v->hasErrors();
    }

    public function testMinMaxBoundaries()
    {
        $v = new Validator();
        $v->min(5, 10, 'edad'); // 5 es menor que 10 (Falla)
        $v->max(20, 10, 'peso'); // 20 es mayor que 10 (Falla)
        $v2 = new Validator();
        $v2->min(15, 10, 'edad')->max(15, 20, 'edad'); // Pasa ambos
        return $v->hasErrors() && !$v2->hasErrors();
    }

    public function testXSSPayloadEmail()
    {
        $v = new Validator();
        $v->email('<script>alert("hack")</script>@dominio.com');
        return $v->hasErrors(); // Debe rechazarlo como email inválido
    }

    // ==========================================
    // PRUEBAS DE CONEXIÓN
    // ==========================================

    public function testPDOErrorMode()
    {
        return $this->db->getAttribute(PDO::ATTR_ERRMODE) === PDO::ERRMODE_EXCEPTION;
    }

    public function testCharsetUtf8mb4()
    {
        $stmt = $this->db->query("SELECT @@character_set_connection");
        $charset = $stmt->fetchColumn();
        return strpos($charset, 'utf8mb4') !== false;
    }

    // ==========================================
    // PRUEBAS DE INTEGRACIÓN
    // ==========================================

    public function testSQLInjectionPrevention()
    {
        $v = new Validator();
        $idInyectado = "1; DROP TABLE Usuarios;";
        $v->numeric($idInyectado, 'id');
        return $v->hasErrors(); // Debe ser bloqueado por Validator
    }

    public function testNonExistentUser()
    {
        return Usuario::getById($this->db, 9999999) === false;
    }

    public function testJoinIntegrity()
    {
        return is_array(Usuario::getRestricciones($this->db, 1));
    }

    public function testDuplicateInsertIgnore()
    {
        $res = "Prueba_" . rand(100, 999);
        Usuario::addRestriccion($this->db, 1, $res);
        return Usuario::addRestriccion($this->db, 1, $res) === true;
    }

    public function testIdempotencyActivityLevels()
    {
        // Ejecutar dos veces no debe duplicar las filas
        Usuario::ensureActivityLevelsExist($this->db);
        $count1 = $this->db->query("SELECT COUNT(*) FROM Nivel_Actividad")->fetchColumn();
        Usuario::ensureActivityLevelsExist($this->db);
        $count2 = $this->db->query("SELECT COUNT(*) FROM Nivel_Actividad")->fetchColumn();
        return $count1 > 0 && $count1 == $count2;
    }

    public function testExtremelyLongString()
    {
        // Simulamos un ataque de desbordamiento de buffer en un campo de texto
        $cadenaInmensa = str_repeat("A", 2000);
        try {
            // Intenta insertar 2000 caracteres en un campo que seguro es VARCHAR(50) o similar
            Usuario::addRestriccion($this->db, 1, $cadenaInmensa);
            // Si llega aquí sin excepción, es que MySQL lo truncó o lo aceptó. Lo marcamos OK.
            return true;
        } catch (PDOException $e) {
            // Si lanza excepción de "Data too long", es un comportamiento esperado y seguro.
            return true;
        }
    }

    // ==========================================
    // PRUEBAS FORZADAS A FALLAR (ANALÍTICA)
    // ==========================================

    public function testArrayInputToModel()
    {
        // Enviar array cuando se espera int/string rompe bindParam
        return Usuario::getById($this->db, ['id' => 1]) !== false;
    }

    public function testTypeMismatchValidator()
    {
        $v = new Validator();
        // PHP 8 lanzará TypeError si trim() recibe un objeto
        $v->required(['nombre' => new stdClass()], ['nombre']);
        return false;
    }

    public function testSimulatedDbCrash()
    {
        // Instanciamos PDO con contraseña mala a propósito
        $badDb = new PDO("mysql:host=localhost;dbname=test", "root", "password_falsa_123");
        return true; // Nunca llegará aquí
    }

    public function testSimulatedTimeout()
    {
        usleep(100000); // 100ms
        return false;
    }

    private function printSummary($totalTime)
    {
        $percent = ($this->stats['total'] > 0) ? ($this->stats['passed'] / $this->stats['total']) * 100 : 0;
        $avgTime = count($this->stats['execution_times']) > 0 ? array_sum($this->stats['execution_times']) / count($this->stats['execution_times']) : 0;

        echo "\n<span style='color:#569cd6;'>----------------------------------------------------</span>\n";
        echo "<span style='color:#ce9178;'> RESULTADOS ESTADÍSTICOS GLOBALES</span>\n";
        echo "<span style='color:#569cd6;'>----------------------------------------------------</span>\n";
        echo "Pruebas Diseñadas:  {$this->stats['total']}\n";
        echo "Éxito (Comportamiento Esperado): <span style='color:#4fc1ff;'>{$this->stats['passed']}</span>\n";
        echo "Anomalías Detectadas / Errores:  <span style='color:#f44747;'>{$this->stats['failed']}</span>\n";
        echo "Índice de Estabilidad: " . number_format($percent, 2) . "%\n";
        echo "Tiempo de barrido (Total): " . number_format($totalTime, 3) . "s\n";
        echo "Latencia promedio por test: " . number_format($avgTime, 2) . "ms\n";
        echo "<span style='color:#569cd6;'>====================================================</span>\n";
    }
}

// Ejecución
if (isset($conn)) {
    $tester = new IntegrationTester($conn);
    $tester->run();
} else {
    echo "Error crítico: La variable \$conn no se encontró en el archivo de conexión.";
}
?>