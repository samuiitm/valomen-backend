<?php

session_start();

if (empty($_SESSION['user_id']) && !empty($_COOKIE['remember_me'])) {
    $cookie = $_COOKIE['remember_me'];
    $parts  = explode(':', $cookie, 2);

    if (count($parts) === 2) {
        [$selector, $validator] = $parts;

        require __DIR__ . '/../app/Model/DAO/RememberTokenDAO.php';
        $tokenDao = new RememberTokenDAO($db);
        $token    = $tokenDao->findBySelector($selector);

        if ($token && $token['expires_at'] >= date('Y-m-d H:i:s')) {
            $calcHash = hash('sha256', $validator);

            if (hash_equals($token['hashed_validator'], $calcHash)) {
                $userDao = new UserDAO($db);
                $user    = $userDao->getUserById((int)$token['user_id']);

                if ($user) {
                    $_SESSION['user_id']    = (int)$user['id'];
                    $_SESSION['username']   = $user['username'];
                    $_SESSION['is_admin']   = (bool)$user['admin'];
                    $_SESSION['user_logo']  = $user['logo'] ?? null;
                    $_SESSION['edit_mode']  = $_SESSION['edit_mode'] ?? false;
                    $_SESSION['last_activity'] = time();
                }
            } else {
                $tokenDao->deleteBySelector($selector);
                setcookie('remember_me', '', time() - 3600, '/');
            }
        } else {
            setcookie('remember_me', '', time() - 3600, '/');
        }
    }
}

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
require __DIR__ . '/../config/recaptcha.php';
require_once __DIR__ . '/../lib/DateFormat.php';
require_once __DIR__ . '/../lib/FlagControl.php';
require_once __DIR__ . '/../lib/CurrencyFormat.php';
require __DIR__ . '/../app/Model/DAO/BaseDAO.php';
require __DIR__ . '/../app/Model/DAO/UserDAO.php';
require __DIR__ . '/../app/Model/DAO/TeamDAO.php';
require __DIR__ . '/../app/Controller/LoginController.php';

function verify_recaptcha(string $token): bool {
    if ($token === '') {
        return false;
    }

    $secret = RECAPTCHA_SECRET_KEY;

    $url = 'https://www.google.com/recaptcha/api/siteverify';
    $data = [
        'secret'   => $secret,
        'response' => $token,
        'remoteip' => $_SERVER['REMOTE_ADDR'] ?? null,
    ];

    $options = [
        'http' => [
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data),
            'timeout' => 5,
        ],
    ];

    $context  = stream_context_create($options);
    $result   = file_get_contents($url, false, $context);
    if ($result === false) {
        return false;
    }

    $json = json_decode($result, true);
    return !empty($json['success']);
}

$loginController = new LoginController(new UserDAO($db));

$page = $_GET['page'] ?? 'home';
$view = $_GET['view'] ?? 'schedule';

if ($view !== 'results') {
    $view = 'schedule';
}

switch ($page) {
    case 'profile':
    if (empty($_SESSION['user_id'])) {
        header('Location: index.php?page=login');
        exit;
    }

    require __DIR__ . '/../app/Controller/UserProfileController.php';

    $controller = new UserProfileController($db);
    $userId     = (int)$_SESSION['user_id'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = $controller->updateProfile($userId);
    } else {
        $data = $controller->getProfileData($userId);
    }

    $user    = $data['user'];
    $errors  = $data['errors'];
    $success = $data['success'];

    $pageTitle = 'Valomen.gg | Profile';
    $pageCss   = 'profile.css';

    require __DIR__ . '/../app/View/partials/header.php';
    require __DIR__ . '/../app/View/user_profile.view.php';
    require __DIR__ . '/../app/View/partials/footer.php';
    break;


    case 'matches':
        require __DIR__ . '/../app/Model/DAO/MatchDAO.php';
        require __DIR__ . '/../app/Model/DAO/PredictionDAO.php';

        $orderMatches = $_GET['order'] ?? 'date_asc';
        $validOrders = ['date_asc', 'date_desc'];
        if (!in_array($orderMatches, $validOrders, true)) {
            $orderMatches = 'date_asc';
        }

        $searchMatches = trim($_GET['search'] ?? '');

        $matchDao      = new MatchDAO($db);
        $predictionDao = new PredictionDAO($db);

        $matchDao->updateMatchStatuses();

        $perPage = filter_input(
            INPUT_GET,
            'perPage',
            FILTER_VALIDATE_INT,
            ['options' => ['default' => 5, 'min_range' => 1]]
        );

        $orderMatches = $_GET['order'] ?? 'date_asc';
        $validOrders  = ['date_asc', 'date_desc'];
        if (!in_array($orderMatches, $validOrders, true)) {
            $orderMatches = 'date_asc';
        }

        $searchMatches = trim($_GET['search'] ?? '');

        if ($view === 'results') {
            if ($searchMatches !== '') {
                $completedMatches = $matchDao->searchCompletedMatches($searchMatches, $orderMatches);

                $completedByDate = [];
                foreach ($completedMatches as $m) {
                    $completedByDate[$m['date']][] = $m;
                }

                $total        = count($completedMatches);
                $totalPages   = 1;
                $currentPage  = 1;
                $totalPagesMb = 1;
                $startPage    = 1;
                $endPage      = 1;
            } else {
                $total       = $matchDao->countCompletedMatches();
                $totalPages  = max(1, (int)ceil($total / $perPage));
                $p           = filter_input(
                    INPUT_GET,
                    'p',
                    FILTER_VALIDATE_INT,
                    ['options' => ['default' => 1, 'min_range' => 1]]
                );
                $p      = min($p, $totalPages);
                $p      = max(1, $p);
                $offset = ($p - 1) * $perPage;

                $completedMatches = $matchDao->getCompletedMatchesPaginated($perPage, $offset, $orderMatches);

                $completedByDate = [];
                foreach ($completedMatches as $m) {
                    $completedByDate[$m['date']][] = $m;
                }

                $startPage    = max(1, $p - 2);
                $endPage      = min($totalPages, $p + 4);
                $currentPage  = $p;
                $totalPagesMb = $totalPages;
            }
        } else {
            if ($searchMatches !== '') {
                $upcomingMatches = $matchDao->searchUpcomingMatches($searchMatches, $orderMatches);

                $upcomingByDate = [];
                foreach ($upcomingMatches as $m) {
                    $upcomingByDate[$m['date']][] = $m;
                }

                $total        = count($upcomingMatches);
                $totalPages   = 1;
                $currentPage  = 1;
                $totalPagesMb = 1;
                $startPage    = 1;
                $endPage      = 1;
            } else {
                $total       = $matchDao->countUpcomingMatches();
                $totalPages  = max(1, (int)ceil($total / $perPage));
                $p           = filter_input(
                    INPUT_GET,
                    'p',
                    FILTER_VALIDATE_INT,
                    ['options' => ['default' => 1, 'min_range' => 1]]
                );
                $p      = min($p, $totalPages);
                $p      = max(1, $p);
                $offset = ($p - 1) * $perPage;

                $upcomingMatches = $matchDao->getUpcomingMatchesPaginated($perPage, $offset, $orderMatches);

                $upcomingByDate = [];
                foreach ($upcomingMatches as $m) {
                    $upcomingByDate[$m['date']][] = $m;
                }

                $startPage    = max(1, $p - 2);
                $endPage      = min($totalPages, $p + 4);
                $currentPage  = $p;
                $totalPagesMb = $totalPages;
            }
        }

        $userPredictedMatchIds = [];
        if (!empty($_SESSION['user_id'])) {
            $userPredictions = $predictionDao->getPredictionsByUser((int)$_SESSION['user_id']);
            foreach ($userPredictions as $pRow) {
                $userPredictedMatchIds[(int)$pRow['match_id']] = true;
            }
        }

        function build_matches_url(int $p, int $perPage, string $view, string $order, string $search): string {
            $p       = max(1, $p);
            $perPage = max(1, $perPage);

            $params = [
                'page'    => 'matches',
                'view'    => $view,
                'p'       => $p,
                'perPage' => $perPage,
                'order'   => $order,
            ];

            if ($search !== '') {
                $params['search'] = $search;
            }

            return 'index.php?' . http_build_query($params);
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
        $pageCss   = 'elements_admin.css';

        require __DIR__ . '/../app/View/partials/header.php';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $controller->createFromPost();
        } else {
            $eventId = isset($_GET['event_id']) ? (int) $_GET['event_id'] : null;
            $controller->showCreateForm($eventId);
        }

        require __DIR__ . '/../app/View/partials/footer.php';
        break;

    case 'match_edit':
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

    $pageTitle = 'Valomen.gg | Edit match';
    $pageCss   = 'elements_admin.css';

    require __DIR__ . '/../app/View/partials/header.php';

    $matchId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    if ($matchId <= 0) {
        echo '<p>Invalid match.</p>';
    } else {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $controller->updateFromPost($matchId);
        } else {
            $controller->showEditForm($matchId);
        }
    }

    require __DIR__ . '/../app/View/partials/footer.php';
    break;

    case 'match_delete':
        if (
            empty($_SESSION['user_id']) ||
            empty($_SESSION['is_admin']) ||
            empty($_SESSION['edit_mode'])
        ) {
            header('Location: index.php?page=matches&view=schedule');
            exit;
        }

        require __DIR__ . '/../app/Controller/MatchAdminController.php';

        $controller = new MatchAdminController($db);
        $matchId    = isset($_GET['id']) ? (int)$_GET['id'] : 0;

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
        if (empty($_SESSION['user_id']) || empty($_SESSION['is_admin'])) {
            header('Location: index.php?page=home');
            exit;
        }

        $section = $_GET['section'] ?? 'users';
        if ($section !== 'users' && $section !== 'teams') {
            $section = 'users';
        }

        $searchAdmin = trim($_GET['search'] ?? '');

        $perPageAdmin = filter_input(
            INPUT_GET,
            'perPage',
            FILTER_VALIDATE_INT,
            ['options' => ['default' => 10, 'min_range' => 1]]
        );

        $pAdmin = filter_input(
            INPUT_GET,
            'p',
            FILTER_VALIDATE_INT,
            ['options' => ['default' => 1, 'min_range' => 1]]
        );

        $teamDao = new TeamDAO($db);
        $userDao = new UserDAO($db);

        if ($section === 'users') {
            $total = $userDao->countUsers($searchAdmin);
        } else {
            $total = $teamDao->countTeams($searchAdmin);
        }

        $totalPagesAdmin = max(1, (int)ceil($total / $perPageAdmin));
        $pAdmin = min($pAdmin, $totalPagesAdmin);
        $pAdmin = max(1, $pAdmin);

        $offset = ($pAdmin - 1) * $perPageAdmin;

        if ($section === 'users') {
            $users = $userDao->getUsersPaginated($perPageAdmin, $offset, $searchAdmin);
            $teams = [];
        } else {
            $teams = $teamDao->getTeamsPaginated($perPageAdmin, $offset, $searchAdmin);
            $users = [];
        }

        $startPageAdmin = max(1, $pAdmin - 2);
        $endPageAdmin   = min($totalPagesAdmin, $pAdmin + 4);

        $currentPageAdmin  = $pAdmin;
        $totalPagesAdminMb = $totalPagesAdmin;

        function build_admin_url(string $section, int $p, int $perPage, string $search = ''): string {
            $p       = max(1, $p);
            $perPage = max(1, $perPage);
            $section = $section === 'teams' ? 'teams' : 'users';
            $url = 'index.php?page=admin&section=' . urlencode($section) . '&p=' . $p . '&perPage=' . $perPage;
            if ($search !== '') {
                $url .= '&search=' . urlencode($search);
            }
            return $url;
        }

        $pageTitle = 'Valomen.gg | Admin panel';
        $pageCss   = 'admin.css';

        require __DIR__ . '/../app/View/partials/header.php';
        require __DIR__ . '/../app/View/admin.view.php';
        require __DIR__ . '/../app/View/partials/footer.php';
        break;


    case 'user_delete':
        require __DIR__ . '/../app/Controller/AdminPanelController.php';
        $controller = new AdminPanelController($db);
        $controller->deleteUser((int)($_GET['id'] ?? 0));
        break;

    case 'team_delete':
        require __DIR__ . '/../app/Controller/AdminPanelController.php';
        $controller = new AdminPanelController($db);
        $controller->deleteTeam((int)($_GET['id'] ?? 0));
        break;

    case 'user_edit':
        require __DIR__ . '/../app/Controller/AdminPanelController.php';
        $controller = new AdminPanelController($db);

        $id = (int)($_GET['id'] ?? 0);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $controller->updateUser($id);
            exit;
        }

        $data = $controller->showEditUser($id);

        $pageTitle = 'Edit user';
        $pageCss   = 'elements_admin.css';
        require __DIR__ . '/../app/View/partials/header.php';
        require __DIR__ . '/../app/View/user_edit.view.php';
        require __DIR__ . '/../app/View/partials/footer.php';
        break;
    
    case 'team_edit':
        require __DIR__ . '/../app/Controller/AdminPanelController.php';
        $controller = new AdminPanelController($db);

        $id = (int)($_GET['id'] ?? 0);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $controller->updateTeam($id);
            exit;
        }

        $data = $controller->showEditTeam($id);

        $pageTitle = 'Edit team';
        $pageCss   = 'elements_admin.css';
        require __DIR__ . '/../app/View/partials/header.php';
        require __DIR__ . '/../app/View/team_edit.view.php';
        require __DIR__ . '/../app/View/partials/footer.php';
        break;
    
    case 'team_create':
        require __DIR__ . '/../app/Controller/AdminPanelController.php';
        $controller = new AdminPanelController($db);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = $controller->createTeamFromPost();
        } else {
            $data = $controller->showCreateTeam();
        }

        $old    = $data['old'];
        $errors = $data['errors'];

        $pageTitle = 'Create team';
        $pageCss   = 'elements_admin.css';

        require __DIR__ . '/../app/View/partials/header.php';
        require __DIR__ . '/../app/View/team_create.view.php';
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

        $expired    = !empty($_GET['expired']);
        $loginError = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $captchaToken = $_POST['g-recaptcha-response'] ?? '';

            if (!verify_recaptcha($captchaToken)) {
                $loginError = 'reCAPTCHA verification failed. Please try again.';
            } else {
                $username = $_POST['username'] ?? '';
                $password = $_POST['password'] ?? '';
                $result   = $loginController->login($username, $password);

                if ($result['success']) {
                    if (!empty($_POST['remember_me'])) {
                        require __DIR__ . '/../app/Model/DAO/RememberTokenDAO.php';
                        $tokenDao = new RememberTokenDAO($db);

                        $userId = (int)($_SESSION['user_id'] ?? 0);

                        if ($userId > 0) {
                            $tokenDao->deleteByUserId($userId);

                            $selector  = bin2hex(random_bytes(8));
                            $validator = bin2hex(random_bytes(32));
                            $hash      = hash('sha256', $validator);

                            $expiresAt = (new DateTime('+30 days'))->format('Y-m-d H:i:s');

                            $tokenDao->createToken($userId, $selector, $hash, $expiresAt);

                            $cookieValue = $selector . ':' . $validator;

                            setcookie('remember_me', $cookieValue, [
                                'expires'  => time() + 60 * 60 * 24 * 30,
                                'path'     => '/',
                                'secure'   => !empty($_SERVER['HTTPS']),
                                'httponly' => true,
                                'samesite' => 'Lax',
                            ]);
                        }
                    }

                    header('Location: index.php');
                    exit;
                } else {
                    $loginError = $result['error'];
                }
            }
        }

        require __DIR__ . '/../app/View/partials/header.php';
        require __DIR__ . '/../app/View/login.view.php';
        require __DIR__ . '/../app/View/partials/footer.php';
        break;

    case 'logout':
        if (!empty($_SESSION['user_id'])) {
            require __DIR__ . '/../app/Model/DAO/RememberTokenDAO.php';
            $tokenDao = new RememberTokenDAO($db);
            $tokenDao->deleteByUserId((int)$_SESSION['user_id']);
        }

        setcookie('remember_me', '', time() - 3600, '/', '', !empty($_SERVER['HTTPS']), true);

        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
        header('Location: index.php?page=login');
        exit;

    case 'events':
        require __DIR__ . '/../app/Model/DAO/EventDAO.php';

        $eventDao = new EventDAO($db);

        $orderEvents = $_GET['order'] ?? 'date_asc';
        $validOrder  = ['date_asc','date_desc'];
        if (!in_array($orderEvents, $validOrder, true)) {
            $orderEvents = 'date_asc';
        }

        $perPageEvents = filter_input(
            INPUT_GET,
            'perPage',
            FILTER_VALIDATE_INT,
            ['options' => ['default' => 5, 'min_range' => 1]]
        );

        $searchEvents = trim($_GET['search'] ?? '');

        $totalCurrent   = $eventDao->countCurrentEvents($searchEvents);
        $totalCompleted = $eventDao->countCompletedEvents($searchEvents);

        if ($searchEvents !== '') {
            $pagesCurrent   = 1;
            $pagesCompleted = 1;
        } else {
            $pagesCurrent   = max(1, (int)ceil($totalCurrent   / $perPageEvents));
            $pagesCompleted = max(1, (int)ceil($totalCompleted / $perPageEvents));
        }

        $totalPagesEvents = max($pagesCurrent, $pagesCompleted);

        $pEvents = filter_input(
            INPUT_GET,
            'p',
            FILTER_VALIDATE_INT,
            ['options' => ['default' => 1, 'min_range' => 1]]
        );
        if ($pEvents < 1) {
            $pEvents = 1;
        }
        if ($pEvents > $totalPagesEvents) {
            $pEvents = $totalPagesEvents;
        }

        if ($searchEvents !== '') {
            $offsetCurrent   = 0;
            $offsetCompleted = 0;

            $limitCurrent    = $totalCurrent   > 0 ? $totalCurrent   : 1;
            $limitCompleted  = $totalCompleted > 0 ? $totalCompleted : 1;
        } else {
            $offsetCurrent   = ($pEvents - 1) * $perPageEvents;
            $offsetCompleted = ($pEvents - 1) * $perPageEvents;

            $limitCurrent    = $perPageEvents;
            $limitCompleted  = $perPageEvents;
        }

        $currentEvents = $eventDao->getCurrentEventsPaginated(
            $limitCurrent,
            $offsetCurrent,
            $orderEvents,
            $searchEvents !== '' ? $searchEvents : null
        );

        $completedEvents = $eventDao->getCompletedEventsPaginated(
            $limitCompleted,
            $offsetCompleted,
            $orderEvents,
            $searchEvents !== '' ? $searchEvents : null
        );

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

        $currentPageEvents  = $pEvents;
        $totalPagesEventsMb = $totalPagesEvents;

        $startPageEvents = max(1, $currentPageEvents - 2);
        $endPageEvents   = min($totalPagesEventsMb, $currentPageEvents + 4);

        function build_events_url(int $p, int $perPage, string $order): string {
            $p       = max(1, $p);
            $perPage = max(1, $perPage);
            $validOrder  = ['date_asc','date_desc'];
            if (!in_array($order, $validOrder, true)) {
                $order = 'date_asc';
            }

            return 'index.php?page=events'
                . '&p=' . $p
                . '&perPage=' . $perPage
                . '&order=' . urlencode($order);
        }

        $pageTitle = 'Valomen.gg | Events';
        $pageCss   = 'events.css';

        require __DIR__ . '/../app/View/partials/header.php';
        require __DIR__ . '/../app/View/events.view.php';
        require __DIR__ . '/../app/View/partials/footer.php';
        break;

    case 'event_create':
        if (
            empty($_SESSION['user_id']) ||
            empty($_SESSION['is_admin']) ||
            empty($_SESSION['edit_mode'])
        ) {
            header('Location: index.php?page=events');
            exit;
        }

        require __DIR__ . '/../app/Controller/EventAdminController.php';
        $controller = new EventAdminController($db);

        $pageTitle = 'Valomen.gg | Create event';
        $pageCss   = 'elements_admin.css';

        require __DIR__ . '/../app/View/partials/header.php';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $controller->createFromPost();
        } else {
            $controller->showCreateForm();
        }

        require __DIR__ . '/../app/View/partials/footer.php';
        break;

    case 'event_edit':
        if (
            empty($_SESSION['user_id']) ||
            empty($_SESSION['is_admin']) ||
            empty($_SESSION['edit_mode'])
        ) {
            header('Location: index.php?page=events');
            exit;
        }

        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) {
            header('Location: index.php?page=events');
            exit;
        }

        require __DIR__ . '/../app/Controller/EventAdminController.php';
        $controller = new EventAdminController($db);

        $pageTitle = 'Valomen.gg | Edit event';
        $pageCss   = 'elements_admin.css';

        require __DIR__ . '/../app/View/partials/header.php';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $controller->updateFromPost($id);
        } else {
            $controller->showEditForm($id);
        }

        require __DIR__ . '/../app/View/partials/footer.php';
        break;

    case 'event_delete':
        if (
            empty($_SESSION['user_id']) ||
            empty($_SESSION['is_admin']) ||
            empty($_SESSION['edit_mode'])
        ) {
            header('Location: index.php?page=events');
            exit;
        }

        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) {
            header('Location: index.php?page=events');
            exit;
        }

        require __DIR__ . '/../app/Controller/EventAdminController.php';
        $controller = new EventAdminController($db);
        $controller->deleteEvent($id);
        break;


    default:
        $pageTitle = 'Valomen.gg | Home';
        $pageCss   = 'main.css';
        require __DIR__ . '/../app/View/partials/header.php';
        require __DIR__ . '/../app/View/home.view.php';
        require __DIR__ . '/../app/View/partials/footer.php';
        break;
}