<?php

declare(strict_types=1);

require_once __DIR__ . '/../Models/TickSighting.php';

class EducationController
{
    private TickSighting $tickModel;

    public function __construct()
    {
        // Instantiate the API model so we can reuse it across methods
        $this->tickModel = new TickSighting();
    }

    public function index(): void
    {
        // Pull the full list of API sightings once
        $allSightings = $this->tickModel->getAll();

        // These arrays will hold the unique cities and years we find in the dataset
        $cities = [];
        $years  = [];

        // These are the species we want to build the “species info cards” for
        $interestingSpecies = [
            'Passerine tick',
            'Fox/badger tick',
            'Southern rodent tick',
            'Marsh tick',
            'Tree-hole tick',
        ];

        // Extract cities and years from all API records
        foreach ($allSightings as $sighting) {
            if (!empty($sighting['location'])) {
                $cities[] = $sighting['location'];
            }

            if (!empty($sighting['date'])) {
                $year = (int) (new DateTime($sighting['date']))->format('Y');
                $years[] = $year;
            }
        }

        // Keep only unique values and sort alphabetically / numerically
        $cities = array_values(array_unique($cities));
        sort($cities);

        $years = array_values(array_unique($years));
        sort($years);

        // Build a list of species actually found in the API data
        $speciesList = [];
        foreach ($allSightings as $sighting) {
            if (!empty($sighting['species'])) {
                $speciesList[] = $sighting['species'];
            }
        }

        // Remove duplicates
        $speciesList = array_values(array_unique($speciesList));
        sort($speciesList);

        // Read the selected filters from the URL or fall back to defaults
        $selectedCity    = $_GET['city']    ?? ($cities[0] ?? null);
        $selectedYear    = isset($_GET['year']) ? (int)$_GET['year'] : (end($years) ?: null);
        $selectedSpecies = $_GET['species'] ?? '';

        // Start an array of 12 months all set to zero
        $monthlyCounts = array_fill(1, 12, 0);

        // Only calculate the chart if both a city and year are selected
        if ($selectedCity && $selectedYear) {
            foreach ($allSightings as $sighting) {

                // Skip incomplete records
                if (empty($sighting['location']) || empty($sighting['date'])) {
                    continue;
                }

                // Filter by city
                if ($sighting['location'] !== $selectedCity) {
                    continue;
                }

                // If a specific species was chosen, enforce that here
                if ($selectedSpecies !== '' &&
                    (!isset($sighting['species']) || $sighting['species'] !== $selectedSpecies)) {
                    continue;
                }

                // Convert to date object
                $dateObj = new DateTime($sighting['date']);
                $year    = (int)$dateObj->format('Y');
                $month   = (int)$dateObj->format('n');

                // Only count sightings from the selected year
                if ($year === $selectedYear && $month >= 1 && $month <= 12) {
                    $monthlyCounts[$month]++;
                }
            }
        }


        // Build the “top cities” and “peak month” info for each interesting species
        $speciesStats = [];

        // Month number → readable month name
        $monthNames = [
            1 => 'January',  2 => 'February', 3 => 'March',
            4 => 'April',    5 => 'May',      6 => 'June',
            7 => 'July',     8 => 'August',   9 => 'September',
            10 => 'October', 11 => 'November',12 => 'December',
        ];

        // Use the species endpoint to compute stats individually for each species
        foreach ($interestingSpecies as $speciesName) {
            $sightings = $this->tickModel->getBySpecies($speciesName);

            $cityCounts  = [];
            $monthCounts = array_fill(1, 12, 0);

            // Build counts based on the species-specific response
            foreach ($sightings as $sighting) {

                // Count by city
                if (!empty($sighting['location'])) {
                    $city = $sighting['location'];
                    $cityCounts[$city] = ($cityCounts[$city] ?? 0) + 1;
                }

                // Count by month
                if (!empty($sighting['date'])) {
                    try {
                        $dateObj = new DateTime($sighting['date']);
                        $month   = (int)$dateObj->format('n');
                        if ($month >= 1 && $month <= 12) {
                            $monthCounts[$month]++;
                        }
                    } catch (Exception $e) {
                        // Ignore any badly formatted dates
                    }
                }
            }

            // Sort cities by highest number of sightings first
            arsort($cityCounts);

            // Keep only the top two
            $topCities = array_keys($cityCounts);
            $topCities = array_slice($topCities, 0, 2);

            // Determine which month had the most sightings
            $peakMonthName = null;
            if (max($monthCounts) > 0) {
                $peakMonthNum  = array_keys($monthCounts, max($monthCounts), true)[0];
                $peakMonthName = $monthNames[$peakMonthNum] ?? null;
            }

            // Store the stats so the view can display them
            $speciesStats[$speciesName] = [
                'topCities' => $topCities,
                'peakMonth' => $peakMonthName,
            ];
        }

        // Title for the page template
        $pageTitle = 'Tick Education & Prevention';

        // Data passed to the view
        $data = [
            'pageTitle'       => $pageTitle,
            'cities'          => $cities,
            'years'           => $years,
            'speciesList'     => $speciesList,
            'selectedCity'    => $selectedCity,
            'selectedYear'    => $selectedYear,
            'selectedSpecies' => $selectedSpecies,
            'monthlyCounts'   => $monthlyCounts,
            'speciesStats'    => $speciesStats,
        ];

        // Extract the variables so the template can use them directly
        extract($data);

        // Render the MVC view
        require __DIR__ . '/../Views/templates/header.phtml';
        require __DIR__ . '/../Views/education.phtml';
        require __DIR__ . '/../Views/templates/footer.phtml';
    }
}
