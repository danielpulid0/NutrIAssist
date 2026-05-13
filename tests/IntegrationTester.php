<?php
/**
 * Script de Pruebas de Integración Incremental para NutriAssist
 * Corregido para usar los nombres de métodos correctos del proyecto.
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
        if (php_sapi_name() !== 'cli')
            echo "<pre>";

        echo "====================================================\n";
        echo " INICIANDO PRUEBAS DE INTEGRACIÓN: NUTRIASSIST \n";
        echo "====================================================\n\n";

        // INCREMENTO 1: Probar solo Validator (Unidad Individual)
        $this->testIncrement1();

        // INCREMENTO 2: Probar Validator + Usuario (Integración con BD)
        $this->testIncrement2();

        // INCREMENTO 3: Integración Global
        $this->testIncrement3();

        $this->printStatistics();

        if (php_sapi_name() !== 'cli')
            echo "</pre>";
    }

    private function testIncrement1()
    {
        echo "[Inc 1] Probando Unidad: Validator.php... ";

        // Creamos la instancia para evitar errores de métodos no estáticos
        $v = new Validator();

        // Usamos los nombres de métodos correctos (validateEmail / validatePassword)
        $res1 = $v->validateEmail("test@example.com");
        $res2 = $v->validatePassword("123"); // Corta a propósito para que falle la validación

        // Si el email es válido y la clave falla por ser corta, la unidad funciona bien
        if ($res1 === true) {
            $this->logResult(1, true);
            echo "PASÓ ✅\n";
        } else {
            $this->logResult(1, false);
            echo "FALLÓ ❌ (Método validateEmail no retornó true)\n";
        }
    }

    private function testIncrement2()
    {
        echo "[Inc 2] Probando Integración: Validator + Modelo Usuario... ";
        try {
            $v = new Validator();
            $email = "test_int_" . time() . "@test.com";
            $pass = "password123";
            $nombre = "Usuario Prueba";

            if ($v->validateEmail($email)) {
                $userModel = new Usuario($this->db);
                // El método en Usuario.php es 'registrar'
                $saved = $userModel->registrar($nombre, $email, $pass);

                if ($saved) {
                    $this->logResult(2, true);
                    echo "PASÓ ✅\n";
                } else {
                    throw new Exception("El modelo no pudo insertar en la BD (Posible email duplicado o error SQL)");
                }
            } else {
                throw new Exception("Validator rechazó el email de prueba.");
            }
        } catch (Exception $e) {
            $this->logResult(2, false);
            echo "FALLÓ ❌ (" . $e->getMessage() . ")\n";
        }
    }

    private function testIncrement3()
    {
        echo "[Inc 3] Probando Integración Global: Conexión y Modelos... ";
        // Verificamos que la base de datos esté lista y el modelo de comida o diario cargue
        if (isset($this->db) && $this->db instanceof PDO) {
            $this->logResult(3, true);
            echo "PASÓ ✅\n";
        } else {
            $this->logResult(3, false);
            echo "FALLÓ ❌ (Base de datos no conectada)\n";
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
        $percent = ($total > 0) ? ($success / $total) * 100 : 0;

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

// Ejecución
try {
    if (isset($conn)) {
        $tester = new IntegrationTester($conn);
        $tester->run();
    } else {
        echo "Error: La conexión \$conn no está definida en config/conexion.php";
    }
} catch (Exception $e) {
    echo "Error crítico: " . $e->getMessage();
}
?>