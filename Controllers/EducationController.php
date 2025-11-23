<?php

declare(strict_types=1);

require_once __DIR__ . '/../Models/TickSighting.php';

class EducationController
{
    private TickSighting $tickModel;

    public function __construct()
    {
        $this->tickModel = new TickSighting();
    }

    public function index(): void
    {
        // 1. Get all API sightings
        $allSightings = $this->tickModel->getAll();

        // 2. Derive available cities + years from the data
        $cities = [];
        $years  = [];
        // Species we want to show in the education cards
        $interestingSpecies = [
            'Passerine tick',
            'Fox/badger tick',
            'Southern rodent tick',
            'Marsh tick',
            'Tree-hole tick',
        ];


        foreach ($allSightings as $sighting) {
            if (!empty($sighting['location'])) {
                $cities[] = $sighting['location'];
            }

            if (!empty($sighting['date'])) {
                $year = (int) (new DateTime($sighting['date']))->format('Y');
                $years[] = $year;
            }
        }

        $cities = array_values(array_unique($cities));
        sort($cities);

        $years = array_values(array_unique($years));
        sort($years);

        // Derive available species from the data
        $speciesList = [];

        foreach ($allSightings as $sighting) {
            if (!empty($sighting['species'])) {
                $speciesList[] = $sighting['species'];
            }
        }

        $speciesList = array_values(array_unique($speciesList));
        sort($speciesList);

        $selectedCity = $_GET['city'] ?? ($cities[0] ?? null);
        $selectedYear = isset($_GET['year']) ? (int)$_GET['year'] : (end($years) ?: null);
        $selectedSpecies = $_GET['species'] ?? ''; // empty string = all species

        $monthlyCounts = array_fill(1, 12, 0);

        if ($selectedCity && $selectedYear) {
            foreach ($allSightings as $sighting) {
                if (empty($sighting['location']) || empty($sighting['date'])) {
                    continue;
                }

                // City must match
                if ($sighting['location'] !== $selectedCity) {
                    continue;
                }

                // Optional species filter: if selectedSpecies is set, only count that species
                if ($selectedSpecies !== '' && (!isset($sighting['species']) || $sighting['species'] !== $selectedSpecies)) {
                    continue;
                }

                $dateObj = new DateTime($sighting['date']);
                $year    = (int)$dateObj->format('Y');
                $month   = (int)$dateObj->format('n');

                if ($year === $selectedYear && $month >= 1 && $month <= 12) {
                    $monthlyCounts[$month]++;
                }
            }
        }

        // Build stats for each species using the species endpoint
        $speciesStats = [];
        $monthNames = [
            1 => 'January',  2 => 'February', 3 => 'March',
            4 => 'April',    5 => 'May',      6 => 'June',
            7 => 'July',     8 => 'August',   9 => 'September',
            10 => 'October', 11 => 'November',12 => 'December',
        ];

        foreach ($interestingSpecies as $speciesName) {
            $sightings = $this->tickModel->getBySpecies($speciesName);

            $cityCounts  = [];
            $monthCounts = array_fill(1, 12, 0);

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
                        // ignore invalid dates
                    }
                }
            }

            // Top 2 cities
            arsort($cityCounts);
            $topCities = array_keys($cityCounts);
            $topCities = array_slice($topCities, 0, 2);

            // Peak month
            $peakMonthName = null;
            if (max($monthCounts) > 0) {
                $peakMonthNum = array_keys($monthCounts, max($monthCounts), true)[0];
                $peakMonthName = $monthNames[$peakMonthNum] ?? null;
            }

            $speciesStats[$speciesName] = [
                'topCities' => $topCities,
                'peakMonth' => $peakMonthName,
            ];
        }

        $pageTitle = 'Tick Education & Prevention';

        $data = [
            'pageTitle'      => $pageTitle,
            'cities'         => $cities,
            'years'          => $years,
            'speciesList'     => $speciesList,
            'selectedCity'   => $selectedCity,
            'selectedYear'   => $selectedYear,
            'selectedSpecies' => $selectedSpecies,
            'monthlyCounts'  => $monthlyCounts,
            'speciesStats'    => $speciesStats,

        ];

        extract($data);

        require __DIR__ . '/../Views/templates/header.phtml';
        require __DIR__ . '/../Views/education.phtml';
        require __DIR__ . '/../Views/templates/footer.phtml';
    }
}
