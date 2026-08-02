<?php

namespace App\Core;

/**
 * Small Laravel-flavoured validator.
 *
 * Usage:
 *   $v = new Validator($_POST, [
 *       'email'    => 'required|email',
 *       'password' => 'required|min:8',
 *       'age'      => 'nullable|numeric',
 *   ]);
 *   if ($v->fails()) { ... $v->errors() ... }
 */
class Validator
{
    private array $data;
    private array $rules;
    private array $errors = [];
    private array $niceNames;

    public function __construct(array $data, array $rules, array $niceNames = [])
    {
        $this->data = $data;
        $this->rules = $rules;
        $this->niceNames = $niceNames;
    }

    public function fails(): bool
    {
        foreach ($this->rules as $field => $ruleString) {
            $rulesForField = is_array($ruleString) ? $ruleString : explode('|', $ruleString);
            $value = $this->data[$field] ?? null;

            $nullable = in_array('nullable', $rulesForField, true);
            if ($nullable && ($value === null || $value === '')) {
                continue;
            }

            foreach ($rulesForField as $rule) {
                if ($rule === 'nullable') {
                    continue;
                }
                $this->applyRule($field, $value, $rule);
            }
        }
        return !empty($this->errors);
    }

    public function passes(): bool
    {
        return !$this->fails();
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

    private function applyRule(string $field, mixed $value, string $rule): void
    {
        $param = null;
        if (str_contains($rule, ':')) {
            [$rule, $param] = explode(':', $rule, 2);
        }

        $label = $this->niceNames[$field] ?? ucfirst(str_replace('_', ' ', $field));
        $valid = true;
        $message = '';

        switch ($rule) {
            case 'required':
                $valid = !($value === null || $value === '' || (is_array($value) && empty($value)));
                $message = "{$label} is required.";
                break;

            case 'email':
                $valid = filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
                $message = "{$label} must be a valid email address.";
                break;

            case 'numeric':
                $valid = is_numeric($value);
                $message = "{$label} must be a number.";
                break;

            case 'integer':
                $valid = filter_var($value, FILTER_VALIDATE_INT) !== false;
                $message = "{$label} must be an integer.";
                break;

            case 'min':
                if (is_string($value)) {
                    $valid = mb_strlen($value) >= (int) $param;
                    $message = "{$label} must be at least {$param} characters.";
                } else {
                    $valid = (float) $value >= (float) $param;
                    $message = "{$label} must be at least {$param}.";
                }
                break;

            case 'max':
                if (is_string($value)) {
                    $valid = mb_strlen($value) <= (int) $param;
                    $message = "{$label} must not exceed {$param} characters.";
                } else {
                    $valid = (float) $value <= (float) $param;
                    $message = "{$label} must not exceed {$param}.";
                }
                break;

            case 'in':
                $options = explode(',', (string) $param);
                $valid = in_array((string) $value, $options, true);
                $message = "{$label} must be one of: " . implode(', ', $options) . '.';
                break;

            case 'date':
                $valid = $value instanceof \DateTimeInterface || strtotime((string) $value) !== false;
                $message = "{$label} must be a valid date.";
                break;

            case 'confirmed':
                $confirmField = $field . '_confirmation';
                $valid = isset($this->data[$confirmField]) && $this->data[$confirmField] === $value;
                $message = "{$label} confirmation does not match.";
                break;

            case 'same':
                $valid = ($this->data[$param] ?? null) === $value;
                $message = "{$label} must match " . ($this->niceNames[$param] ?? $param) . '.';
                break;

            case 'unique':
                // param format: table,column[,ignoreId]
                $parts = explode(',', (string) $param);
                $table = $parts[0] ?? null;
                $column = $parts[1] ?? $field;
                $ignoreId = $parts[2] ?? null;
                $valid = $this->isUnique($table, $column, $value, $ignoreId);
                $message = "{$label} is already taken.";
                break;

            case 'exists':
                $parts = explode(',', (string) $param);
                $table = $parts[0] ?? null;
                $column = $parts[1] ?? 'id';
                $valid = $this->exists($table, $column, $value);
                $message = "Selected {$label} is invalid.";
                break;

            case 'alpha_num':
                $valid = ctype_alnum(str_replace(['_', '-', ' '], '', (string) $value));
                $message = "{$label} may only contain letters, numbers, dashes and spaces.";
                break;

            case 'regex':
                $valid = preg_match($param, (string) $value) === 1;
                $message = "{$label} format is invalid.";
                break;

            case 'phone':
                $valid = preg_match('/^[0-9+\-\s()]{7,20}$/', (string) $value) === 1;
                $message = "{$label} must be a valid phone number.";
                break;

            default:
                // Unknown rule name — ignore rather than false-fail the form.
                return;
        }

        if (!$valid) {
            $this->errors[$field][] = $message;
        }
    }

    private function isUnique(?string $table, string $column, mixed $value, ?string $ignoreId): bool
    {
        if (!$table) {
            return true;
        }
        $db = Database::getInstance();
        $sql = "SELECT COUNT(*) AS c FROM `{$table}` WHERE `{$column}` = :value";
        $params = ['value' => $value];
        if ($ignoreId !== null && $ignoreId !== '') {
            $sql .= ' AND `id` != :ignore_id';
            $params['ignore_id'] = $ignoreId;
        }
        $row = $db->query($sql, $params)->fetch();
        return (int) ($row['c'] ?? 0) === 0;
    }

    private function exists(?string $table, string $column, mixed $value): bool
    {
        if (!$table || $value === null || $value === '') {
            return true;
        }
        $db = Database::getInstance();
        $sql = "SELECT COUNT(*) AS c FROM `{$table}` WHERE `{$column}` = :value";
        $row = $db->query($sql, ['value' => $value])->fetch();
        return (int) ($row['c'] ?? 0) > 0;
    }
}
