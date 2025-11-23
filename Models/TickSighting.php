<?php

declare(strict_types=1);

class TickSighting
{
    // Base API URL for retrieving all tick sightings
    private string $apiUrl = 'https://dev-task.elancoapps.com/data/tick-sightings';

    // Fetch all tick sightings from the main API endpoint
    public function getAll(): array
    {
        // If cURL is not available, fail gracefully
        if (!function_exists('curl_init')) {
            return [];
        }

        // Initialise cURL with the base API URL
        $ch = curl_init($this->apiUrl);

        // Set basic cURL options for a simple GET request
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_TIMEOUT        => 10,
        ]);

        // Execute the request
        $response = curl_exec($ch);

        // If the request failed, clean up and return empty
        if ($response === false) {
            curl_close($ch);
            return [];
        }

        // Check HTTP status code from the response
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Only accept a 200 OK response
        if ($statusCode !== 200) {
            return [];
        }

        // Decode JSON into a PHP array
        $data = json_decode($response, true);

        // If the decoded data is not an array, return empty
        if (!is_array($data)) {
            return [];
        }

        // Return the raw API data as an array
        return $data;
    }

    // Filter a set of sightings by species and date range
    public function filter(array $sightings, ?string $species = null, ?string $dateRange = null): array
    {
        // Normalise input strings
        $species    = $species !== null ? trim($species) : null;
        $dateRange  = $dateRange !== null ? trim($dateRange) : null;

        // Current time for relative range calculations
        $now        = new DateTime('now');
        $rangeStart = null; // DateTime|null
        $rangeEnd   = null; // DateTime|null

        // Convert the named date range into start/end boundaries
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
                // No recognised range selected, skip date filtering
                break;
        }

        // Apply species and date filters to the sightings array
        $filtered = array_filter($sightings, function (array $sighting) use ($species, $rangeStart, $rangeEnd): bool {
            $sightingSpecies = $sighting['species'] ?? null;
            $sightingDateRaw = $sighting['date'] ?? null;

            // 1) Species filter (case-insensitive exact match)
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

            // 2) Date filter (only if a range was defined)
            if ($rangeStart !== null || $rangeEnd !== null) {
                if (!$sightingDateRaw) {
                    return false;
                }

                // Try to parse the sighting date
                $dt = date_create($sightingDateRaw);
                if (!$dt) {
                    return false;
                }

                // Inclusive lower bound
                if ($rangeStart !== null && $dt < $rangeStart) {
                    return false;
                }

                // Inclusive upper bound
                if ($rangeEnd !== null && $dt > $rangeEnd) {
                    return false;
                }
            }

            // If it passed all filters, keep this sighting
            return true;
        });

        // Reindex the array so JSON encoding is tidy
        return array_values($filtered);
    }

    // Fetch tick sightings for a specific city using the city endpoint
    public function getByCity(string $city): array
    {
        // Do not call the API with an empty city
        if ($city === '') {
            return [];
        }

        // Build the city-specific endpoint
        $baseUrl = 'https://dev-task.elancoapps.com/data/tick-sightings/city/';
        $url = $baseUrl . rawurlencode($city);

        // If cURL is not available, fail gracefully
        if (!function_exists('curl_init')) {
            return [];
        }

        // Initialise cURL with the city URL
        $ch = curl_init($url);

        // Standard cURL options for GET with JSON expected
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

        // Execute the request
        $response = curl_exec($ch);

        // Bail out if the request failed
        if ($response === false) {
            curl_close($ch);
            return [];
        }

        // Check HTTP status
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Only accept 200 OK responses
        if ($statusCode !== 200) {
            return [];
        }

        // Decode the JSON body
        $data = json_decode($response, true);

        // Ensure we always return an array
        if (!is_array($data)) {
            return [];
        }

        return $data;
    }

    // Fetch tick sightings for a specific species using the species endpoint
    public function getBySpecies(string $species): array
    {
        // Do not call the API with an empty species name
        if ($species === '') {
            return [];
        }

        // Build the species-specific endpoint
        $baseUrl = 'https://dev-task.elancoapps.com/data/tick-sightings/species/';
        $url = $baseUrl . rawurlencode($species);

        // If cURL is not available, fail gracefully
        if (!function_exists('curl_init')) {
            return [];
        }

        // Initialise cURL with the species URL
        $ch = curl_init($url);

        // Standard cURL options for JSON GET
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

        // Execute the request
        $response = curl_exec($ch);

        // If the call failed, tidy up and return empty
        if ($response === false) {
            curl_close($ch);
            return [];
        }

        // Check status code from the response
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Only accept 200 OK
        if ($statusCode !== 200) {
            return [];
        }

        // Decode JSON into a PHP array
        $data = json_decode($response, true);

        // Guard against non-array responses
        if (!is_array($data)) {
            return [];
        }

        return $data;
    }

}

