<?php

class Validator {
    private $errors = [];

    /**
     * Valida que los campos requeridos no estén vacíos
     */
    public function required($data, $fields) {
        foreach ($fields as $field) {
            if (!isset($data[$field]) || empty(trim($data[$field]))) {
                $this->addError($field, 'required');
            }
        }
        return $this;
    }

    /**
     * Valida el formato del email
     */
    public function email($email) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->addError('email', 'formato_invalido');
        }
        return $this;
    }

    /**
     * Valida la fuerza de la contraseña
     */
    public function password($password) {
        if (strlen($password) < 8 || 
            !preg_match('/[A-Za-z]/', $password) || 
            !preg_match('/[0-9]/', $password) || 
            !preg_match('/[^A-Za-z0-9]/', $password)) {
            $this->addError('password', 'password_insegura');
        }
        return $this;
    }

    /**
     * Valida que el valor sea numérico
     */
    public function numeric($value, $field) {
        if (!is_numeric($value)) {
            $this->addError($field, 'debe_ser_numero');
        }
        return $this;
    }

    /**
     * Valida un valor mínimo
     */
    public function min($value, $min, $field) {
        if ($value < $min) {
            $this->addError($field, 'valor_minimo');
        }
        return $this;
    }

    /**
     * Valida un valor máximo
     */
    public function max($value, $max, $field) {
        if ($value > $max) {
            $this->addError($field, 'valor_maximo');
        }
        return $this;
    }

    /**
     * Valida que dos valores coincidan (útil para confirmación de contraseña)
     */
    public function matches($value1, $value2, $field) {
        if ($value1 !== $value2) {
            $this->addError($field, 'no_coinciden');
        }
        return $this;
    }

    /**
     * Agrega un error a la lista
     */
    public function addError($field, $type) {
        $this->errors[$field] = $type;
    }

    /**
     * Verifica si hay errores
     */
    public function hasErrors() {
        return !empty($this->errors);
    }

    /**
     * Obtiene el primer error para redirección simple
     */
    public function getFirstError() {
        if ($this->hasErrors()) {
            return reset($this->errors);
        }
        return null;
    }

    /**
     * Obtiene todos los errores
     */
    public function getErrors() {
        return $this->errors;
    }
}
