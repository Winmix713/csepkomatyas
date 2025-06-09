<?php

declare(strict_types=1);

namespace FootballAPI\Services;

/**
 * Service for match predictions and analysis
 */
class PredictionService
{
    private StatisticsService $statisticsService;

    public function __construct()
    {
        $this->statisticsService = new StatisticsService();
    }

    /**
     * Run comprehensive prediction analysis
     */
    public function runPrediction(string $homeTeam, string $awayTeam, array $matches): array
    {
        $headToHeadMatches = $this->getHeadToHeadMatches($matches, $homeTeam, $awayTeam);
        
        return [
            'winner_prediction' => $this->predictWinner($homeTeam, $awayTeam, $headToHeadMatches),
            'expected_goals' => [
                'home' => $this->calculateExpectedGoals($homeTeam, $matches),
                'away' => $this->calculateExpectedGoals($awayTeam, $matches)
            ],
            'both_teams_to_score_probability' => $this->statisticsService->calculateBothTeamsToScoreProb($headToHeadMatches),
            'form_analysis' => [
                'home_form' => $this->statisticsService->calculateFormIndex($matches, $homeTeam),
                'away_form' => $this->statisticsService->calculateFormIndex($matches, $awayTeam)
            ],
            'head_to_head_stats' => $this->statisticsService->calculateHeadToHeadStats($headToHeadMatches),
            'confidence_level' => $this->calculateConfidenceLevel($headToHeadMatches)
        ];
    }

    /**
     * Predict the winner based on head-to-head history
     */
    public function predictWinner(string $homeTeam, string $awayTeam, array $matches): array
    {
        if (empty($matches)) {
            return [
                'predicted_winner' => 'insufficient_data',
                'confidence' => 0.0,
                'reasoning' => 'No historical data available for these teams'
            ];
        }

        $stats = $this->statisticsService->calculateHeadToHeadStats($matches);
        
        // Determine the most likely outcome
        $outcomes = [
            'home_win' => $stats['home_win_percentage'],
            'away_win' => $stats['away_win_percentage'],
            'draw' => $stats['draw_percentage']
        ];

        $maxPercentage = max($outcomes);
        $predictedOutcome = array_search($maxPercentage, $outcomes);

        $winner = match ($predictedOutcome) {
            'home_win' => $homeTeam,
            'away_win' => $awayTeam,
            'draw' => 'draw',
            default => 'uncertain'
        };

        return [
            'predicted_winner' => $winner,
            'confidence' => $maxPercentage,
            'reasoning' => $this->generatePredictionReasoning($stats, $winner, count($matches))
        ];
    }

    /**
     * Calculate expected goals for a team
     */
    public function calculateExpectedGoals(string $team, array $matches): float
    {
        if (empty($team)) {
            return 0.0;
        }

        $teamMatches = array_filter($matches, function (array $match) use ($team): bool {
            return (isset($match['home_team']) && strcasecmp($match['home_team'], $team) === 0) ||
                   (isset($match['away_team']) && strcasecmp($match['away_team'], $team) === 0);
        });

        if (empty($teamMatches)) {
            return 0.0;
        }

        $totalGoals = array_reduce($teamMatches, function (int $sum, array $match) use ($team): int {
            if (!isset($match['score']['home']) || !isset($match['score']['away'])) {
                return $sum;
            }

            $isHomeTeam = isset($match['home_team']) && strcasecmp($match['home_team'], $team) === 0;
            return $sum + ($isHomeTeam ? (int)$match['score']['home'] : (int)$match['score']['away']);
        }, 0);

        return round($totalGoals / count($teamMatches), 2);
    }

    /**
     * Calculate confidence level based on available data
     */
    private function calculateConfidenceLevel(array $matches): float
    {
        $matchCount = count($matches);
        
        // More matches = higher confidence (up to a maximum)
        if ($matchCount === 0) {
            return 0.0;
        } elseif ($matchCount < 3) {
            return 30.0;
        } elseif ($matchCount < 5) {
            return 50.0;
        } elseif ($matchCount < 10) {
            return 70.0;
        } else {
            return 85.0;
        }
    }

    /**
     * Generate reasoning text for prediction
     */
    private function generatePredictionReasoning(array $stats, string $winner, int $matchCount): string
    {
        if ($matchCount === 0) {
            return 'No historical matches available between these teams';
        }

        $reasoning = "Based on {$matchCount} historical match";
        if ($matchCount > 1) {
            $reasoning .= 'es';
        }

        if ($winner === 'draw') {
            $reasoning .= ", draws are most common ({$stats['draw_percentage']}% of matches)";
        } elseif ($winner !== 'uncertain') {
            $winPercentage = ($winner === $stats['home_wins'] > $stats['away_wins']) 
                ? $stats['home_win_percentage'] 
                : $stats['away_win_percentage'];
            $reasoning .= ", {$winner} has won {$winPercentage}% of previous encounters";
        }

        return $reasoning;
    }

    /**
     * Get head-to-head matches between two teams
     */
    private function getHeadToHeadMatches(array $matches, string $team1, string $team2): array
    {
        return array_filter($matches, function (array $match) use ($team1, $team2): bool {
            $homeTeam = $match['home_team'] ?? '';
            $awayTeam = $match['away_team'] ?? '';

            return (strcasecmp($homeTeam, $team1) === 0 && strcasecmp($awayTeam, $team2) === 0) ||
                   (strcasecmp($homeTeam, $team2) === 0 && strcasecmp($awayTeam, $team1) === 0);
        });
    }
}
