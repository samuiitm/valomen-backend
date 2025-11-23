<?php

require_once __DIR__ . '/../Model/DAO/UserDAO.php';

class LoginController
{
    private UserDAO $userDao;

    public function __construct(UserDAO $userDao)
    {
        $this->userDao = $userDao;
    }

    public function login(string $username, string $password): array
    {
        $username = trim($username);

        if ($username === '' || $password === '') {
            return ['success' => false, 'error' => 'Username and password are required.'];
        }

        $user = $this->userDao->findByUsername($username);

        if (!$user) {
            return ['success' => false, 'error' => 'Invalid username or password.'];
        }

        if (!password_verify($password, $user['passwd_hash'])) {
            return ['success' => false, 'error' => 'Invalid username or password.'];
        }

        $_SESSION['user_id']  = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['is_admin'] = (bool) $user['admin'];
        $_SESSION['user_logo'] = $user['logo'] ?? null;

        return ['success' => true];
    }

    public function logout(): void
    {
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    public static function requireAdmin(): void
    {
        if (empty($_SESSION['user_id']) || empty($_SESSION['is_admin'])) {
            http_response_code(403);
            echo 'Access denied.';
            exit;
        }
    }
}