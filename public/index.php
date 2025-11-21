<?php

session_start();

if (
    isset($_GET['action']) &&
    $_GET['action'] === 'toggle_edit_mode' &&
    !empty($_SESSION['is_admin'])
) {
    $current = !empty($_SESSION['edit_mode']);
    $_SESSION['edit_mode'] = !$current;

    $redirectTo = $_SERVER['HTTP_REFERER'] ?? 'index.php';
    header('Location: ' . $redirectTo);
    exit;
}

$sessionTimeout = 40 * 60;

if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $sessionTimeout) {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
        header('Location: index.php?page=login&expired=1');
        exit;
    } else {
        $_SESSION['last_activity'] = time();
    }
}

require __DIR__ . '/../config/db-connection.php';
require_once __DIR__ . '/../lib/DateFormat.php';
require_once __DIR__ . '/../lib/FlagControl.php';
require_once __DIR__ . '/../lib/CurrencyFormat.php';
require __DIR__ . '/../app/Model/DAO/BaseDAO.php';
require __DIR__ . '/../app/Model/DAO/UserDAO.php';
require __DIR__ . '/../app/Controller/LoginController.php';


$loginController = new LoginController(new UserDAO($db));

$page = $_GET['page'] ?? 'home';
$view = $_GET['view'] ?? 'schedule';

if ($view !== 'results') {
    $view = 'schedule';
}

switch ($page) {
    case 'matches':
        require __DIR__ . '/../app/Model/DAO/MatchDAO.php';
        require __DIR__ . '/../app/Model/DAO/PredictionDAO.php';

        $matchDao      = new MatchDAO($db);
        $predictionDao = new PredictionDAO($db);

        $matchDao->updateMatchStatuses();

        $perPage = filter_input(
            INPUT_GET,
            'perPage',
            FILTER_VALIDATE_INT,
            ['options' => ['default' => 5, 'min_range' => 1]]
        );

        if ($view === 'results') {
            $total       = $matchDao->countCompletedMatches();
            $totalPages  = max(1, (int)ceil($total / $perPage));
            $p           = filter_input(INPUT_GET, 'p', FILTER_VALIDATE_INT, ['options' => ['default' => 1,'min_range' => 1]]);
            $p           = min($p, $totalPages);
            $offset      = ($p - 1) * $perPage;

            $completedMatches = $matchDao->getCompletedMatchesPaginated($perPage, $offset);

            $completedByDate = [];
            foreach ($completedMatches as $m) {
                $completedByDate[$m['date']][] = $m;
            }

            $startPage = max(1, $p - 2);
            $endPage   = min($totalPages, $p + 4);

            $currentPage  = $p;
            $totalPagesMb = $totalPages;
        } else {
            $total       = $matchDao->countUpcomingMatches();
            $totalPages  = max(1, (int)ceil($total / $perPage));
            $p           = filter_input(INPUT_GET, 'p', FILTER_VALIDATE_INT, ['options' => ['default' => 1,'min_range' => 1]]);
            $p           = min($p, $totalPages);
            $offset      = ($p - 1) * $perPage;

            $upcomingMatches = $matchDao->getUpcomingMatchesPaginated($perPage, $offset);

            $upcomingByDate = [];
            foreach ($upcomingMatches as $m) {
                $upcomingByDate[$m['date']][] = $m;
            }

            $startPage = max(1, $p - 2);
            $endPage   = min($totalPages, $p + 4);

            $currentPage  = $p;
            $totalPagesMb = $totalPages;
        }

        $userPredictedMatchIds = [];
        if (!empty($_SESSION['user_id'])) {
            $userPredictions = $predictionDao->getPredictionsByUser((int)$_SESSION['user_id']);
            foreach ($userPredictions as $pRow) {
                $userPredictedMatchIds[(int)$pRow['match_id']] = true;
            }
        }

        function build_matches_url(int $p, int $perPage, string $view): string {
            $p       = max(1, $p);
            $perPage = max(1, $perPage);
            return 'index.php?page=matches&view=' . urlencode($view) .
                '&p=' . $p . '&perPage=' . $perPage;
        }

        $pageTitle = 'Valomen.gg | Matches';
        $pageCss   = 'matches.css';

        require __DIR__ . '/../app/View/partials/header.php';
        require __DIR__ . '/../app/View/matches.view.php';
        require __DIR__ . '/../app/View/partials/footer.php';
        break;

    case 'match_create':
        if (
            empty($_SESSION['user_id']) ||
            empty($_SESSION['is_admin']) ||
            empty($_SESSION['edit_mode'])
        ) {
            header('Location: index.php?page=matches');
            exit;
        }

        require __DIR__ . '/../app/Controller/MatchAdminController.php';

        $controller = new MatchAdminController($db);

        $pageTitle = 'Valomen.gg | Create match';
        $pageCss   = 'match_admin.css';

        require __DIR__ . '/../app/View/partials/header.php';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $controller->createFromPost();
        } else {
            $eventId = isset($_GET['event_id']) ? (int) $_GET['event_id'] : null;
            $controller->showCreateForm($eventId);
        }

        require __DIR__ . '/../app/View/partials/footer.php';
        break;

    case 'match_delete':
        if (
            empty($_SESSION['user_id']) ||
            empty($_SESSION['is_admin']) ||
            empty($_SESSION['edit_mode'])
        ) {
            header('Location: index.php?page=matches');
            exit;
        }

        if (empty($_GET['id'])) {
            header('Location: index.php?page=matches');
            exit;
        }

        require __DIR__ . '/../app/Controller/MatchAdminController.php';
        $controller = new MatchAdminController($db);

        $matchId = (int) $_GET['id'];
        $controller->deleteMatch($matchId);
        break;

    case 'predict':
        if (empty($_SESSION['user_id'])) {
            header('Location: index.php?page=login');
            exit;
        }

        if (empty($_GET['match_id']) || !ctype_digit($_GET['match_id'])) {
            header('Location: index.php?page=matches');
            exit;
        }

        $matchId = (int) $_GET['match_id'];

        require __DIR__ . '/../app/Model/DAO/MatchDAO.php';

        $matchDao = new MatchDAO($db);
        $match    = $matchDao->getMatchById($matchId);

        if (!$match) {
            header('Location: index.php?page=matches');
            exit;
        }

        $pageTitle = 'Valomen.gg | Make prediction';
        $pageCss   = 'prediction_form.css';

        require __DIR__ . '/../app/View/partials/header.php';
        require __DIR__ . '/../app/View/prediction_form.view.php';
        require __DIR__ . '/../app/View/partials/footer.php';
        break;

    case 'my_predictions':
        if (empty($_SESSION['user_id'])) {
            header('Location: index.php?page=login');
            exit;
        }

        require __DIR__ . '/../app/Model/DAO/PredictionDAO.php';

        $predictionDao    = new PredictionDAO($db);
        $userPredictions  = $predictionDao->getPredictionsByUser((int)$_SESSION['user_id']);

        $predictionsByDate = [];
        foreach ($userPredictions as $prediction) {
            $predictionsByDate[$prediction['date']][] = $prediction;
        }

        $pageTitle = 'Valomen.gg | My Predictions';
        $pageCss   = 'my_predictions.css';

        require __DIR__ . '/../app/View/partials/header.php';
        require __DIR__ . '/../app/View/my_predictions.view.php';
        require __DIR__ . '/../app/View/partials/footer.php';
        break;

    case 'admin':
        if (empty($_SESSION['is_admin'])) {
            header('Location: index.php?page=home');
            exit;
        }

        $pageTitle = 'Valomen.gg | Admin';
        $pageCss   = 'admin.css';

        require __DIR__ . '/../app/View/partials/header.php';
        require __DIR__ . '/../app/View/admin.view.php';
        require __DIR__ . '/../app/View/partials/footer.php';
        break;

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

        $expired = !empty($_GET['expired']);

        $loginError = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';
            $result = $loginController->login($username, $password);

            if ($result['success']) {
                header('Location: index.php');
                $_SESSION['user_id']   = (int)$user['id'];
                $_SESSION['username']  = $user['username'];
                $_SESSION['is_admin']  = (int)$user['admin'] === 1;
                $_SESSION['edit_mode'] = $_SESSION['edit_mode'] ?? false;
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

        $eventDao = new EventDAO($db);

        $perPageEvents = filter_input(
            INPUT_GET,
            'perPage',
            FILTER_VALIDATE_INT,
            ['options' => ['default' => 5, 'min_range' => 1]]
        );

        $totalCurrent    = $eventDao->countCurrentEvents();
        $totalCompleted  = $eventDao->countCompletedEvents();

        $pagesCurrent   = max(1, (int)ceil($totalCurrent   / $perPageEvents));
        $pagesCompleted = max(1, (int)ceil($totalCompleted / $perPageEvents));

        $totalPagesEvents = max($pagesCurrent, $pagesCompleted);

        $pEvents = filter_input(
            INPUT_GET,
            'p',
            FILTER_VALIDATE_INT,
            ['options' => ['default' => 1, 'min_range' => 1]]
        );
        $pEvents = min($pEvents, $totalPagesEvents);
        $pEvents = max(1, $pEvents);

        $offsetCurrent   = ($pEvents - 1) * $perPageEvents;
        $offsetCompleted = ($pEvents - 1) * $perPageEvents;

        $currentEvents   = $eventDao->getCurrentEventsPaginated($perPageEvents, $offsetCurrent);
        $completedEvents = $eventDao->getCompletedEventsPaginated($perPageEvents, $offsetCompleted);

        $ongoingEvents  = [];
        $upcomingEvents = [];

        foreach ($currentEvents as $ev) {
            $status = strtolower((string)$ev['status']);
            if ($status === 'ongoing') {
                $ongoingEvents[] = $ev;
            } else {
                $upcomingEvents[] = $ev;
            }
        }

        $startPageEvents = max(1, $pEvents - 2);
        $endPageEvents   = min($totalPagesEvents, $pEvents + 4);

        $currentPageEvents  = $pEvents;
        $totalPagesEventsMb = $totalPagesEvents;

        function build_events_url(int $p, int $perPage): string {
            $p       = max(1, $p);
            $perPage = max(1, $perPage);
            return 'index.php?page=events&p=' . $p . '&perPage=' . $perPage;
        }

        $pageTitle = 'Valomen.gg | Events';
        $pageCss   = 'events.css';

        require __DIR__ . '/../app/View/partials/header.php';
        require __DIR__ . '/../app/View/events.view.php';
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