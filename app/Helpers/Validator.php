<?php

namespace App\Helpers;

class Validator
{
    private array $data;
    private array $errors = [];

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Check if fields are present and not empty.
     *
     * @param array $fields
     * @return $this
     */
    public function required(array $fields): self
    {
        foreach ($fields as $field) {
            if (!isset($this->data[$field]) || (is_string($this->data[$field]) && trim($this->data[$field]) === '')) {
                $this->errors[$field][] = "The field '" . str_replace('_', ' ', $field) . "' is required.";
            }
        }
        return $this;
    }

    /**
     * Validate email format.
     *
     * @param string $field
     * @return $this
     */
    public function email(string $field): self
    {
        if (isset($this->data[$field]) && !empty($this->data[$field])) {
            if (!filter_var($this->data[$field], FILTER_VALIDATE_EMAIL)) {
                $this->errors[$field][] = "The field '{$field}' must be a valid email address.";
            }
        }
        return $this;
    }

    /**
     * Validate mobile number format.
     *
     * @param string $field
     * @return $this
     */
    public function mobile(string $field): self
    {
        if (isset($this->data[$field]) && !empty($this->data[$field])) {
            // Check if mobile matches basic phone number regex
            if (!preg_match('/^\+?[0-9]{7,15}$/', $this->data[$field])) {
                $this->errors[$field][] = "The field '{$field}' must be a valid mobile number (7-15 digits, optionally starting with +).";
            }
        }
        return $this;
    }

    /**
     * Validate value is within a set of enum values.
     *
     * @param string $field
     * @param array $allowedValues
     * @return $this
     */
    public function enum(string $field, array $allowedValues): self
    {
        if (isset($this->data[$field]) && !empty($this->data[$field])) {
            if (!in_array($this->data[$field], $allowedValues, true)) {
                $this->errors[$field][] = "The field '{$field}' must be one of the following: " . implode(', ', $allowedValues) . ".";
            }
        }
        return $this;
    }

    /**
     * Validate value is numeric.
     *
     * @param string $field
     * @return $this
     */
    public function numeric(string $field): self
    {
        if (isset($this->data[$field]) && !empty($this->data[$field])) {
            if (!is_numeric($this->data[$field])) {
                $this->errors[$field][] = "The field '{$field}' must be a numeric value.";
            }
        }
        return $this;
    }

    /**
     * Validate date format (YYYY-MM-DD).
     *
     * @param string $field
     * @return $this
     */
    public function date(string $field): self
    {
        if (isset($this->data[$field]) && !empty($this->data[$field])) {
            $date = $this->data[$field];
            $d = \DateTime::createFromFormat('Y-m-d', $date);
            if (!$d || $d->format('Y-m-d') !== $date) {
                $this->errors[$field][] = "The field '{$field}' must be a valid date in YYYY-MM-DD format.";
            }
        }
        return $this;
    }

    /**
     * Check if validation passed.
     *
     * @return bool
     */
    public function passes(): bool
    {
        return empty($this->errors);
    }

    /**
     * Get validation errors.
     *
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
