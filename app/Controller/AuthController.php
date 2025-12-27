<?php

class AuthController
{
    private PDO $db;
    private LoginController $loginController;
    private RegisterController $registerController;

    public function __construct(PDO $db)
    {
        $this->db                 = $db;
        $this->loginController    = new LoginController(new UserDAO($db));
        $this->registerController = new RegisterController(new UserDAO($db));
    }

    public function showLoginForm(): void
    {
        $pageTitle = 'Valomen.gg | Login';
        $pageCss   = 'login.css';

        $expired       = !empty($_GET['expired']);
        $loginError    = null;
        $username      = '';
        $rememberMe    = false;
        $attempts      = $_SESSION['login_attempts'] ?? 0;
        $showRecaptcha = $attempts >= 3;

        require __DIR__ . '/../View/partials/header.php';
        require __DIR__ . '/../View/login.view.php';
        require __DIR__ . '/../View/partials/footer.php';
    }

    public function processLogin(): void
    {
        $pageTitle = 'Valomen.gg | Login';
        $pageCss   = 'login.css';

        $expired       = !empty($_GET['expired']);
        $loginError    = null;
        $username      = $_POST['username'] ?? '';
        $rememberMe    = !empty($_POST['remember_me']);
        $attempts      = $_SESSION['login_attempts'] ?? 0;
        $showRecaptcha = $attempts >= 3;

        if ($attempts >= 3) {
            $captchaToken = $_POST['g-recaptcha-response'] ?? '';

            if (empty($captchaToken) || !verify_recaptcha($captchaToken)) {
                $loginError = 'Debes completar correctamente el reCAPTCHA.';
            }
        }

        if ($loginError === null) {
            $password = $_POST['password'] ?? '';
            $result   = $this->loginController->login($username, $password);

            if ($result['success']) {
                $_SESSION['login_attempts'] = 0;

                if ($rememberMe) {
                    $tokenDao = new RememberTokenDAO($this->db);

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

                header('Location: ./');
                exit;
            } else {
                $_SESSION['login_attempts'] = $attempts + 1;
                $attempts      = $_SESSION['login_attempts'];
                $showRecaptcha = $attempts >= 3;

                $loginError = $result['error'];
            }
        }

        require __DIR__ . '/../View/partials/header.php';
        require __DIR__ . '/../View/login.view.php';
        require __DIR__ . '/../View/partials/footer.php';
    }

    public function logout(): void
    {
        if (!empty($_SESSION['user_id'])) {
            $tokenDao = new RememberTokenDAO($this->db);
            $tokenDao->deleteByUserId((int)$_SESSION['user_id']);
        }

        setcookie('remember_me', '', time() - 3600, '/', '', !empty($_SERVER['HTTPS']), true);

        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
        header('Location: login');
        exit;
    }

    public function showRegisterForm(): void
    {
        $pageTitle = 'Valomen.gg | Register';
        $pageCss   = 'register.css';

        $registerErrors  = [
            'username'         => '',
            'email'            => '',
            'password'         => '',
            'confirm_password' => '',
        ];
        $registerSuccess = false;

        require __DIR__ . '/../View/partials/header.php';
        require __DIR__ . '/../View/register.view.php';
        require __DIR__ . '/../View/partials/footer.php';
    }

    public function processRegister(): void
    {
        $pageTitle = 'Valomen.gg | Register';
        $pageCss   = 'register.css';

        $username        = $_POST['username'] ?? '';
        $email           = $_POST['email'] ?? '';
        $password        = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        $result = $this->registerController->register($username, $email, $password, $confirmPassword);

        $registerErrors  = $result['errors'];
        $registerSuccess = $result['success'];

        require __DIR__ . '/../View/partials/header.php';
        require __DIR__ . '/../View/register.view.php';
        require __DIR__ . '/../View/partials/footer.php';
    }
}