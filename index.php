<?php

declare(strict_types=1);

// Determine which page the user is requesting, default to the map
$page = $_GET['page'] ?? 'map';

// Load all controllers used by the application
require_once __DIR__ . '/Controllers/MapController.php';
require_once __DIR__ . '/Controllers/ReportController.php';
require_once __DIR__ . '/Controllers/EducationController.php';

switch ($page) {

    case 'report':
        // Handle the report-a-sighting page
        $reportController = new ReportController();

        // POST = form submission, GET = show the form
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $reportController->submit();
        } else {
            $reportController->show();
        }
        break;

    case 'education':
        // Show the education page with species guides and charts
        $controller = new EducationController();
        $controller->index();
        break;

    case 'prevention':
        // Standalone prevention page (view)
        $pageTitle = 'Tick Prevention Tips';

        // Render header, the page, and footer manually
        require __DIR__ . '/Views/templates/header.phtml';
        require __DIR__ . '/Views/prevention.phtml';
        require __DIR__ . '/Views/templates/footer.phtml';
        break;

    case 'map':
    default:
        // Default page: the interactive map
        $controller = new MapController();
        $controller->index();
        break;
}
