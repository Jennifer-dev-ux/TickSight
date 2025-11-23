<?php
// Controllers/MapController.php

declare(strict_types=1);

require_once __DIR__ . '/../Models/TickSighting.php';
require_once __DIR__ . '/../Models/UserSighting.php';

class MapController
{
    public function index(): void
    {
        $tickModel = new TickSighting();
        $userModel = new UserSighting();

        // 1) Fetch raw data
        $apiSightingsRaw   = $tickModel->getAll();           // from Elanco API
        $userSightingsRaw  = $userModel->getAll();           // raw DB rows (for tables, etc.)
        $userSightingsNorm = $userModel->getAllNormalised(); // shaped like API data

        // 2) Read filters from query string
        $species   = $_GET['species']   ?? '';
        $dateRange = $_GET['dateRange'] ?? '';
        $severity  = $_GET['severity']  ?? '';

        // 3) Combine API + user data into one array for the map
        //    Both sides should share keys: date, location, species, latinName, source
        $combinedSightings = array_merge($apiSightingsRaw, $userSightingsNorm);

        // 4) Apply species + date range filters on the combined set
        $filteredSightings = $this->filterSightings($combinedSightings, $species, $dateRange);

        // 5) Pass filtered data to the view
        $data = [
            'sightings'        => $filteredSightings, // used as apiSightings in JS
            'userSightings'    => $userSightingsRaw,  // optional: for other displays
            'selectedSpecies'  => $species,
            'selectedRange'    => $dateRange,
            'selectedSeverity' => $severity,
            'page'             => 'map',
        ];

        $this->render('index', $data);
    }

    /**
     * Apply species + date-range filtering to the combined sightings array.
     */
    private function filterSightings(array $sightings, string $species, string $dateRange): array
    {
        // Species filter (exact match)
        if ($species !== '') {
            $sightings = array_values(array_filter(
                $sightings,
                fn(array $s) => isset($s['species']) && $s['species'] === $species
            ));
        }

        // Date range filter
        $sightings = $this->filterByDateRange($sightings, $dateRange);

        return $sightings;
    }

    /**
     * Filter an array of sightings by a named date range.
     *
     * Expects ['date' => 'YYYY-MM-DDTHH:MM:SS', ...] in each row.
     */
    private function filterByDateRange(array $sightings, ?string $range): array
    {
        if ($range === null || $range === '') {
            // "All time"
            return $sightings;
        }

        $now  = new DateTimeImmutable('now');
        $from = null;
        $to   = null;

        switch ($range) {
            case '7_days':
                $from = $now->modify('-7 days');
                break;

            case '30_days':
                $from = $now->modify('-30 days');
                break;

            case '90_days':
                $from = $now->modify('-90 days');
                break;

            case '6_months':
                $from = $now->modify('-6 months');
                break;

            case '6_12_months':
                $to   = $now->modify('-6 months');
                $from = $now->modify('-12 months');
                break;

            case '12m_5y':
                $to   = $now->modify('-12 months');
                $from = $now->modify('-5 years');
                break;

            case '5_15y':
                $to   = $now->modify('-5 years');
                $from = $now->modify('-15 years');
                break;

            default:
                // Unknown range key => don’t filter by date at all
                return $sightings;
        }

        return array_values(array_filter($sightings, function (array $sighting) use ($from, $to): bool {
            if (empty($sighting['date'])) {
                return false;
            }

            try {
                $dt = new DateTimeImmutable($sighting['date']);
            } catch (Exception) {
                return false;
            }

            if ($from !== null && $dt < $from) {
                return false;
            }
            if ($to !== null && $dt > $to) {
                return false;
            }

            return true;
        }));
    }

    private function render(string $view, array $data = []): void
    {
        extract($data);
        $pageTitle = 'TickSight UK – Map';

        require __DIR__ . '/../Views/templates/header.phtml';
        require __DIR__ . '/../Views/' . $view . '.phtml';
        require __DIR__ . '/../Views/templates/footer.phtml';
    }
}
