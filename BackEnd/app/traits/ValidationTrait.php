<?php
namespace App\Traits;

trait ValidationTrait {
    protected function validate($data, $rules) {
        $errors = [];
        
        foreach ($rules as $field => $rule) {
            if (!isset($data[$field])) {
                if (strpos($rule, 'required') !== false) {
                    $errors[$field][] = "Trường {$field} là bắt buộc";
                }
                continue;
            }

            $ruleArray = explode('|', $rule);
            foreach ($ruleArray as $singleRule) {
                $params = [];
                if (strpos($singleRule, ':') !== false) {
                    list($singleRule, $param) = explode(':', $singleRule);
                    $params = explode(',', $param);
                }

                switch ($singleRule) {
                    case 'required':
                        if (empty($data[$field])) {
                            $errors[$field][] = "Trường {$field} không được để trống";
                        }
                        break;
                    case 'email':
                        if (!filter_var($data[$field], FILTER_VALIDATE_EMAIL)) {
                            $errors[$field][] = "Email không hợp lệ";
                        }
                        break;
                    case 'min':
                        if (strlen($data[$field]) < $params[0]) {
                            $errors[$field][] = "Trường {$field} phải có ít nhất {$params[0]} ký tự";
                        }
                        break;
                    case 'max':
                        if (strlen($data[$field]) > $params[0]) {
                            $errors[$field][] = "Trường {$field} không được vượt quá {$params[0]} ký tự";
                        }
                        break;
                }
            }
        }

        return [
            'success' => empty($errors),
            'errors' => $errors
        ];
    }
}
