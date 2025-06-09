<?php

declare(strict_types=1);

namespace FootballAPI;

use FootballAPI\Services\MatchService;
use FootballAPI\Services\StatisticsService;
use FootballAPI\Services\PredictionService;
use FootballAPI\Utils\Validator;
use FootballAPI\Utils\ResponseFormatter;
use FootballAPI\Exceptions\FootballAPIException;

/**
 * Main Football API class that coordinates all services
 */
class FootballAPI
{
    private const DEFAULT_PAGE_SIZE = 100;
    private const MAX_PAGE_SIZE = 500;

    private MatchService $matchService;
    private StatisticsService $statisticsService;
    private PredictionService $predictionService;

    public function __construct()
    {
        $this->matchService = new MatchService();
        $this->statisticsService = new StatisticsService();
        $this->predictionService = new PredictionService();
    }

    /**
     * Process the incoming API request
     */
    public function processRequest(): array
    {
        try {
            // Validate and sanitize request parameters
            $params = Validator::validateAndSanitizeParams($_GET);
            
            // Get filtered matches
            $filteredMatches = $this->matchService->getFilteredMatches($params);
            
            // Sort matches by date (descending)
            $sortedMatches = $this->sortMatchesByDate($filteredMatches);
            
            // Apply pagination
            $paginationInfo = $this->getPaginationInfo($params, count($sortedMatches));
            $paginatedMatches = $this->applyPagination($sortedMatches, $paginationInfo);
            
            // Build response structure
            $response = [
                'success' => true,
                'data' => [
                    'matches' => $paginatedMatches,
                    'pagination' => $paginationInfo,
                    'statistics' => $this->statisticsService->calculateMatchStatistics($filteredMatches),
                    'available_teams' => $this->matchService->getAvailableTeams()
                ]
            ];

            // Add prediction analysis if both teams are specified
            if (isset($params['home_team']) && isset($params['away_team'])) {
                $response['data']['prediction'] = $this->predictionService->runPrediction(
                    $params['home_team'],
                    $params['away_team'],
                    $filteredMatches
                );
            }

            return $response;

        } catch (FootballAPIException $e) {
            return ResponseFormatter::createErrorResponse($e->getMessage(), $e->getCode());
        } catch (\Exception $e) {
            return ResponseFormatter::createErrorResponse(
                'An unexpected error occurred: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Sort matches by date in descending order
     */
    private function sortMatchesByDate(array $matches): array
    {
        usort($matches, function ($a, $b) {
            $dateA = isset($a['date']) ? strtotime($a['date']) : 0;
            $dateB = isset($b['date']) ? strtotime($b['date']) : 0;
            return $dateB <=> $dateA;
        });

        return $matches;
    }

    /**
     * Get pagination information
     */
    private function getPaginationInfo(array $params, int $totalMatches): array
    {
        $page = max(1, (int)($params['page'] ?? 1));
        $pageSize = min(
            self::MAX_PAGE_SIZE,
            max(1, (int)($params['page_size'] ?? self::DEFAULT_PAGE_SIZE))
        );

        $totalPages = (int)ceil($totalMatches / $pageSize);
        $offset = ($page - 1) * $pageSize;

        return [
            'current_page' => $page,
            'page_size' => $pageSize,
            'total_pages' => $totalPages,
            'total_matches' => $totalMatches,
            'has_next_page' => $page < $totalPages,
            'has_previous_page' => $page > 1
        ];
    }

    /**
     * Apply pagination to matches
     */
    private function applyPagination(array $matches, array $paginationInfo): array
    {
        $offset = ($paginationInfo['current_page'] - 1) * $paginationInfo['page_size'];
        return array_slice($matches, $offset, $paginationInfo['page_size']);
    }
}
