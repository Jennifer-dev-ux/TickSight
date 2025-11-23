<?php
// database/db.php

function getDbConnection(): PDO
{
    // SQLite file inside the same folder as this file
    $dbPath = __DIR__ . '/sightings.sqlite';

    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create table for user-submitted sightings if it doesn't exist
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS user_sightings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            sighting_date TEXT NOT NULL,
            sighting_time TEXT NOT NULL,
            location TEXT NOT NULL,
            species TEXT NOT NULL,
            severity TEXT NOT NULL,
            description TEXT,
            image_path TEXT,
            created_at TEXT NOT NULL
        )
    ");

    return $pdo;
}

