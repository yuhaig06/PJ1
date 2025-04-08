<?php

namespace App\Exceptions;

class NotFoundException extends \Exception {
    protected $errors;

    public function __construct($message = "Resource not found", $errors = [], $code = 404) {
        parent::__construct($message, $code);
        $this->errors = $errors;
    }

    public function getErrors() {
        return $this->errors;
    }
} 