<?php

declare(strict_types=1);

require_once __DIR__ . '/../Models/UserSighting.php';

class ReportController
{
    private UserSighting $userModel;

    public function __construct()
    {
        // Set up the model so we can talk to the user_sightings table
        $this->userModel = new UserSighting();
    }

    /**
     * Show the report form.
     */
    public function show(array $errors = [], array $old = [], bool $success = false): void
    {
        // Bundle all data the view needs into one array
        $data = [
            'errors'  => $errors,
            'old'     => $old,
            'success' => $success,
        ];

        // Render the report view with the provided state
        $this->render('report', $data);
    }

    /**
     * Handle form submission.
     */
    public function submit(): void
    {
        $errors = [];

        // Keep old values so we can refill the form on error
        $old = [
            'date'        => trim($_POST['date'] ?? ''),
            'time'        => trim($_POST['time'] ?? ''),
            'location'    => trim($_POST['location'] ?? ''),
            'species'     => trim($_POST['species'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
        ];

        // 1. Basic required checks
        if ($old['date'] === '') {
            $errors['date'] = 'Please select a date for the sighting.';
        }

        if ($old['time'] === '') {
            $errors['time'] = 'Please select a time for the sighting.';
        }

        if ($old['location'] === '') {
            $errors['location'] = 'Please enter a location (town/area or postcode).';
        }

        if ($old['species'] === '') {
            $errors['species'] = 'Please select a tick species.';
        }

        // 2. Validate date + time format and check "not in the future"
        $sightingDateTime = null;

        if ($old['date'] !== '' && $old['time'] !== '') {
            // Try to parse the date in Y-m-d format
            $dateObj = DateTime::createFromFormat('Y-m-d', $old['date']);
            $dateValid = $dateObj && $dateObj->format('Y-m-d') === $old['date'];

            // Try to parse time as HH:MM or HH:MM:SS
            $timeObj = DateTime::createFromFormat('H:i', $old['time'])
                ?: DateTime::createFromFormat('H:i:s', $old['time']);

            $timeValid = $timeObj !== false;

            if (!$dateValid) {
                $errors['date'] = 'Please enter a valid date.';
            }

            if (!$timeValid) {
                $errors['time'] = 'Please enter a valid time.';
            }

            if ($dateValid && $timeValid) {
                // Combine the separate date and time into a single DateTime object
                $sightingDateTime = clone $dateObj;
                $sightingDateTime->setTime(
                    (int)$timeObj->format('H'),
                    (int)$timeObj->format('i')
                );

                $now = new DateTime('now');

                // Reject any sighting that is in the future
                if ($sightingDateTime > $now) {
                    $errors['date'] = 'The sighting date/time cannot be in the future.';
                    $errors['time'] = 'The sighting date/time cannot be in the future.';
                }
            }
        }

        // 3. Validate location against known cities (case-insensitive)
        $allowedCities = [
            'Nottingham',
            'Glasgow',
            'London',
            'Manchester',
            'Sheffield',
            'Liverpool',
            'Bristol',
            'Birmingham',
            'Edinburgh',
            'Cardiff',
            'Southampton',
            'Newcastle',
            'Leeds',
            'Leicester',
        ];

        $normalisedLocation = null;
        if ($old['location'] !== '') {
            // Compare against the supported list ignoring case
            foreach ($allowedCities as $city) {
                if (strcasecmp($old['location'], $city) === 0) {
                    $normalisedLocation = $city; // keep the canonical spelling
                    break;
                }
            }

            // If we did not find a match, show a validation error
            if ($normalisedLocation === null) {
                $errors['location'] = 'Please enter a valid UK city from the supported list.';
            }
        }

        // 4. Handle optional image upload
        $imagePath = null;

        if (!empty($_FILES['image']['name'])) {
            if ($_FILES['image']['error'] === UPLOAD_ERR_OK) {
                // Folder under /images for user uploads
                $uploadDir = __DIR__ . '/../images/uploads';

                // Create the directory if it does not exist yet
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                // Build a safe filename with a timestamp and random suffix
                $originalName = $_FILES['image']['name'];
                $ext = pathinfo($originalName, PATHINFO_EXTENSION);
                $safeExt = $ext ? '.' . preg_replace('/[^a-zA-Z0-9]/', '', $ext) : '';

                $fileName = 'sighting_' . time() . '_' . bin2hex(random_bytes(4)) . $safeExt;
                $target   = $uploadDir . '/' . $fileName;

                // Move the uploaded file from temp to our uploads folder
                if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
                    $imagePath = 'images/uploads/' . $fileName; // path used by the browser
                } else {
                    $errors['image'] = 'Failed to upload image. Please try again.';
                }
            } elseif ($_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
                // Any upload error other than "no file" gets a generic message
                $errors['image'] = 'There was a problem with the image upload.';
            }
        }

        // 5. If any validation failed, re-display the form with errors and old input
        if (!empty($errors)) {
            $this->show($errors, $old, false);
            return;
        }

        // Use the normalised location if we found a match in the supported list
        $locationToSave = $normalisedLocation ?? $old['location'];

        // 6. Build the data array for the model
        $data = [
            'date'        => $old['date'],
            'time'        => $old['time'],
            'location'    => $locationToSave,
            'species'     => $old['species'],
            'description' => $old['description'],
            'image_path'  => $imagePath,
        ];

        // Attempt to insert the sighting into the database
        $created = $this->userModel->create($data);

        // If the insert failed, show an error at the top of the form
        if (!$created) {
            $errors['general'] = 'There was a problem saving your sighting. Please try again.';
            $this->show($errors, $old, false);
            return;
        }

        // 7. On success, clear the form and show the success banner
        $this->show([], [], true);
    }

    /**
     * Render a view with the standard layout.
     */
    private function render(string $view, array $data = []): void
    {
        // Make array keys available as variables in the template
        extract($data);

        // Page title used by the header template
        $pageTitle = 'Report a Tick Sighting';

        // Include shared header, the view, then the footer
        require __DIR__ . '/../Views/templates/header.phtml';
        require __DIR__ . '/../Views/' . $view . '.phtml';
        require __DIR__ . '/../Views/templates/footer.phtml';
    }
}
