<?php
namespace App\Core;

class Validator {
    private $data;
    private $errors = [];

    public function __construct(array $data) {
        $this->data = $data;
    }

    public function validate(array $rules) {
        foreach ($rules as $field => $ruleSet) {
            $this->validateField($field, $ruleSet);
        }

        return [
            'success' => empty($this->errors),
            'data' => $this->data,
            'errors' => $this->errors
        ];
    }

    private function validateField($field, $rules) {
        // Skip validation if field is not required and empty
        if (!isset($this->data[$field]) && !$this->hasRule($rules, 'required')) {
            return;
        }

        $value = $this->data[$field] ?? null;

        foreach ($this->parseRules($rules) as $rule => $params) {
            if (!$this->validateRule($field, $value, $rule, $params)) {
                break;
            }
        }
    }

    private function validateRule($field, $value, $rule, $params) {
        switch ($rule) {
            case 'required':
                return $this->validateRequired($field, $value);
            case 'nullable':
                return true;
            case 'integer':
                return $this->validateInteger($field, $value);
            case 'min':
                return $this->validateMin($field, $value, $params[0]);
            case 'max': 
                return $this->validateMax($field, $value, $params[0]);
            default:
                return true;
        }
    }

    private function validateRequired($field, $value) {
        if ($value === null || $value === '') {
            $this->addError($field, 'Trường này là bắt buộc');
            return false;
        }
        return true;
    }

    private function validateInteger($field, $value) {
        if (!is_numeric($value) || $value != (int)$value) {
            $this->addError($field, 'Trường này phải là số nguyên');
            return false;
        }
        return true;
    }

    private function validateMin($field, $value, $min) {
        if ($value < $min) {
            $this->addError($field, "Giá trị tối thiểu là {$min}");
            return false;
        }
        return true;
    }

    private function validateMax($field, $value, $max) {
        if ($value > $max) {
            $this->addError($field, "Giá trị tối đa là {$max}");
            return false;
        }
        return true;
    }

    private function parseRules($rulesString) {
        $rules = [];
        foreach (explode('|', $rulesString) as $rule) {
            $parts = explode(':', $rule);
            $ruleName = $parts[0];
            $params = isset($parts[1]) ? explode(',', $parts[1]) : [];
            $rules[$ruleName] = $params;
        }
        return $rules;
    }

    private function hasRule($rules, $rule) {
        return strpos($rules, $rule) !== false;
    }

    private function addError($field, $message) {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        $this->errors[$field][] = $message;
    }
}
