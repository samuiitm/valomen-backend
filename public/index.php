<?php

session_start();

// aquí miro si l'admin vol canviar el mode edició (per crear/editar coses des del front)
if (
    isset($_GET['action']) &&
    $_GET['action'] === 'toggle_edit_mode' &&
    !empty($_SESSION['is_admin'])
) {
    // guardo l'estat actual i el canvio (true/false)
    $current = !empty($_SESSION['edit_mode']);
    $_SESSION['edit_mode'] = !$current;

    // torno a la pàgina d'on veníem (o a index si no hi ha referer)
    $redirectTo = $_SERVER['HTTP_REFERER'] ?? 'index.php';
    header('Location: ' . $redirectTo);
    exit;
}

// temps de sessió màxim (40 minuts en segons)
$sessionTimeout = 40 * 60;

// controlo el timeout de la sessió de l'usuari
if (isset($_SESSION['user_id'])) {
    // si hi ha última activitat i ha passat més de X temps, tanquem sessió
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $sessionTimeout) {
        // buido array de sessió
        $_SESSION = [];

        // també borro la cookie de sessió si s'està usant
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        // destrueixo la sessió del tot
        session_destroy();

        // redirigeixo al login amb el flag d'expirada
        header('Location: index.php?page=login&expired=1');
        exit;
    } else {
        // si encara no ha caducat, actualitzo el temps d'activitat
        $_SESSION['last_activity'] = time();
    }
}

// Comptador intents del login (per fer servir el reCAPTCHA després de X errors)
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
}

// carreguem la connexió a la base de dades i altres configs/biblioteques
require __DIR__ . '/../config/db-connection.php';
require __DIR__ . '/../config/recaptcha.php';
require_once __DIR__ . '/../lib/DateFormat.php';
require_once __DIR__ . '/../lib/FlagControl.php';
require_once __DIR__ . '/../lib/CurrencyFormat.php';
require __DIR__ . '/../app/Model/DAO/BaseDAO.php';
require __DIR__ . '/../app/Model/DAO/UserDAO.php';
require __DIR__ . '/../app/Model/DAO/TeamDAO.php';
require __DIR__ . '/../app/Controller/LoginController.php';

// gestió del "remember me" amb cookie i tokens
if (empty($_SESSION['user_id']) && !empty($_COOKIE['remember_me'])) {
    $cookie = $_COOKIE['remember_me'];
    // la cookie porta selector:validator
    $parts  = explode(':', $cookie, 2);

    if (count($parts) === 2) {
        [$selector, $validator] = $parts;

        require __DIR__ . '/../app/Model/DAO/RememberTokenDAO.php';
        $tokenDao = new RememberTokenDAO($db);
        $token    = $tokenDao->findBySelector($selector);

        // comprovo si el token existeix i no ha caducat
        if ($token && $token['expires_at'] >= date('Y-m-d H:i:s')) {
            // calculo hash del validator que ve de la cookie
            $calcHash = hash('sha256', $validator);

            // comparo el hash guardat a BD amb el calculat
            if (hash_equals($token['hashed_validator'], $calcHash)) {
                $userDao = new UserDAO($db);
                $user    = $userDao->getUserById((int)$token['user_id']);

                if ($user) {
                    // si tot quadra, reomplo la sessió com si s'hagués logat ara
                    $_SESSION['user_id']       = (int)$user['id'];
                    $_SESSION['username']      = $user['username'];
                    $_SESSION['is_admin']      = (bool)$user['admin'];
                    $_SESSION['user_logo']     = $user['logo'] ?? null;
                    $_SESSION['edit_mode']     = $_SESSION['edit_mode'] ?? false;
                    $_SESSION['last_activity'] = time();
                }
            } else {
                // si el hash no coincideix, borro el token i la cookie (seguretat)
                $tokenDao->deleteBySelector($selector);
                setcookie('remember_me', '', time() - 3600, '/');
            }
        } else {
            // si està caducat o no vàlid, borro la cookie
            setcookie('remember_me', '', time() - 3600, '/');
        }
    }
}

// funció auxiliar per validar reCAPTCHA al backend
function verify_recaptcha(string $token): bool {
    // si no hi ha token, ja retorno false
    if ($token === '') {
        return false;
    }

    $secret = RECAPTCHA_SECRET_KEY;

    // endpoint oficial de Google
    $url = 'https://www.google.com/recaptcha/api/siteverify';
    $data = [
        'secret'   => $secret,
        'response' => $token,
        'remoteip' => $_SERVER['REMOTE_ADDR'] ?? null,
    ];

    // preparo la petició POST amb stream_context
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
        // si falla la crida, jo considero que no passa el captcha
        return false;
    }

    $json = json_decode($result, true);
    // Google retorna "success" a true si és correcte
    return !empty($json['success']);
}

// controlador de login que reutilitzaré a varis llocs
$loginController = new LoginController(new UserDAO($db));

// paràmetres de routing bàsics
$page = $_GET['page'] ?? 'home';
$view = $_GET['view'] ?? 'schedule';

// només deixo dos valors possibles per view (schedule / results)
if ($view !== 'results') {
    $view = 'schedule';
}

// router principal
switch ($page) {
    case 'profile':
        // només pots veure el teu perfil si estàs logat
        if (empty($_SESSION['user_id'])) {
            header('Location: index.php?page=login');
            exit;
        }

        require __DIR__ . '/../app/Controller/UserProfileController.php';

        $controller = new UserProfileController($db);
        $userId     = (int)$_SESSION['user_id'];

        // POST per actualitzar el perfil, GET per mostrar-lo
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
        // pàgina principal de partits (schedule / results)
        require __DIR__ . '/../app/Model/DAO/MatchDAO.php';
        require __DIR__ . '/../app/Model/DAO/PredictionDAO.php';

        // ordre dels partits: data asc o desc
        $orderMatches = $_GET['order'] ?? 'date_asc';
        $validOrders  = ['date_asc', 'date_desc'];
        if (!in_array($orderMatches, $validOrders, true)) {
            $orderMatches = 'date_asc';
        }

        // text de cerca (busco per equips/event/stage)
        $searchMatches = trim($_GET['search'] ?? '');

        $matchDao      = new MatchDAO($db);
        $predictionDao = new PredictionDAO($db);

        // abans de mostrar, actualitzo els estats (Upcoming/Live/Completed)
        $matchDao->updateMatchStatuses();

        // quants partits per pàgina
        $perPage = filter_input(
            INPUT_GET,
            'perPage',
            FILTER_VALIDATE_INT,
            ['options' => ['default' => 5, 'min_range' => 1]]
        );

        // torno a validar ordre i cerca (per si de cas)
        $orderMatches = $_GET['order'] ?? 'date_asc';
        $validOrders  = ['date_asc', 'date_desc'];
        if (!in_array($orderMatches, $validOrders, true)) {
            $orderMatches = 'date_asc';
        }

        $searchMatches = trim($_GET['search'] ?? '');

        if ($view === 'results') {
            // vista de resultats (partits completats)
            if ($searchMatches !== '') {
                // si hi ha cerca, no faig paginació "real", ho mostro tot i ja
                $completedMatches = $matchDao->searchCompletedMatches($searchMatches, $orderMatches);

                // agrupo els partits per data per mostrar-los en blocs
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
                // mode normal amb paginació per resultats
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

                // agrupo per data igualment
                $completedByDate = [];
                foreach ($completedMatches as $m) {
                    $completedByDate[$m['date']][] = $m;
                }

                // càlcul de rang de pàgines per la paginació (desktop/mobile)
                $startPage    = max(1, $p - 2);
                $endPage      = min($totalPages, $p + 4);
                $currentPage  = $p;
                $totalPagesMb = $totalPages;
            }
        } else {
            // vista schedule (Upcoming + Live)
            if ($searchMatches !== '') {
                // quan hi ha cerca, també mostro sense paginar de veritat
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
                // schedule normal amb paginació
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

                // agrupo també per data
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

        // array per marcar quins partits ja té predicció l'usuari
        $userPredictedMatchIds = [];
        if (!empty($_SESSION['user_id'])) {
            $userPredictions = $predictionDao->getPredictionsByUser((int)$_SESSION['user_id']);
            foreach ($userPredictions as $pRow) {
                $userPredictedMatchIds[(int)$pRow['match_id']] = true;
            }
        }

        // funció helper per construir URLs de la paginació de matches
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
        // només admin en mode edició pot crear partits
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
            // aquí es fa tota la lògica de crear match
            $controller->createFromPost();
        } else {
            // GET → mostro el formulari, opcionalment amb event_id preseleccionat
            $eventId = isset($_GET['event_id']) ? (int) $_GET['event_id'] : null;
            $controller->showCreateForm($eventId);
        }

        require __DIR__ . '/../app/View/partials/footer.php';
        break;

    case 'match_edit':
        // igual, només admin + edit_mode
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
            // si l'id no és vàlid, mostro un missatge simple
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
        // només admin en mode edició pot eliminar partits
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
        // per fer prediccions has d'estar logat
        if (empty($_SESSION['user_id'])) {
            header('Location: index.php?page=login');
            exit;
        }

        // valido l'id del match des del GET
        if (empty($_GET['match_id']) || !ctype_digit($_GET['match_id'])) {
            header('Location: index.php?page=matches');
            exit;
        }

        $matchId = (int)$_GET['match_id'];
        $userId  = (int)$_SESSION['user_id'];

        require __DIR__ . '/../app/Controller/PredictionController.php';

        $controller = new PredictionController($db);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // guardo/edito la predicció
            $data = $controller->savePrediction($matchId, $userId);
        } else {
            // només mostrar el formulari amb dades si ja existeix predicció
            $data = $controller->showForm($matchId, $userId);
        }

        $match              = $data['match'];
        $existingPrediction = $data['existingPrediction'];
        $errors             = $data['errors'];
        $success            = $data['success'];

        $pageTitle = 'Valomen.gg | Make prediction';
        $pageCss   = 'prediction_form.css';

        require __DIR__ . '/../app/View/partials/header.php';
        require __DIR__ . '/../app/View/prediction_form.view.php';
        require __DIR__ . '/../app/View/partials/footer.php';
        break;

    case 'my_predictions':
        // llistat de totes les prediccions d'un usuari
        if (empty($_SESSION['user_id'])) {
            header('Location: index.php?page=login');
            exit;
        }

        require __DIR__ . '/../app/Model/DAO/PredictionDAO.php';

        $predictionDao    = new PredictionDAO($db);
        $userPredictions  = $predictionDao->getPredictionsByUser((int)$_SESSION['user_id']);

        // agrupo les prediccions per data del partit
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

    case 'prediction_delete':
        // només puc eliminar prediccions pròpies i si estic logat
        if (empty($_SESSION['user_id'])) {
            header('Location: index.php?page=login');
            exit;
        }

        // valido match_id del GET
        $matchId = filter_input(
            INPUT_GET,
            'match_id',
            FILTER_VALIDATE_INT,
            ['options' => ['default' => 0, 'min_range' => 1]]
        );

        if ($matchId === 0) {
            header('Location: index.php?page=my_predictions');
            exit;
        }

        require __DIR__ . '/../app/Controller/PredictionController.php';
        $controller = new PredictionController($db);
        $controller->deletePrediction($matchId, (int)$_SESSION['user_id']);

        header('Location: index.php?page=my_predictions');
        exit;

    case 'admin':
        // accés al panell d'admin només per usuaris admins
        if (empty($_SESSION['user_id']) || empty($_SESSION['is_admin'])) {
            header('Location: index.php?page=home');
            exit;
        }

        // secció del panell: usuaris o equips
        $section = $_GET['section'] ?? 'users';
        if ($section !== 'users' && $section !== 'teams') {
            $section = 'users';
        }

        $searchAdmin = trim($_GET['search'] ?? '');

        // quants elements per pàgina dins admin
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

        // trec el total en funció si estic a usuaris o equips
        if ($section === 'users') {
            $total = $userDao->countUsers($searchAdmin);
        } else {
            $total = $teamDao->countTeams($searchAdmin);
        }

        $totalPagesAdmin = max(1, (int)ceil($total / $perPageAdmin));
        $pAdmin = min($pAdmin, $totalPagesAdmin);
        $pAdmin = max(1, $pAdmin);

        $offset = ($pAdmin - 1) * $perPageAdmin;

        // carrego els registres de la pàgina actual
        if ($section === 'users') {
            $users = $userDao->getUsersPaginated($perPageAdmin, $offset, $searchAdmin);
            $teams = [];
        } else {
            $teams = $teamDao->getTeamsPaginated($perPageAdmin, $offset, $searchAdmin);
            $users = [];
        }

        // càlcul de paginació per la UI
        $startPageAdmin = max(1, $pAdmin - 2);
        $endPageAdmin   = min($totalPagesAdmin, $pAdmin + 4);

        $currentPageAdmin  = $pAdmin;
        $totalPagesAdminMb = $totalPagesAdmin;

        // helper per muntar URLs del panell d'admin amb paràmetres
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
        // ruta per borrar usuari des del panell admin
        require __DIR__ . '/../app/Controller/AdminPanelController.php';
        $controller = new AdminPanelController($db);
        $controller->deleteUser((int)($_GET['id'] ?? 0));
        break;

    case 'team_delete':
        // ruta per borrar un equip des del panell admin
        require __DIR__ . '/../app/Controller/AdminPanelController.php';
        $controller = new AdminPanelController($db);
        $controller->deleteTeam((int)($_GET['id'] ?? 0));
        break;

    case 'user_edit':
        // ruta per editar usuari (admin)
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
        // ruta per editar equip (admin)
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
        // ruta per crear equip (admin)
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
        // registre d'usuari nou
        require __DIR__ . '/../app/Controller/RegisterController.php';

        $registerController = new RegisterController(new UserDAO($db));

        // estructura bàsica d'errors i flag d'èxit
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

            // tota la validació i creació es fa al controlador
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
        // pantalla de login
        $pageTitle = 'Valomen.gg | Login';
        $pageCss   = 'login.css';

        // si ve amb expired=1 és que la sessió ha caducat
        $expired       = !empty($_GET['expired']);
        $loginError    = null;
        $username      = $_POST['username'] ?? '';
        $rememberMe    = !empty($_POST['remember_me']);
        $attempts      = $_SESSION['login_attempts'] ?? 0;
        // a partir de 3 intents fallem, mostrem reCAPTCHA
        $showRecaptcha = $attempts >= 3;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // si ja ha fet 3 intents o més, obligo a passar el reCAPTCHA
            if ($attempts >= 3) {
                $captchaToken = $_POST['g-recaptcha-response'] ?? '';

                if (empty($captchaToken) || !verify_recaptcha($captchaToken)) {
                    $loginError = 'Debes completar correctamente el reCAPTCHA.';
                }
            }

            // si el captcha (si tocava) ha anat bé, faig el login normal
            if ($loginError === null) {
                $password = $_POST['password'] ?? '';
                $result   = $loginController->login($username, $password);

                if ($result['success']) {
                    // login correcte → reinicio els intents
                    $_SESSION['login_attempts'] = 0;

                    // gestió del remember me
                    if (!empty($_POST['remember_me'])) {
                        require __DIR__ . '/../app/Model/DAO/RememberTokenDAO.php';
                        $tokenDao = new RememberTokenDAO($db);

                        $userId = (int)($_SESSION['user_id'] ?? 0);

                        if ($userId > 0) {
                            // primer esborro tokens antics d'aquest usuari
                            $tokenDao->deleteByUserId($userId);

                            // genero selector + validator (la part sensible és el validator)
                            $selector  = bin2hex(random_bytes(8));
                            $validator = bin2hex(random_bytes(32));
                            $hash      = hash('sha256', $validator);

                            // la cookie dura 30 dies
                            $expiresAt = (new DateTime('+30 days'))->format('Y-m-d H:i:s');

                            $tokenDao->createToken($userId, $selector, $hash, $expiresAt);

                            $cookieValue = $selector . ':' . $validator;

                            // creo la cookie segura per remember_me
                            setcookie('remember_me', $cookieValue, [
                                'expires'  => time() + 60 * 60 * 24 * 30,
                                'path'     => '/',
                                'secure'   => !empty($_SERVER['HTTPS']),
                                'httponly' => true,
                                'samesite' => 'Lax',
                            ]);
                        }
                    }

                    // un cop logat el porto a la home
                    header('Location: index.php');
                    exit;
                } else {
                    // si falla el login, incrementem intents i actualitzem flag recaptcha
                    $_SESSION['login_attempts'] = $attempts + 1;
                    $attempts      = $_SESSION['login_attempts'];
                    $showRecaptcha = $attempts >= 3;

                    $loginError = $result['error'];
                }
            }
        }

        require __DIR__ . '/../app/View/partials/header.php';
        require __DIR__ . '/../app/View/login.view.php';
        require __DIR__ . '/../app/View/partials/footer.php';
        break;

    case 'logout':
        // al fer logout també esborro tokens de remember_me i cookie
        if (!empty($_SESSION['user_id'])) {
            require __DIR__ . '/../app/Model/DAO/RememberTokenDAO.php';
            $tokenDao = new RememberTokenDAO($db);
            $tokenDao->deleteByUserId((int)$_SESSION['user_id']);
        }

        setcookie('remember_me', '', time() - 3600, '/', '', !empty($_SERVER['HTTPS']), true);

        // buido sessió i cookie de sessió
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        session_destroy();
        header('Location: index.php?page=login');
        exit;

    case 'events':
        // pàgina d'events (ongoing, upcoming i completed) amb paginació
        require __DIR__ . '/../app/Model/DAO/EventDAO.php';

        $eventDao = new EventDAO($db);

        // ordre d'events per data
        $orderEvents = $_GET['order'] ?? 'date_asc';
        $validOrder  = ['date_asc','date_desc'];
        if (!in_array($orderEvents, $validOrder, true)) {
            $orderEvents = 'date_asc';
        }

        // quants events per pàgina
        $perPageEvents = filter_input(
            INPUT_GET,
            'perPage',
            FILTER_VALIDATE_INT,
            ['options' => ['default' => 5, 'min_range' => 1]]
        );

        $searchEvents = trim($_GET['search'] ?? '');

        // compto els events actuals (Upcoming + Ongoing) i els completats
        $totalCurrent   = $eventDao->countCurrentEvents($searchEvents);
        $totalCompleted = $eventDao->countCompletedEvents($searchEvents);

        if ($searchEvents !== '') {
            // si hi ha cerca, no faig paginació "real"
            $pagesCurrent   = 1;
            $pagesCompleted = 1;
        } else {
            $pagesCurrent   = max(1, (int)ceil($totalCurrent   / $perPageEvents));
            $pagesCompleted = max(1, (int)ceil($totalCompleted / $perPageEvents));
        }

        // màxim de pàgines entre actuals i completats
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

        // si hi ha cerca, poso offsets a 0 i agafem tot
        if ($searchEvents !== '') {
            $offsetCurrent   = 0;
            $offsetCompleted = 0;

            $limitCurrent    = $totalCurrent   > 0 ? $totalCurrent   : 1;
            $limitCompleted  = $totalCompleted > 0 ? $totalCompleted : 1;
        } else {
            // en cas normal, faig paginació amb limit/offset
            $offsetCurrent   = ($pEvents - 1) * $perPageEvents;
            $offsetCompleted = ($pEvents - 1) * $perPageEvents;

            $limitCurrent    = $perPageEvents;
            $limitCompleted  = $perPageEvents;
        }

        // carrego events actuals (upcoming/ongoing) + completats amb la mateixa pàgina
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

        // separo els actuals en ongoing i upcoming per la UI
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

        // helper per muntar URLs d’events amb la paginació
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
        // només admin amb edit_mode pot crear un event
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
        // només admin + edit_mode
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
        // només admin + edit_mode pot eliminar events
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
        // home per defecte si la pàgina no coincideix amb cap cas
        $pageTitle = 'Valomen.gg | Home';
        $pageCss   = 'main.css';
        require __DIR__ . '/../app/View/partials/header.php';
        require __DIR__ . '/../app/View/home.view.php';
        require __DIR__ . '/../app/View/partials/footer.php';
        break;
}