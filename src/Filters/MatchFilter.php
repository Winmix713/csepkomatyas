<?php

declare(strict_types=1);

namespace FootballAPI\Filters;

/**
 * Filter class for match filtering operations
 */
class MatchFilter
{
    /**
     * Filter matches based on query parameters
     */
    public function filterMatches(array $matches, array $params): array
    {
        if (empty($params)) {
            return $matches;
        }
        
        return array_filter($matches, function (array $match) use ($params): bool {
            foreach ($params as $key => $value) {
                if (!$this->matchParameter($match, $key, $value)) {
                    return false;
                }
            }
            return true;
        });
    }

    /**
     * Check if a match parameter matches the specified condition
     */
    private function matchParameter(array $match, string $key, string $value): bool
    {
        return match ($key) {
            'team' => $this->matchesTeam($match, $value),
            'home_team' => $this->matchesHomeTeam($match, $value),
            'away_team' => $this->matchesAwayTeam($match, $value),
            'date' => $this->matchesDate($match, $value),
            'both_teams_scored' => $this->matchesBothTeamsScored($match, $value),
            'page', 'page_size' => true, // Skip pagination parameters
            default => str_starts_with($key, 'score_') 
                ? $this->matchesScore($match, $key, $value)
                : $this->matchesDefault($match, $key, $value)
        };
    }

    /**
     * Check if a team is involved in a match
     */
    private function matchesTeam(array $match, string $value): bool
    {
        if (!isset($match['home_team']) || !isset($match['away_team'])) {
            return false;
        }
        return strcasecmp($match['home_team'], $value) === 0 ||
               strcasecmp($match['away_team'], $value) === 0;
    }

    /**
     * Check if a team is the home team in a match
     */
    private function matchesHomeTeam(array $match, string $value): bool
    {
        return isset($match['home_team']) && strcasecmp($match['home_team'], $value) === 0;
    }

    /**
     * Check if a team is the away team in a match
     */
    private function matchesAwayTeam(array $match, string $value): bool
    {
        return isset($match['away_team']) && strcasecmp($match['away_team'], $value) === 0;
    }

    /**
     * Check if a match date matches the specified condition
     */
    private function matchesDate(array $match, string $value): bool
    {
        if (!isset($match['date'])) {
            return false;
        }
        
        try {
            $matchDate = new \DateTime($match['date']);
            $paramDate = new \DateTime($value);
            return $matchDate >= $paramDate;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check if a match score matches the specified condition
     */
    private function matchesScore(array $match, string $key, string $value): bool
    {
        $scoreType = str_replace('score_', '', $key);
        
        if (!isset($match['score']) || !is_array($match['score']) || !isset($match['score'][$scoreType])) {
            return false;
        }
        
        return (string)$match['score'][$scoreType] === $value;
    }

    /**
     * Check if both teams scored in a match
     */
    private function matchesBothTeamsScored(array $match, string $value): bool
    {
        if (!isset($match['score']['home']) || !isset($match['score']['away'])) {
            return false;
        }
        
        $bothScored = ((int)$match['score']['home'] > 0 && (int)$match['score']['away'] > 0);
        $expected = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        
        if ($expected === null) {
            return false;
        }
        
        return $bothScored === $expected;
    }

    /**
     * Check if a match property matches the specified condition (default)
     */
    private function matchesDefault(array $match, string $key, string $value): bool
    {
        return isset($match[$key]) && strcasecmp((string)$match[$key], $value) === 0;
    }
}
