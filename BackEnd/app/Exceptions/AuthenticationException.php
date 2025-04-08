<?php

namespace App\Exceptions;

class AuthenticationException extends \Exception {
    protected $errors;

    public function __construct($message = "Authentication failed", $errors = [], $code = 401) {
        parent::__construct($message, $code);
        $this->errors = $errors;
    }

    public function getErrors() {
        return $this->errors;
    }
} 