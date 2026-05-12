<?php
require_once __DIR__ . '/../utils/Validator.php';

function testValidator() {
    $validator = new Validator();
    
    echo "--- Ejecutando Pruebas de Validator ---\n";

    // Test 1: Email Inválido
    $validator->email('correo-invalido');
    if ($validator->hasErrors() && $validator->getErrors()['email'] === 'formato_invalido') {
        echo "✅ Test 1 Pasado: Email inválido detectado.\n";
    } else {
        echo "❌ Test 1 Fallido: No se detectó email inválido.\n";
    }

    $validator = new Validator();
    // Test 2: Password Insegura
    $validator->password('12345');
    if ($validator->hasErrors() && $validator->getErrors()['password'] === 'password_insegura') {
        echo "✅ Test 2 Pasado: Password insegura detectada.\n";
    } else {
        echo "❌ Test 2 Fallido: No se detectó password insegura.\n";
    }

    $validator = new Validator();
    // Test 3: Campos Requeridos
    $validator->required(['nombre' => ''], ['nombre', 'email']);
    if ($validator->hasErrors() && count($validator->getErrors()) === 2) {
        echo "✅ Test 3 Pasado: Campos requeridos vacíos detectados.\n";
    } else {
        echo "❌ Test 3 Fallido: No se detectaron campos vacíos.\n";
    }

    $validator = new Validator();
    // Test 4: Datos Válidos
    $validator->email('test@ejemplo.com')->password('Pass123!')->required(['nombre' => 'Juan'], ['nombre']);
    if (!$validator->hasErrors()) {
        echo "✅ Test 4 Pasado: Datos válidos aceptados.\n";
    } else {
        echo "❌ Test 4 Fallido: Datos válidos rechazados.\n";
    }

    $validator = new Validator();
    // Test 5: Validación Numérica y Rangos
    $validator->numeric('abc', 'edad')->min(10, 18, 'peso')->max(200, 100, 'altura');
    $errors = $validator->getErrors();
    if (isset($errors['edad']) && $errors['edad'] === 'debe_ser_numero' &&
        isset($errors['peso']) && $errors['peso'] === 'valor_minimo' &&
        isset($errors['altura']) && $errors['altura'] === 'valor_maximo') {
        echo "✅ Test 5 Pasado: Validaciones numéricas y rangos detectadas.\n";
    } else {
        echo "❌ Test 5 Fallido: Error en validaciones numéricas.\n";
    }
}

testValidator();
