<?php
// public/index.php

$page = $_GET['page'] ?? 'home';

switch ($page) {
    case 'register':
        $pageTitle = 'Valomen.gg | Register';
        $pageCss   = 'register.css';
        require __DIR__ . '/../app/View/partials/header.php';
        require __DIR__ . '/../app/View/register.view.php';
        require __DIR__ . '/../app/View/partials/footer.php';
        break;

    case 'login':
        $pageTitle = 'Valomen.gg | Login';
        $pageCss   = 'login.css';
        require __DIR__ . '/../app/View/partials/header.php';
        require __DIR__ . '/../app/View/login.view.php';
        require __DIR__ . '/../app/View/partials/footer.php';
        break;

    case 'events':
        $pageTitle = 'Valomen.gg | Events';
        $pageCss   = 'events.css';
        require __DIR__ . '/../app/View/partials/header.php';
        require __DIR__ . '/../app/View/events.view.php';
        require __DIR__ . '/../app/View/partials/footer.php';
        break;

    case 'matches':
        $pageTitle = 'Valomen.gg | Matches';
        $pageCss   = 'matches.css';
        require __DIR__ . '/../app/View/partials/header.php';
        require __DIR__ . '/../app/View/matches.view.php';
        require __DIR__ . '/../app/View/partials/footer.php';
        break;

    default:
        $pageTitle = 'Valomen.gg | Home Page';
        $pageCss   = 'main.css';
        require __DIR__ . '/../app/View/partials/header.php';
        require __DIR__ . '/../app/View/home.view.php';
        require __DIR__ . '/../app/View/partials/footer.php';
        break;
}
