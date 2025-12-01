<?php

require_once __DIR__ . '/../Model/DAO/UserDAO.php';

class LoginController
{
    private UserDAO $userDao;

    public function __construct(UserDAO $userDao)
    {
        // guardo el DAO per poder buscar usuaris a la bd
        $this->userDao = $userDao;
    }

    public function login(string $username, string $password): array
    {
        // faigs trim per si hi ha espais
        $username = trim($username);

        // comprovo que no estiguin buits
        if ($username === '' || $password === '') {
            return ['success' => false, 'error' => 'Username and password are required.'];
        }

        // busco l'usuari pel nom
        $user = $this->userDao->findByUsername($username);

        // si no existeix, mando error general
        if (!$user) {
            return ['success' => false, 'error' => 'Invalid username or password.'];
        }

        // comprovo la contrasenya amb password_verify
        if (!password_verify($password, $user['passwd_hash'])) {
            // si no coincideix, també envio l'error genèric
            return ['success' => false, 'error' => 'Invalid username or password.'];
        }

        // si tot està bé, guardo dades de l'usuari a la sessió
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['username']  = $user['username'];
        $_SESSION['is_admin']  = (bool) $user['admin'];   // miro si és admin o no
        $_SESSION['user_logo'] = $user['logo'] ?? null;   // foto de perfil si en té

        // retorno ok
        return ['success' => true];
    }

    public function logout(): void
    {
        // borro totes les dades de la sessió
        $_SESSION = [];

        // si la sessió està activa, la tanco del tot
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }
}