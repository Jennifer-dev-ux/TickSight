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

        // Pull all API sightings (raw) from the Elanco API
        $apiSightingsRaw   = $tickModel->getAll();

        // Get raw DB rows of user-submitted sightings
        $userSightingsRaw  = $userModel->getAll();

        // Get user sightings converted into the same shape as API records
        $userSightingsNorm = $userModel->getAllNormalised();

        // Read filter choices from the query string
        $species   = $_GET['species']   ?? '';
        $dateRange = $_GET['dateRange'] ?? '';
        $severity  = $_GET['severity']  ?? '';

        // Merge API + normalised user sightings into a single dataset
        $combinedSightings = array_merge($apiSightingsRaw, $userSightingsNorm);

        // Apply species + date range filtering
        $filteredSightings = $this->filterSightings($combinedSightings, $species, $dateRange);

        // Pack everything we need to pass into the view
        $data = [
            'sightings'        => $filteredSightings,
            'userSightings'    => $userSightingsRaw,
            'selectedSpecies'  => $species,
            'selectedRange'    => $dateRange,
            'selectedSeverity' => $severity,
            'page'             => 'map',
        ];

        // Render the map view
        $this->render('index', $data);
    }

    /**
     * Apply species and date-range filters to the combined data.
     */
    private function filterSightings(array $sightings, string $species, string $dateRange): array
    {
        // Filter by species if one was selected
        if ($species !== '') {
            $sightings = array_values(array_filter(
                $sightings,
                fn(array $s) => isset($s['species']) && $s['species'] === $species
            ));
        }

        // Pass data into the date-range filter
        $sightings = $this->filterByDateRange($sightings, $dateRange);

        return $sightings;
    }

    /**
     * Handle date-range filtering based on the selected option.
     * Uses DateTimeImmutable for safety and consistency.
     */
    private function filterByDateRange(array $sightings, ?string $range): array
    {
        if ($range === null || $range === '') {
            // No date range selected → return everything
            return $sightings;
        }

        $now  = new DateTimeImmutable('now');
        $from = null;
        $to   = null;

        // Work out the boundaries of the selected date window
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
                // Between 6 and 12 months ago
                $to   = $now->modify('-6 months');
                $from = $now->modify('-12 months');
                break;

            case '12m_5y':
                // Between 1 and 5 years ago
                $to   = $now->modify('-12 months');
                $from = $now->modify('-5 years');
                break;

            case '5_15y':
                // Between 5 and 15 years ago
                $to   = $now->modify('-5 years');
                $from = $now->modify('-15 years');
                break;

            default:
                // Unknown range name → skip filtering
                return $sightings;
        }

        // Filter each sighting against our computed date window
        return array_values(array_filter($sightings, function (array $sighting) use ($from, $to): bool {
            if (empty($sighting['date'])) {
                return false;
            }

            try {
                $dt = new DateTimeImmutable($sighting['date']);
            } catch (Exception) {
                return false;
            }

            // Check lower bound
            if ($from !== null && $dt < $from) {
                return false;
            }

            // Check upper bound
            if ($to !== null && $dt > $to) {
                return false;
            }

            return true;
        }));
    }

    /**
     * Render a view file with its data.
     */
    private function render(string $view, array $data = []): void
    {
        // Extract array keys into variables for cleaner template usage
        extract($data);

        // Page title for the tab/heading
        $pageTitle = 'TickSight UK – Map';

        // Include the layout templates
        require __DIR__ . '/../Views/templates/header.phtml';
        require __DIR__ . '/../Views/' . $view . '.phtml';
        require __DIR__ . '/../Views/templates/footer.phtml';
    }
}
