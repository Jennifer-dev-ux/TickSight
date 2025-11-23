<?php

declare(strict_types=1);

require_once __DIR__ . '/../Database/db.php';

class UserSighting
{
    private PDO $db;

    public function __construct()
    {
        // Set up the database connection as soon as the class is created
        $this->db = getDbConnection();
    }

    /**
     * Create a new user sighting record.
     */
    public function create(array $data): bool
    {
        // Prepare an INSERT statement for adding a new sighting
        $stmt = $this->db->prepare("
            INSERT INTO user_sightings (
                sighting_date,
                sighting_time,
                location,
                species,
                description,
                image_path
            )
            VALUES (
                :sighting_date,
                :sighting_time,
                :location,
                :species,
                :description,
                :image_path
            )
        ");

        // Execute with provided data mapped to named parameters
        return $stmt->execute([
            ':sighting_date' => $data['date'],
            ':sighting_time' => $data['time'],
            ':location'      => $data['location'],
            ':species'       => $data['species'],
            ':description'   => $data['description'] ?? null,
            ':image_path'    => $data['image_path'] ?? null,
        ]);
    }

    /**
     * Get all user sightings from the database.
     */
    public function getAll(): array
    {
        // Retrieve all rows ordered newest first
        $stmt = $this->db->query("
            SELECT id, sighting_date, sighting_time, location, species, description, image_path
            FROM user_sightings
            ORDER BY sighting_date DESC, sighting_time DESC
        ");

        // Return as an array of associative arrays
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Return user sightings normalised to the same shape
     * as the API data, so they can be merged for the map.
     */
    public function getAllNormalised(): array
    {
        // Fetch raw DB records first
        $rows = $this->getAll();

        // Local species → latin name lookup for normalisation
        $speciesToLatin = [
            'Passerine tick'        => 'Ixodes arboricola',
            'Fox/badger tick'       => 'Ixodes canisuga',
            'Southern rodent tick'  => 'Ixodes acuminatus',
            'Marsh tick'            => 'Ixodes apronophorus',
            'Tree-hole tick'        => 'Dermacentor frontalis',
        ];

        // Convert each DB record into API-like structure
        return array_map(function (array $row) use ($speciesToLatin): array {

            // Combine date and time into an ISO-like string
            $datePart = $row['sighting_date'] ?? null;
            $timePart = $row['sighting_time'] ?? null;

            $dateTime = null;
            if ($datePart && $timePart) {
                $dateTime = $datePart . 'T' . $timePart; // e.g. 2025-11-21T14:30
            } elseif ($datePart) {
                $dateTime = $datePart; // fallback if no time provided
            }

            // Map the common species name to its Latin name
            $species = $row['species'] ?? null;
            $latin   = null;

            if ($species && isset($speciesToLatin[$species])) {
                $latin = $speciesToLatin[$species];
            }

            // Build the normalised record
            return [
                'date'       => $dateTime,
                'location'   => $row['location'] ?? null,
                'species'    => $species,
                'latinName'  => $latin,
                'image_path' => $row['image_path'] ?? null, // keep the image path so the map can show photos
                'source'     => 'user', // helps tell API and user sightings apart
            ];
        }, $rows);
    }
}
