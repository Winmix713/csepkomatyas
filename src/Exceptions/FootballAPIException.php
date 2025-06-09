<?php

declare(strict_types=1);

namespace FootballAPI\Exceptions;

/**
 * Base exception class for Football API
 */
class FootballAPIException extends \Exception
{
    public function __construct(string $message = '', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Get user-friendly error message
     */
    public function getUserMessage(): string
    {
        return $this->getMessage() ?: 'An error occurred while processing your request';
    }

    /**
     * Get error context for logging
     */
    public function getContext(): array
    {
        return [
            'exception_class' => static::class,
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'file' => $this->getFile(),
            'line' => $this->getLine(),
            'trace' => $this->getTraceAsString()
        ];
    }

    /**
     * Convert exception to array for API response
     */
    public function toArray(): array
    {
        return [
            'error' => true,
            'message' => $this->getUserMessage(),
            'code' => $this->getCode(),
            'type' => static::class
        ];
    }
}
