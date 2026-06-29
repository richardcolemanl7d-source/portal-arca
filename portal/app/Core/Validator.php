<?php

namespace App\Core;

class Validator
{
    private array $data;
    private array $rules;
    private array $errors = [];
    private array $customMessages = [];

    private static array $defaultMessages = [
        'required' => 'El campo :field es obligatorio.',
        'email' => 'El campo :field debe ser una dirección de email válida.',
        'min' => 'El campo :field debe tener al menos :min caracteres.',
        'max' => 'El campo :field no puede exceder los :max caracteres.',
        'numeric' => 'El campo :field debe ser numérico.',
        'integer' => 'El campo :field debe ser un número entero.',
        'string' => 'El campo :field debe ser una cadena de texto.',
        'array' => 'El campo :field debe ser un array.',
        'confirmed' => 'La confirmación del campo :field no coincide.',
        'unique' => 'El campo :field ya está en uso.',
        'exists' => 'El campo :field seleccionado no es válido.',
        'date' => 'El campo :field debe ser una fecha válida.',
        'date_format' => 'El campo :field debe tener el formato :format.',
        'in' => 'El campo :field debe ser uno de los valores permitidos.',
        'not_in' => 'El campo :field seleccionado no es válido.',
        'url' => 'El campo :field debe ser una URL válida.',
        'alpha' => 'El campo :field solo puede contener letras.',
        'alpha_num' => 'El campo :field solo puede contener letras y números.',
        'between' => 'El campo :field debe estar entre :min y :max.',
        'file' => 'El campo :field debe ser un archivo.',
        'image' => 'El campo :field debe ser una imagen.',
        'mimes' => 'El campo :field debe ser un archivo de tipo: :values.',
        'max_file' => 'El archivo :field no puede pesar más de :max KB.',
    ];

    public function __construct(array $data, array $rules, array $messages = [])
    {
        $this->data = $data;
        $this->rules = $rules;
        $this->customMessages = $messages;
    }

    public static function make(array $data, array $rules, array $messages = []): self
    {
        return new self($data, $rules, $messages);
    }

    public function validate(): bool
    {
        foreach ($this->rules as $field => $ruleSet) {
            $rules = is_string($ruleSet) ? explode('|', $ruleSet) : $ruleSet;
            
            foreach ($rules as $rule) {
                $this->applyRule($field, $rule);
            }
        }

        return empty($this->errors);
    }

    private function applyRule(string $field, string $rule): void
    {
        $value = $this->data[$field] ?? null;
        
        // Parse rule with parameters (e.g., max:50)
        [$ruleName, $params] = $this->parseRule($rule);
        
        $method = 'validate' . ucfirst($ruleName);
        
        if (method_exists($this, $method)) {
            $result = $this->$method($field, $value, $params);
            
            if ($result !== true) {
                $this->addError($field, $ruleName, $params);
            }
        }
    }

    private function parseRule(string $rule): array
    {
        $parts = explode(':', $rule, 2);
        $ruleName = $parts[0];
        $params = isset($parts[1]) ? explode(',', $parts[1]) : [];
        
        return [$ruleName, $params];
    }

    private function validateRequired(string $field, mixed $value, array $params): bool
    {
        return $value !== null && $value !== '';
    }

    private function validateEmail(string $field, mixed $value, array $params): bool
    {
        if ($value === null || $value === '') {
            return true;
        }
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    private function validateMin(string $field, mixed $value, array $params): bool
    {
        if ($value === null || $value === '') {
            return true;
        }
        $min = (int) $params[0];
        return strlen((string) $value) >= $min;
    }

    private function validateMax(string $field, mixed $value, array $params): bool
    {
        if ($value === null || $value === '') {
            return true;
        }
        $max = (int) $params[0];
        return strlen((string) $value) <= $max;
    }

    private function validateNumeric(string $field, mixed $value, array $params): bool
    {
        if ($value === null || $value === '') {
            return true;
        }
        return is_numeric($value);
    }

    private function validateInteger(string $field, mixed $value, array $params): bool
    {
        if ($value === null || $value === '') {
            return true;
        }
        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }

    private function validateString(string $field, mixed $value, array $params): bool
    {
        if ($value === null || $value === '') {
            return true;
        }
        return is_string($value);
    }

    private function validateArray(string $field, mixed $value, array $params): bool
    {
        if ($value === null || $value === '') {
            return true;
        }
        return is_array($value);
    }

    private function validateConfirmed(string $field, mixed $value, array $params): bool
    {
        if ($value === null || $value === '') {
            return true;
        }
        $confirmationField = $field . '_confirmation';
        return isset($this->data[$confirmationField]) && 
               $this->data[$confirmationField] === $value;
    }

    private function validateUnique(string $field, mixed $value, array $params): bool
    {
        if ($value === null || $value === '') {
            return true;
        }
        $table = $params[0] ?? 'users';
        $column = $params[1] ?? $field;
        $excludeId = $params[2] ?? null;
        
        try {
            $db = Database::getInstance();
            
            $sql = "SELECT COUNT(*) as count FROM {$table} WHERE {$column} = ?";
            $whereParams = [$value];
            
            if ($excludeId) {
                $sql .= " AND id != ?";
                $whereParams[] = $excludeId;
            }
            
            $result = $db->fetch($sql, $whereParams);
            return $result['count'] == 0;
        } catch (\PDOException $e) {
            error_log("Validation unique error: " . $e->getMessage());
            return true;
        }
    }

    private function validateExists(string $field, mixed $value, array $params): bool
    {
        if ($value === null || $value === '') {
            return true;
        }
        $table = $params[0] ?? 'users';
        $column = $params[1] ?? $field;
        
        try {
            $db = Database::getInstance();
            $result = $db->fetch(
                "SELECT COUNT(*) as count FROM {$table} WHERE {$column} = ?",
                [$value]
            );
            return $result['count'] > 0;
        } catch (\PDOException $e) {
            error_log("Validation exists error: " . $e->getMessage());
            return true;
        }
    }

    private function validateDate(string $field, mixed $value, array $params): bool
    {
        if ($value === null || $value === '') {
            return true;
        }
        return strtotime((string) $value) !== false;
    }

    private function validateDateFormat(string $field, mixed $value, array $params): bool
    {
        if ($value === null || $value === '') {
            return true;
        }
        $format = $params[0] ?? 'Y-m-d';
        $dateTime = \DateTime::createFromFormat($format, (string) $value);
        return $dateTime && $dateTime->format($format) === (string) $value;
    }

    private function validateIn(string $field, mixed $value, array $params): bool
    {
        if ($value === null || $value === '') {
            return true;
        }
        return in_array($value, $params);
    }

    private function validateUrl(string $field, mixed $value, array $params): bool
    {
        if ($value === null || $value === '') {
            return true;
        }
        return filter_var($value, FILTER_VALIDATE_URL) !== false;
    }

    private function validateAlpha(string $field, mixed $value, array $params): bool
    {
        if ($value === null || $value === '') {
            return true;
        }
        return ctype_alpha((string) $value);
    }

    private function validateAlphaNum(string $field, mixed $value, array $params): bool
    {
        if ($value === null || $value === '') {
            return true;
        }
        return ctype_alnum((string) $value);
    }

    private function validateBetween(string $field, mixed $value, array $params): bool
    {
        if ($value === null || $value === '') {
            return true;
        }
        $min = (int) $params[0];
        $max = (int) $params[1];
        $length = strlen((string) $value);
        return $length >= $min && $length <= $max;
    }

    private function validateFile(string $field, mixed $value, array $params): bool
    {
        if (!isset($_FILES[$field])) {
            return false;
        }
        $file = $_FILES[$field];
        return $file['error'] === UPLOAD_ERR_OK;
    }

    private function validateImage(string $field, mixed $value, array $params): bool
    {
        if (!isset($_FILES[$field])) {
            return false;
        }
        $file = $_FILES[$field];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return false;
        }
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        return in_array($file['type'], $allowedTypes);
    }

    private function validateMimes(string $field, mixed $value, array $params): bool
    {
        if (!isset($_FILES[$field])) {
            return false;
        }
        $file = $_FILES[$field];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return false;
        }
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        return in_array($extension, $params);
    }

    private function validateMaxFile(string $field, mixed $value, array $params): bool
    {
        if (!isset($_FILES[$field])) {
            return false;
        }
        $file = $_FILES[$field];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return false;
        }
        $maxSize = (int) $params[0] * 1024; // Convert KB to bytes
        return $file['size'] <= $maxSize;
    }

    private function addError(string $field, string $rule, array $params): void
    {
        $messageKey = "{$field}.{$rule}";
        
        if (isset($this->customMessages[$messageKey])) {
            $message = $this->customMessages[$messageKey];
        } elseif (isset(self::$defaultMessages[$rule])) {
            $message = self::$defaultMessages[$rule];
        } else {
            $message = "El campo :field no es válido.";
        }
        
        // Replace placeholders
        $message = str_replace(':field', $this->getFieldName($field), $message);
        $message = str_replace(':min', $params[0] ?? '', $message);
        $message = str_replace(':max', $params[0] ?? '', $message);
        $message = str_replace(':format', $params[0] ?? '', $message);
        $message = str_replace(':values', implode(', ', $params), $message);
        
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        
        $this->errors[$field][] = $message;
    }

    private function getFieldName(string $field): string
    {
        // Convert snake_case to readable format
        return ucwords(str_replace('_', ' ', $field));
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function firstError(): ?string
    {
        foreach ($this->errors as $fieldErrors) {
            return $fieldErrors[0] ?? null;
        }
        return null;
    }

    public function fails(): bool
    {
        return !empty($this->errors);
    }

    public function passes(): bool
    {
        return empty($this->errors);
    }
}
