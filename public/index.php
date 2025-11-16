<?php

session_start();

require __DIR__ . '/../config/db-connection.php';
require_once __DIR__ . '/../lib/DateFormat.php';
require __DIR__ . '/../app/Model/DAO/BaseDAO.php';
require __DIR__ . '/../app/Model/DAO/UserDAO.php';
require __DIR__ . '/../app/Controller/LoginController.php';

$loginController = new LoginController(new UserDAO($db));

$page = $_GET['page'] ?? 'home';

switch ($page) {
    case 'register':
    require __DIR__ . '/../app/Controller/RegisterController.php';

    $registerController = new RegisterController(new UserDAO($db));

    $registerErrors  = [
        'username'         => '',
        'email'            => '',
        'password'         => '',
        'confirm_password' => '',
    ];
    $registerSuccess = false;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $username        = $_POST['username'] ?? '';
        $email           = $_POST['email'] ?? '';
        $password        = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        $result = $registerController->register($username, $email, $password, $confirmPassword);

        $registerErrors = $result['errors'];

        if ($result['success']) {
            $registerSuccess = true;
        }
    }

    $pageTitle = 'Valomen.gg | Register';
    $pageCss   = 'register.css';

    require __DIR__ . '/../app/View/partials/header.php';
    require __DIR__ . '/../app/View/register.view.php';
    require __DIR__ . '/../app/View/partials/footer.php';
    break;

    case 'login':
        $pageTitle = 'Valomen.gg | Login';
        $pageCss   = 'login.css';

        $loginError = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';
            $result = $loginController->login($username, $password);

            if ($result['success']) {
                header('Location: index.php');
                exit;
            } else {
                $loginError = $result['error'];
            }
        }

        require __DIR__ . '/../app/View/partials/header.php';
        require __DIR__ . '/../app/View/login.view.php';
        require __DIR__ . '/../app/View/partials/footer.php';
        break;

    case 'logout':
        $loginController->logout();
        header('Location: index.php');
        exit;

    case 'events':
        require __DIR__ . '/../app/Model/DAO/EventDAO.php';
        require_once __DIR__ . '/../lib/CurrencyFormat.php';

        $eventDao = new EventDAO($db);
        $ongoingEvents   = $eventDao->getOngoingEvents();
        $upcomingEvents  = $eventDao->getUpcomingEvents();
        $completedEvents = $eventDao->getCompletedEvents();

        $pageTitle = 'Valomen.gg | Events';
        $pageCss   = 'events.css';

        require __DIR__ . '/../app/View/partials/header.php';
        require __DIR__ . '/../app/View/events.view.php';
        require __DIR__ . '/../app/View/partials/footer.php';
        break;

    case 'matches':
        require __DIR__ . '/../app/Model/DAO/MatchDAO.php';

        $matchDao = new MatchDAO($db);

        $upcomingMatches  = $matchDao->getUpcomingMatches();
        $completedMatches = $matchDao->getCompletedMatches();

        $upcomingByDate = [];
        foreach ($upcomingMatches as $match) {
            $upcomingByDate[$match['date']][] = $match;
        }

        $completedByDate = [];
        foreach ($completedMatches as $match) {
            $completedByDate[$match['date']][] = $match;
        }

        $pageTitle = 'Valomen.gg | Matches';
        $pageCss   = 'matches.css';

        require __DIR__ . '/../app/View/partials/header.php';
        require __DIR__ . '/../app/View/matches.view.php';
        require __DIR__ . '/../app/View/partials/footer.php';
        break;

    default:
        $pageTitle = 'Valomen.gg | Home';
        $pageCss   = 'main.css';
        require __DIR__ . '/../app/View/partials/header.php';
        require __DIR__ . '/../app/View/home.view.php';
        require __DIR__ . '/../app/View/partials/footer.php';
        break;
}