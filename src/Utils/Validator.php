<?php

declare(strict_types=1);

namespace FootballAPI\Utils;

use FootballAPI\Exceptions\ValidationException;

/**
 * Utility class for input validation and sanitization
 */
class Validator
{
    private const ALLOWED_PARAMS = [
        'team', 'home_team', 'away_team', 'date', 'score_home', 'score_away',
        'both_teams_scored', 'page', 'page_size', 'season', 'competition'
    ];

    /**
     * Validate and sanitize request parameters
     */
    public static function validateAndSanitizeParams(array $params): array
    {
        $sanitized = [];
        
        foreach ($params as $key => $value) {
            // Skip empty values
            if ($value === '' || $value === null) {
                continue;
            }
            
            // Validate parameter key
            if (!in_array($key, self::ALLOWED_PARAMS, true)) {
                continue; // Skip unknown parameters
            }
            
            // Sanitize the value based on parameter type
            $sanitizedValue = self::sanitizeParameter($key, $value);
            
            if ($sanitizedValue !== null) {
                $sanitized[$key] = $sanitizedValue;
            }
        }
        
        return $sanitized;
    }

    /**
     * Sanitize individual parameter based on its type
     */
    private static function sanitizeParameter(string $key, mixed $value): ?string
    {
        $stringValue = (string)$value;
        
        return match ($key) {
            'page', 'page_size', 'score_home', 'score_away' => self::sanitizeInteger($stringValue),
            'date' => self::sanitizeDate($stringValue),
            'both_teams_scored' => self::sanitizeBoolean($stringValue),
            'team', 'home_team', 'away_team', 'season', 'competition' => self::sanitizeString($stringValue),
            default => self::sanitizeString($stringValue)
        };
    }

    /**
     * Sanitize integer values
     */
    private static function sanitizeInteger(string $value): ?string
    {
        $filtered = filter_var($value, FILTER_VALIDATE_INT);
        return $filtered !== false && $filtered >= 0 ? (string)$filtered : null;
    }

    /**
     * Sanitize date values
     */
    private static function sanitizeDate(string $value): ?string
    {
        try {
            $date = new \DateTime($value);
            return $date->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Sanitize boolean values
     */
    private static function sanitizeBoolean(string $value): ?string
    {
        $lowerValue = strtolower(trim($value));
        
        if (in_array($lowerValue, ['true', '1', 'yes'], true)) {
            return 'true';
        }
        
        if (in_array($lowerValue, ['false', '0', 'no'], true)) {
            return 'false';
        }
        
        return null;
    }

    /**
     * Sanitize string values
     */
    private static function sanitizeString(string $value): ?string
    {
        // Remove HTML tags and encode special characters
        $sanitized = htmlspecialchars(strip_tags(trim($value)), ENT_QUOTES, 'UTF-8');
        
        // Return null if empty after sanitization
        return $sanitized !== '' ? $sanitized : null;
    }

    /**
     * Validate team name format
     */
    public static function validateTeamName(string $teamName): bool
    {
        return !empty(trim($teamName)) && 
               strlen($teamName) <= 100 && 
               preg_match('/^[a-zA-Z0-9\s\-\.]+$/', $teamName);
    }

    /**
     * Validate pagination parameters
     */
    public static function validatePagination(int $page, int $pageSize): array
    {
        $errors = [];
        
        if ($page < 1) {
            $errors[] = 'Page number must be greater than 0';
        }
        
        if ($pageSize < 1) {
            $errors[] = 'Page size must be greater than 0';
        }
        
        if ($pageSize > 500) {
            $errors[] = 'Page size cannot exceed 500';
        }
        
        if (!empty($errors)) {
            throw new ValidationException('Pagination validation failed: ' . implode(', ', $errors));
        }
        
        return ['page' => $page, 'page_size' => $pageSize];
    }
}
