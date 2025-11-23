<?php

declare(strict_types=1);

$page = $_GET['page'] ?? 'map';

// Load controllers
require_once __DIR__ . '/Controllers/MapController.php';
require_once __DIR__ . '/Controllers/ReportController.php';
require_once __DIR__ . '/Controllers/EducationController.php';

switch ($page) {

    case 'report':
        $reportController = new ReportController();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $reportController->submit();
        } else {
            $reportController->show();
        }
        break;

    case 'education':
        $controller = new EducationController();
        $controller->index();
        break;

    case 'prevention':
        $pageTitle = 'Tick Prevention Tips';
        require __DIR__ . '/Views/templates/header.phtml';
        require __DIR__ . '/Views/prevention.phtml';
        require __DIR__ . '/Views/templates/footer.phtml';
        break;

    case 'map':
    default:
        $controller = new MapController();
        $controller->index();
        break;
}
