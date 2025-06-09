<?php

declare(strict_types=1);

namespace FootballAPI\Utils;

/**
 * Utility class for formatting API responses
 */
class ResponseFormatter
{
    /**
     * Format successful response
     */
    public static function formatResponse(array $data): string
    {
        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Format error response
     */
    public static function formatError(string $message, int $code = 400): string
    {
        http_response_code($code);
        
        $response = [
            'success' => false,
            'error' => [
                'message' => $message,
                'code' => $code,
                'timestamp' => date('c')
            ]
        ];
        
        return json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Create error response array
     */
    public static function createErrorResponse(string $message, int $code = 400): array
    {
        return [
            'success' => false,
            'error' => [
                'message' => $message,
                'code' => $code,
                'timestamp' => date('c')
            ]
        ];
    }

    /**
     * Create success response array
     */
    public static function createSuccessResponse(array $data): array
    {
        return [
            'success' => true,
            'data' => $data,
            'timestamp' => date('c')
        ];
    }

    /**
     * Format match data for API response
     */
    public static function formatMatch(array $match): array
    {
        return [
            'id' => $match['id'] ?? null,
            'date' => $match['date'] ?? null,
            'home_team' => $match['home_team'] ?? null,
            'away_team' => $match['away_team'] ?? null,
            'score' => [
                'home' => isset($match['score']['home']) ? (int)$match['score']['home'] : null,
                'away' => isset($match['score']['away']) ? (int)$match['score']['away'] : null
            ],
            'competition' => $match['competition'] ?? null,
            'season' => $match['season'] ?? null
        ];
    }

    /**
     * Format multiple matches for API response
     */
    public static function formatMatches(array $matches): array
    {
        return array_map([self::class, 'formatMatch'], $matches);
    }

    /**
     * Add metadata to response
     */
    public static function addMetadata(array $response, array $metadata): array
    {
        $response['metadata'] = array_merge([
            'generated_at' => date('c'),
            'api_version' => '1.0'
        ], $metadata);
        
        return $response;
    }
}
