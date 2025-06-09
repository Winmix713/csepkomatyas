<?php

declare(strict_types=1);

namespace FootballAPI\Exceptions;

/**
 * Exception thrown when input validation fails
 */
class ValidationException extends FootballAPIException
{
    private array $errors = [];

    public function __construct(string $message = 'Validation failed', array $errors = [], int $code = 422, ?\Throwable $previous = null)
    {
        $this->errors = $errors;
        parent::__construct($message, $code, $previous);
    }

    /**
     * Get validation errors
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Add validation error
     */
    public function addError(string $field, string $message): void
    {
        $this->errors[$field] = $message;
    }

    /**
     * Check if there are validation errors
     */
    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    public function getUserMessage(): string
    {
        if (!empty($this->errors)) {
            return 'Validation failed: ' . implode(', ', $this->errors);
        }
        
        return parent::getUserMessage();
    }

    public function toArray(): array
    {
        $array = parent::toArray();
        $array['validation_errors'] = $this->errors;
        return $array;
    }
}
