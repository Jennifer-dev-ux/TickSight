<?php

// Models/UserSighting.php

declare(strict_types=1);

require_once __DIR__ . '/../Database/db.php';

class UserSighting
{
    private PDO $db;

    public function __construct()
    {
        $this->db = getDbConnection();
    }

    /**
     * Create a new user sighting record.
     *
     * Expected $data keys:
     *  - date        (Y-m-d)
     *  - time        (HH:MM or HH:MM:SS)
     *  - location    (city or area)
     *  - species     (e.g. "Marsh tick")
     *  - description (optional)
     *  - image_path  (optional)
     */
    public function create(array $data): bool
    {
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

        return $stmt->execute([
            ':sighting_date' => $data['date'],               // e.g. '2025-11-21'
            ':sighting_time' => $data['time'],               // e.g. '14:30'
            ':location'      => $data['location'],           // e.g. 'Manchester'
            ':species'       => $data['species'],            // e.g. 'Marsh tick'
            ':description'   => $data['description'] ?? null,
            ':image_path'    => $data['image_path'] ?? null,
        ]);
    }

    /**
     * Get all user sightings from the database.
     */
    public function getAll(): array
    {
        $stmt = $this->db->query("
            SELECT id, sighting_date, sighting_time, location, species, description, image_path
            FROM user_sightings
            ORDER BY sighting_date DESC, sighting_time DESC
        ");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * OPTIONAL helper: return user sightings normalised to the same shape
     * as the API data, so they can be merged for the map.
     *
     * Shape:
     *  - date       (combined date + time in ISO-ish format)
     *  - location
     *  - species
     *  - latinName  (always null here, unless you decide to store it)
     *  - source     ('user')
     */
    public function getAllNormalised(): array
    {
        $rows = $this->getAll();

        // Map common species name -> Latin name (from API / dataset)
        $speciesToLatin = [
            'Passerine tick'        => 'Ixodes arboricola',
            'Fox/badger tick'       => 'Ixodes canisuga',
            'Southern rodent tick'  => 'Ixodes acuminatus',
            'Marsh tick'            => 'Ixodes apronophorus',
            'Tree-hole tick'        => 'Dermacentor frontalis',
        ];

        return array_map(function (array $row) use ($speciesToLatin): array {

            // Combine date + time
            $datePart = $row['sighting_date'] ?? null;
            $timePart = $row['sighting_time'] ?? null;

            $dateTime = null;
            if ($datePart && $timePart) {
                $dateTime = $datePart . 'T' . $timePart; // e.g. 2025-11-21T14:30
            } elseif ($datePart) {
                $dateTime = $datePart;
            }

            // Species → latin name
            $species = $row['species'] ?? null;
            $latin   = null;

            if ($species && isset($speciesToLatin[$species])) {
                $latin = $speciesToLatin[$species];
            }

            return [
                'date'       => $dateTime,
                'location'   => $row['location'] ?? null,
                'species'    => $species,
                'latinName'  => $latin,
                'image_path' => $row['image_path'] ?? null,   // ⭐ REQUIRED FOR PHOTOS
                'source'     => 'user',
            ];

        }, $rows);

    }

}
