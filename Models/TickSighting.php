<?php

declare(strict_types=1);

class TickSighting
{
    private string $apiUrl = 'https://dev-task.elancoapps.com/data/tick-sightings';

    public function getAll(): array
    {
        if (!function_exists('curl_init')) {
            return [];
        }

        $ch = curl_init($this->apiUrl);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_TIMEOUT        => 10,
        ]);

        $response = curl_exec($ch);

        if ($response === false) {
            curl_close($ch);
            return [];
        }

        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($statusCode !== 200) {
            return [];
        }

        $data = json_decode($response, true);

        if (!is_array($data)) {
            return [];
        }

        return $data;
    }

    public function filter(array $sightings, ?string $species = null, ?string $dateRange = null): array
    {
        $species    = $species !== null ? trim($species) : null;
        $dateRange  = $dateRange !== null ? trim($dateRange) : null;

        $now        = new DateTime('now');
        $rangeStart = null; // DateTime|null
        $rangeEnd   = null; // DateTime|null

        switch ($dateRange) {
            case '7_days':
                $rangeStart = (clone $now)->modify('-7 days');
                break;

            case '30_days':
                $rangeStart = (clone $now)->modify('-30 days');
                break;

            case '90_days':
                $rangeStart = (clone $now)->modify('-90 days');
                break;

            case '6_months':
                $rangeStart = (clone $now)->modify('-6 months');
                break;

            case '6_12_months':
                // Between 6 and 12 months ago
                $rangeStart = (clone $now)->modify('-12 months');
                $rangeEnd   = (clone $now)->modify('-6 months');
                break;

            case '12m_5y':
                // Between 1 year and 5 years ago
                $rangeStart = (clone $now)->modify('-5 years');
                $rangeEnd   = (clone $now)->modify('-12 months');
                break;

            case '5_15y':
                // Between 5 years and 15 years ago
                $rangeStart = (clone $now)->modify('-15 years');
                $rangeEnd   = (clone $now)->modify('-5 years');
                break;

            default:
                break;
        }

        $filtered = array_filter($sightings, function (array $sighting) use ($species, $rangeStart, $rangeEnd): bool {
            $sightingSpecies = $sighting['species'] ?? null;
            $sightingDateRaw = $sighting['date'] ?? null;

            // 1) Species filter (case-insensitive)
            if ($species !== null && $species !== '') {
                if ($sightingSpecies === null) {
                    return false;
                }

                $left  = mb_strtolower(trim($sightingSpecies));
                $right = mb_strtolower(trim($species));

                if ($left !== $right) {
                    return false;
                }
            }

            // 2) Date filter (if any range selected)
            if ($rangeStart !== null || $rangeEnd !== null) {
                if (!$sightingDateRaw) {
                    return false;
                }

                $dt = date_create($sightingDateRaw);
                if (!$dt) {
                    return false;
                }

                // Inclusive boundaries: [rangeStart, rangeEnd]
                if ($rangeStart !== null && $dt < $rangeStart) {
                    return false;
                }

                if ($rangeEnd !== null && $dt > $rangeEnd) {
                    return false;
                }
            }

            return true;
        });

        // Reindex for clean JSON
        return array_values($filtered);
    }

    public function getByCity(string $city): array
    {
        if ($city === '') {
            return [];
        }

        $baseUrl = 'https://dev-task.elancoapps.com/data/tick-sightings/city/';
        $url = $baseUrl . rawurlencode($city);

        if (!function_exists('curl_init')) {
            return [];
        }

        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_HTTPHEADER     => [
                'accept: application/json',
            ],
        ]);

        $response = curl_exec($ch);

        if ($response === false) {
            curl_close($ch);
            return [];
        }

        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($statusCode !== 200) {
            return [];
        }

        $data = json_decode($response, true);

        if (!is_array($data)) {
            return [];
        }

        return $data;
    }

    public function getBySpecies(string $species): array
    {
        if ($species === '') {
            return [];
        }

        $baseUrl = 'https://dev-task.elancoapps.com/data/tick-sightings/species/';
        $url = $baseUrl . rawurlencode($species);

        if (!function_exists('curl_init')) {
            return [];
        }

        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_HTTPHEADER     => [
                'accept: application/json',
            ],
        ]);

        $response = curl_exec($ch);

        if ($response === false) {
            curl_close($ch);
            return [];
        }

        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($statusCode !== 200) {
            return [];
        }

        $data = json_decode($response, true);

        if (!is_array($data)) {
            return [];
        }

        return $data;
    }

}
