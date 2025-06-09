<?php

declare(strict_types=1);

namespace FootballAPI\Services;

/**
 * Service for calculating match statistics
 */
class StatisticsService
{
    /**
     * Calculate comprehensive match statistics
     */
    public function calculateMatchStatistics(array $matches): array
    {
        if (empty($matches)) {
            return $this->getEmptyStatistics();
        }

        return [
            'both_teams_scored_percentage' => $this->calculateBothTeamsScoredPercentage($matches),
            'average_goals' => $this->calculateAverageGoals($matches),
            'head_to_head' => $this->calculateHeadToHeadStats($matches)
        ];
    }

    /**
     * Calculate percentage of matches where both teams scored
     */
    public function calculateBothTeamsScoredPercentage(array $matches): float
    {
        $matchCount = count($matches);
        if ($matchCount === 0) {
            return 0.0;
        }

        $bothTeamsScoredCount = array_reduce($matches, function (int $count, array $match): int {
            if (!isset($match['score']['home']) || !isset($match['score']['away'])) {
                return $count;
            }
            return $count + (($match['score']['home'] > 0 && $match['score']['away'] > 0) ? 1 : 0);
        }, 0);

        return round(($bothTeamsScoredCount / $matchCount) * 100, 2);
    }

    /**
     * Calculate average goals statistics
     */
    public function calculateAverageGoals(array $matches): array
    {
        if (empty($matches)) {
            return [
                'average_total_goals' => 0.0,
                'average_home_goals' => 0.0,
                'average_away_goals' => 0.0
            ];
        }

        $totalMatches = count($matches);
        $goals = array_reduce($matches, function (array $acc, array $match): array {
            $homeGoals = isset($match['score']['home']) ? (int)$match['score']['home'] : 0;
            $awayGoals = isset($match['score']['away']) ? (int)$match['score']['away'] : 0;
            
            return [
                'total' => $acc['total'] + $homeGoals + $awayGoals,
                'home' => $acc['home'] + $homeGoals,
                'away' => $acc['away'] + $awayGoals
            ];
        }, ['total' => 0, 'home' => 0, 'away' => 0]);

        return [
            'average_total_goals' => round($goals['total'] / $totalMatches, 2),
            'average_home_goals' => round($goals['home'] / $totalMatches, 2),
            'average_away_goals' => round($goals['away'] / $totalMatches, 2)
        ];
    }

    /**
     * Calculate team form index based on recent games
     */
    public function calculateFormIndex(array $matches, string $team, int $recentGames = 5): float
    {
        if (empty($team)) {
            return 0.0;
        }
        
        $teamMatches = array_values(array_filter($matches, function (array $match) use ($team): bool {
            return 
                (isset($match['home_team']) && strcasecmp($match['home_team'], $team) === 0) || 
                (isset($match['away_team']) && strcasecmp($match['away_team'], $team) === 0);
        }));

        if (empty($teamMatches)) {
            return 0.0;
        }

        $recentMatches = array_slice($teamMatches, 0, min(count($teamMatches), $recentGames));
        $points = array_reduce($recentMatches, function (int $sum, array $match) use ($team): int {
            if (!isset($match['home_team']) || !isset($match['away_team']) || 
                !isset($match['score']['home']) || !isset($match['score']['away'])) {
                return $sum;
            }
            
            $isHomeTeam = strcasecmp($match['home_team'], $team) === 0;
            $homeScore = (int)$match['score']['home'];
            $awayScore = (int)$match['score']['away'];

            if ($isHomeTeam) {
                if ($homeScore > $awayScore) return $sum + 3;
                if ($homeScore === $awayScore) return $sum + 1;
            } else {
                if ($awayScore > $homeScore) return $sum + 3;
                if ($homeScore === $awayScore) return $sum + 1;
            }

            return $sum;
        }, 0);

        $maxPossiblePoints = count($recentMatches) * 3;
        return $maxPossiblePoints > 0 ? round(($points / $maxPossiblePoints) * 100, 2) : 0.0;
    }

    /**
     * Calculate head-to-head statistics between teams
     */
    public function calculateHeadToHeadStats(array $matches): array
    {
        if (empty($matches)) {
            return $this->getEmptyHeadToHeadStats();
        }

        $validMatches = array_filter($matches, function (array $match): bool {
            return isset($match['score']['home']) && isset($match['score']['away']);
        });
        
        $totalMatches = count($validMatches);
        if ($totalMatches === 0) {
            return $this->getEmptyHeadToHeadStats();
        }

        $stats = array_reduce($validMatches, function (array $acc, array $match): array {
            $homeScore = (int)$match['score']['home'];
            $awayScore = (int)$match['score']['away'];
            
            if ($homeScore > $awayScore) {
                $acc['home_wins']++;
            } elseif ($homeScore < $awayScore) {
                $acc['away_wins']++;
            } else {
                $acc['draws']++;
            }
            return $acc;
        }, ['home_wins' => 0, 'away_wins' => 0, 'draws' => 0]);

        return [
            'home_wins' => $stats['home_wins'],
            'away_wins' => $stats['away_wins'],
            'draws' => $stats['draws'],
            'home_win_percentage' => round(($stats['home_wins'] / $totalMatches) * 100, 2),
            'away_win_percentage' => round(($stats['away_wins'] / $totalMatches) * 100, 2),
            'draw_percentage' => round(($stats['draws'] / $totalMatches) * 100, 2)
        ];
    }

    /**
     * Calculate both teams to score probability
     */
    public function calculateBothTeamsToScoreProb(array $matches): float
    {
        if (empty($matches)) {
            return 0.0;
        }

        $validMatches = array_filter($matches, function (array $match): bool {
            return isset($match['score']['home']) && isset($match['score']['away']);
        });

        if (empty($validMatches)) {
            return 0.0;
        }

        $bothScoredCount = array_reduce($validMatches, function (int $count, array $match): int {
            return $count + (($match['score']['home'] > 0 && $match['score']['away'] > 0) ? 1 : 0);
        }, 0);

        return round(($bothScoredCount / count($validMatches)) * 100, 2);
    }

    /**
     * Get empty statistics structure
     */
    private function getEmptyStatistics(): array
    {
        return [
            'both_teams_scored_percentage' => 0.0,
            'average_goals' => [
                'average_total_goals' => 0.0,
                'average_home_goals' => 0.0,
                'average_away_goals' => 0.0
            ],
            'head_to_head' => $this->getEmptyHeadToHeadStats()
        ];
    }

    /**
     * Get empty head-to-head statistics structure
     */
    private function getEmptyHeadToHeadStats(): array
    {
        return [
            'home_wins' => 0,
            'away_wins' => 0,
            'draws' => 0,
            'home_win_percentage' => 0.0,
            'away_win_percentage' => 0.0,
            'draw_percentage' => 0.0
        ];
    }
}
