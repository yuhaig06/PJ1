<?php

namespace App\Exceptions;

class AuthorizationException extends \Exception {
    protected $errors;

    public function __construct($message = "Unauthorized access", $errors = [], $code = 403) {
        parent::__construct($message, $code);
        $this->errors = $errors;
    }

    public function getErrors() {
        return $this->errors;
    }
} 