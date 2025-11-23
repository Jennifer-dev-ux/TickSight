<?php

// Controllers/ReportController.php

declare(strict_types=1);

require_once __DIR__ . '/../Models/UserSighting.php';

class ReportController
{
    private UserSighting $userModel;

    public function __construct()
    {
        $this->userModel = new UserSighting();
    }

    /**
     * Show the report form.
     */
    public function show(array $errors = [], array $old = [], bool $success = false): void
    {
        $data = [
            'errors'  => $errors,
            'old'     => $old,
            'success' => $success,
        ];

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

        // 🔹 1. Basic required checks
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

        // 🔹 2. Validate date + time format and check "not in the future"
        $sightingDateTime = null;

        if ($old['date'] !== '' && $old['time'] !== '') {
            // Parse date
            $dateObj = DateTime::createFromFormat('Y-m-d', $old['date']);
            $dateValid = $dateObj && $dateObj->format('Y-m-d') === $old['date'];

            // Parse time (support HH:MM and HH:MM:SS)
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
                // Combine into a single DateTime
                $sightingDateTime = clone $dateObj;
                $sightingDateTime->setTime(
                    (int)$timeObj->format('H'),
                    (int)$timeObj->format('i')
                );

                $now = new DateTime('now');

                if ($sightingDateTime > $now) {
                    $errors['date'] = 'The sighting date/time cannot be in the future.';
                    $errors['time'] = 'The sighting date/time cannot be in the future.';
                }
            }
        }

        // 🔹 3. Validate location against known cities (case-insensitive)
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
            foreach ($allowedCities as $city) {
                if (strcasecmp($old['location'], $city) === 0) {
                    $normalisedLocation = $city; // store the canonical spelling
                    break;
                }
            }

            if ($normalisedLocation === null) {
                $errors['location'] = 'Please enter a valid UK city from the supported list.';
            }
        }

        // 🔹 4. Handle optional image upload
        $imagePath = null;

        if (!empty($_FILES['image']['name'])) {
            if ($_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../images/uploads';

                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                $originalName = $_FILES['image']['name'];
                $ext = pathinfo($originalName, PATHINFO_EXTENSION);
                $safeExt = $ext ? '.' . preg_replace('/[^a-zA-Z0-9]/', '', $ext) : '';

                $fileName = 'sighting_' . time() . '_' . bin2hex(random_bytes(4)) . $safeExt;
                $target   = $uploadDir . '/' . $fileName;

                if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
                    $imagePath = 'images/uploads/' . $fileName; // web path
                } else {
                    $errors['image'] = 'Failed to upload image. Please try again.';
                }
            } elseif ($_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
                $errors['image'] = 'There was a problem with the image upload.';
            }
        }

        // 🔹 5. If any errors, re-show the form with messages + old values
        if (!empty($errors)) {
            $this->show($errors, $old, false);
            return;
        }

        // Use normalised location if it passed validation
        $locationToSave = $normalisedLocation ?? $old['location'];

        // 🔹 6. Save to DB
        $data = [
            'date'        => $old['date'],
            'time'        => $old['time'],
            'location'    => $locationToSave,
            'species'     => $old['species'],
            'description' => $old['description'],
            'image_path'  => $imagePath,
        ];

        $created = $this->userModel->create($data);

        if (!$created) {
            $errors['general'] = 'There was a problem saving your sighting. Please try again.';
            $this->show($errors, $old, false);
            return;
        }

        // 🔹 7. On success: clear form + show success banner
        $this->show([], [], true);
    }


    private function render(string $view, array $data = []): void
    {
        extract($data);
        $pageTitle = 'Report a Tick Sighting';

        require __DIR__ . '/../Views/templates/header.phtml';
        require __DIR__ . '/../Views/' . $view . '.phtml';
        require __DIR__ . '/../Views/templates/footer.phtml';
    }
}
