<?php

declare(strict_types=1);

namespace FootballAPI\Exceptions;

/**
 * Exception thrown when requested data is not found
 */
class DataNotFoundException extends FootballAPIException
{
    public function __construct(string $message = 'Requested data not found', int $code = 404, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function getUserMessage(): string
    {
        return 'The requested data could not be found. Please check your parameters and try again.';
    }
}
