<?php

declare(strict_types=1);

namespace FootballAPI\Repositories;

use FootballAPI\Exceptions\DataNotFoundException;

/**
 * Repository for handling match data access
 */
class MatchRepository
{
    private const JSON_FILE = 'combined_matches.json';
    private array $matches = [];
    private bool $loaded = false;

    /**
     * Get all matches
     */
    public function getAllMatches(): array
    {
        if (!$this->loaded) {
            $this->loadMatches();
        }
        
        return $this->matches;
    }

    /**
     * Load matches from JSON file
     */
    private function loadMatches(): void
    {
        if (!is_file(self::JSON_FILE)) {
            throw new DataNotFoundException("Match data file not found: " . self::JSON_FILE);
        }

        $jsonData = file_get_contents(self::JSON_FILE);
        if ($jsonData === false) {
            throw new DataNotFoundException("Failed to read match data file: " . self::JSON_FILE);
        }

        try {
            $data = json_decode($jsonData, true, 512, JSON_THROW_ON_ERROR);
            $this->matches = $data['matches'] ?? [];
            $this->loaded = true;
        } catch (\JsonException $e) {
            throw new DataNotFoundException("Invalid JSON data in match file: " . $e->getMessage());
        }
    }

    /**
     * Get total match count
     */
    public function getMatchCount(): int
    {
        if (!$this->loaded) {
            $this->loadMatches();
        }
        
        return count($this->matches);
    }

    /**
     * Check if data is loaded
     */
    public function isDataLoaded(): bool
    {
        return $this->loaded;
    }

    /**
     * Reload data from file
     */
    public function reloadData(): void
    {
        $this->loaded = false;
        $this->matches = [];
        $this->loadMatches();
    }
}
