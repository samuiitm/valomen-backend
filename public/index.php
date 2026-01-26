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
        header('Location: login?expired=1');
        exit;
    } else {
        $_SESSION['last_activity'] = time();
    }
}

if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
}

require __DIR__ . '/../config/db-connection.php';
require __DIR__ . '/../config/recaptcha.php';
require __DIR__ . '/../config/mail.php';
require __DIR__ . '/../config/oauth.php';

require_once __DIR__ . '/../lib/DateFormat.php';
require_once __DIR__ . '/../lib/FlagControl.php';
require_once __DIR__ . '/../lib/CurrencyFormat.php';

require __DIR__ . '/../app/Helpers/url.php';
require __DIR__ . '/../app/Helpers/mailer.php';

require __DIR__ . '/../app/Model/DAO/BaseDAO.php';
require __DIR__ . '/../app/Model/DAO/UserDAO.php';
require __DIR__ . '/../app/Model/DAO/TeamDAO.php';
require __DIR__ . '/../app/Model/DAO/EventDAO.php';
require __DIR__ . '/../app/Model/DAO/MatchDAO.php';
require __DIR__ . '/../app/Model/DAO/PredictionDAO.php';
require __DIR__ . '/../app/Model/DAO/RememberTokenDAO.php';
require __DIR__ . '/../app/Model/DAO/PasswordResetTokenDAO.php';


if (empty($_SESSION['user_id']) && !empty($_COOKIE['remember_me'])) {
    $cookie = $_COOKIE['remember_me'];
    $parts  = explode(':', $cookie, 2);

    if (count($parts) === 2) {
        [$selector, $validator] = $parts;

        $tokenDao = new RememberTokenDAO($db);
        $token    = $tokenDao->findBySelector($selector);

        if ($token && $token['expires_at'] >= date('Y-m-d H:i:s')) {
            $calcHash = hash('sha256', $validator);

            if (hash_equals($token['hashed_validator'], $calcHash)) {
                $userDao = new UserDAO($db);
                $user    = $userDao->getUserById((int)$token['user_id']);

                if ($user) {
                    $_SESSION['user_id']       = (int)$user['id'];
                    $_SESSION['username']      = $user['username'];
                    $_SESSION['is_admin']      = (bool)$user['admin'];
                    $_SESSION['user_logo']     = $user['logo'] ?? null;
                    $_SESSION['edit_mode']     = $_SESSION['edit_mode'] ?? false;
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

require __DIR__ . '/../app/Controller/LoginController.php';
require __DIR__ . '/../app/Controller/RegisterController.php';
require __DIR__ . '/../app/Controller/UserProfileController.php';
require __DIR__ . '/../app/Controller/EventAdminController.php';
require __DIR__ . '/../app/Controller/PredictionController.php';
require __DIR__ . '/../app/Controller/MatchAdminController.php';
require __DIR__ . '/../app/Controller/AdminPanelController.php';
require __DIR__ . '/../app/Controller/PasswordResetController.php';

require __DIR__ . '/../app/Controller/HomeController.php';
require __DIR__ . '/../app/Controller/AuthController.php';
require __DIR__ . '/../app/Controller/EventsController.php';
require __DIR__ . '/../app/Controller/MatchesController.php';
require __DIR__ . '/../app/Controller/AdminPageController.php';
require __DIR__ . '/../app/Controller/OAuthController.php';

require __DIR__ . '/../app/Core/Router.php';
require __DIR__ . '/../routes.php';


Router::dispatch();