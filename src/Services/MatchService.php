<?php

declare(strict_types=1);

namespace FootballAPI\Services;

use FootballAPI\Repositories\MatchRepository;
use FootballAPI\Filters\MatchFilter;

/**
 * Service for handling match-related operations
 */
class MatchService
{
    private MatchRepository $matchRepository;
    private MatchFilter $matchFilter;

    public function __construct()
    {
        $this->matchRepository = new MatchRepository();
        $this->matchFilter = new MatchFilter();
    }

    /**
     * Get filtered matches based on parameters
     */
    public function getFilteredMatches(array $params): array
    {
        $matches = $this->matchRepository->getAllMatches();
        return $this->matchFilter->filterMatches($matches, $params);
    }

    /**
     * Get all available teams
     */
    public function getAvailableTeams(): array
    {
        $matches = $this->matchRepository->getAllMatches();
        return $this->extractUniqueTeams($matches);
    }

    /**
     * Get matches for a specific team
     */
    public function getTeamMatches(string $team): array
    {
        $matches = $this->matchRepository->getAllMatches();
        
        return array_filter($matches, function ($match) use ($team) {
            return $this->isTeamInMatch($match, $team);
        });
    }

    /**
     * Get head-to-head matches between two teams
     */
    public function getHeadToHeadMatches(string $homeTeam, string $awayTeam): array
    {
        $matches = $this->matchRepository->getAllMatches();
        
        return array_filter($matches, function ($match) use ($homeTeam, $awayTeam) {
            return $this->isHeadToHeadMatch($match, $homeTeam, $awayTeam);
        });
    }

    /**
     * Check if a team is involved in a match
     */
    private function isTeamInMatch(array $match, string $team): bool
    {
        return (isset($match['home_team']) && strcasecmp($match['home_team'], $team) === 0) ||
               (isset($match['away_team']) && strcasecmp($match['away_team'], $team) === 0);
    }

    /**
     * Check if a match is between two specific teams
     */
    private function isHeadToHeadMatch(array $match, string $team1, string $team2): bool
    {
        $homeTeam = $match['home_team'] ?? '';
        $awayTeam = $match['away_team'] ?? '';

        return (strcasecmp($homeTeam, $team1) === 0 && strcasecmp($awayTeam, $team2) === 0) ||
               (strcasecmp($homeTeam, $team2) === 0 && strcasecmp($awayTeam, $team1) === 0);
    }

    /**
     * Extract unique teams from matches
     */
    private function extractUniqueTeams(array $matches): array
    {
        $teams = [];
        
        foreach ($matches as $match) {
            if (!empty($match['home_team'])) {
                $teams[] = $match['home_team'];
            }
            if (!empty($match['away_team'])) {
                $teams[] = $match['away_team'];
            }
        }
        
        // Case-insensitive unique values
        $uniqueTeams = [];
        foreach ($teams as $team) {
            $lowerTeam = strtolower($team);
            if (!isset($uniqueTeams[$lowerTeam])) {
                $uniqueTeams[$lowerTeam] = $team;
            }
        }
        
        return array_values($uniqueTeams);
    }
}
