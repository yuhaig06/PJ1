<?php

namespace App\Exceptions;

class PaymentException extends \Exception {
    protected $errors;

    public function __construct($message = "Payment failed", $errors = [], $code = 400) {
        parent::__construct($message, $code);
        $this->errors = $errors;
    }

    public function getErrors() {
        return $this->errors;
    }
} 