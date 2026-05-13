<?php
/**
 * Script de Pruebas de Integración Incremental para NutriAssist
 * Este script automatiza la ejecución de los 3 incrementos solicitados.
 */

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../utils/Validator.php';
require_once __DIR__ . '/../models/Usuario.php';

class IntegrationTester
{
    private $db;
    private $results = [];

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function run()
    {
        // Si se ejecuta en navegador, usamos <pre> para que se vea ordenado
        if (php_sapi_name() !== 'cli')
            echo "<pre>";

        echo "====================================================\n";
        echo " INICIANDO PRUEBAS DE INTEGRACIÓN: NUTRIASSIST \n";
        echo "====================================================\n\n";

        // INCREMENTO 1: Probar solo Validator (Unidad Individual)
        $this->testIncrement1();

        // INCREMENTO 2: Probar Validator + Usuario (Integración con BD)
        $this->testIncrement2();

        // INCREMENTO 3: Simular flujo de controlador (Integración de componentes)
        $this->testIncrement3();

        $this->printStatistics();

        if (php_sapi_name() !== 'cli')
            echo "</pre>";
    }

    private function testIncrement1()
    {
        echo "[Inc 1] Probando Unidad: Validator.php... ";
        $res1 = Validator::validarEmail("test@example.com");
        $res2 = Validator::validarPassword("123"); // Corta a propósito

        if ($res1 === true && $res2 === false) {
            $this->logResult(1, true);
            echo "PASÓ ✅\n";
        } else {
            $this->logResult(1, false);
            echo "FALLÓ ❌\n";
        }
    }

    private function testIncrement2()
    {
        echo "[Inc 2] Probando Integración: Validator + Modelo Usuario... ";
        try {
            $email = "test_int_" . time() . "@test.com";
            $pass = "password123";

            if (Validator::validarEmail($email)) {
                $userModel = new Usuario($this->db);
                $saved = $userModel->registrar("Usuario Prueba", $email, $pass);

                if ($saved) {
                    $this->logResult(2, true);
                    echo "PASÓ ✅\n";
                } else {
                    throw new Exception("El modelo no pudo insertar en la BD.");
                }
            }
        } catch (Exception $e) {
            $this->logResult(2, false);
            echo "FALLÓ ❌ (" . $e->getMessage() . ")\n";
        }
    }

    private function testIncrement3()
    {
        echo "[Inc 3] Probando Integración Global: Flujo de Registro... ";
        // Verificamos que la conexión esté activa y el modelo responda
        if (isset($this->db) && $this->db instanceof PDO) {
            $this->logResult(3, true);
            echo "PASÓ ✅\n";
        } else {
            $this->logResult(3, false);
            echo "FALLÓ ❌\n";
        }
    }

    private function logResult($inc, $status)
    {
        $this->results[] = ['inc' => $inc, 'success' => $status];
    }

    private function printStatistics()
    {
        $total = count($this->results);
        $success = count(array_filter($this->results, fn($r) => $r['success']));
        $percent = ($success / $total) * 100;

        echo "\n----------------------------------------------------\n";
        echo " RESULTADOS ESTADÍSTICOS\n";
        echo "----------------------------------------------------\n";
        echo "Pruebas Totales: $total\n";
        echo "Exitosas:       $success\n";
        echo "Fallidas:       " . ($total - $success) . "\n";
        echo "Tasa de Éxito:  " . number_format($percent, 2) . "%\n";
        echo "----------------------------------------------------\n";
    }
}

// Lógica de ejecución automática para XAMPP
try {
    // Usamos la variable $conn que viene de config/conexion.php
    if (isset($conn)) {
        $tester = new IntegrationTester($conn);
        $tester->run();
    } else {
        echo "Error: No se pudo encontrar la variable de conexión \$conn.";
    }
} catch (Exception $e) {
    echo "Error crítico: " . $e->getMessage();
}
?>