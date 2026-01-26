<?php

class PasswordResetController
{
    private PDO $db;
    private UserDAO $userDao;
    private PasswordResetTokenDAO $resetDao;

    public function __construct(PDO $db)
    {
        // Guardem la connexió i preparem els DAOs
        $this->db       = $db;
        $this->userDao  = new UserDAO($db);
        $this->resetDao = new PasswordResetTokenDAO($db);
    }

    /**
     * GET /forgot_password
     * Mostra el formulari per escriure l'email.
     */
    public function showForgotPasswordForm(): void
    {
        // Si ja està logejat, no té sentit fer un reset
        if (!empty($_SESSION['user_id'])) {
            redirect_to('profile');
        }

        $pageTitle = 'Valomen.gg | Forgot Password';
        $pageCss   = 'form_generic.css';

        $email = '';

        $errors = [
            'email'  => '',
            'global' => '',
        ];

        // Missatge de "t'he enviat el correu" (sense dir si l'email existeix o no)
        $sent = !empty($_GET['sent']);

        require __DIR__ . '/../View/partials/header.php';
        require __DIR__ . '/../View/forgot_password.view.php';
        require __DIR__ . '/../View/partials/footer.php';
    }

    /**
     * POST /forgot_password
     * Rep l'email, crea el token i intenta enviar el correu.
     */
    public function processForgotPasswordForm(): void
    {
        // Si ja està logejat, no té sentit fer un reset
        if (!empty($_SESSION['user_id'])) {
            redirect_to('profile');
        }

        $pageTitle = 'Valomen.gg | Forgot Password';
        $pageCss   = 'form_generic.css';

        $email = trim($_POST['email'] ?? '');

        $errors = [
            'email'  => '',
            'global' => '',
        ];

        // 1) Validem l'email
        if ($email === '') {
            $errors['email'] = 'Email is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email format is not valid.';
        }

        // Si hi ha error, tornem a mostrar el formulari
        if ($errors['email'] !== '') {
            $sent = false;
            require __DIR__ . '/../View/partials/header.php';
            require __DIR__ . '/../View/forgot_password.view.php';
            require __DIR__ . '/../View/partials/footer.php';
            return;
        }

        // 2) Busquem l'usuari per email
        $user = $this->userDao->findByEmail($email);

        // 3) Per seguretat, SEMPRE redirigim a "sent=1"
        //    (així no es pot endevinar si un email existeix o no)
        if ($user) {
            // Esborrem tokens vells i tokens caducats/usats
            $this->resetDao->deleteByUserId((int)$user['id']);
            $this->resetDao->deleteExpiredOrUsed();

            // Generem un token segur (selector + validator)
            $selector  = bin2hex(random_bytes(8));    // 16 caràcters
            $validator = bin2hex(random_bytes(32));   // 64 caràcters
            $hash      = hash('sha256', $validator);  // a la BD guardem només el hash

            $minutesValid = (int)PASSWORD_RESET_TTL_MINUTES;
            $expiresAt = (new DateTime('+' . $minutesValid . ' minutes'))->format('Y-m-d H:i:s');

            // Guardem el token a la BD (hash del validator, no el validator real)
            $this->resetDao->createToken((int)$user['id'], $selector, $hash, $expiresAt);

            // Preparem l'enllaç del correu
            $token = $selector . ':' . $validator;
            $resetLink = $this->buildAbsoluteUrl('reset_password') . '?token=' . urlencode($token);

            // Enviem el correu (si falla, mostrem un error genèric)
            $sentOk = send_password_reset_email(
                $user['email'],
                $user['username'],
                $resetLink,
                $minutesValid
            );

            if (!$sentOk) {
                // Aquí no diem si l'email existeix o no: només un missatge general
                $errors['global'] = 'We could not send the email right now. Please try again later.';

                $sent = false;
                require __DIR__ . '/../View/partials/header.php';
                require __DIR__ . '/../View/forgot_password.view.php';
                require __DIR__ . '/../View/partials/footer.php';
                return;
            }
        }

        redirect_to('forgot_password?sent=1');
    }

    /**
     * GET /reset_password?token=...
     * Mostra el formulari per posar la nova contrasenya.
     */
    public function showResetPasswordForm(): void
    {
        // Si ja està logejat, no té sentit fer un reset
        if (!empty($_SESSION['user_id'])) {
            redirect_to('profile');
        }

        $pageTitle = 'Valomen.gg | Reset Password';
        $pageCss   = 'form_generic.css';

        $token = trim($_GET['token'] ?? '');

        $errors = [
            'new_password'     => '',
            'confirm_password' => '',
            'global'           => '',
        ];

        // Validem el token abans de mostrar el formulari
        $tokenRow = $this->validateResetToken($token);
        if ($tokenRow === null) {
            $errors['global'] = 'This reset link is invalid or has expired.';
        }

        require __DIR__ . '/../View/partials/header.php';
        require __DIR__ . '/../View/reset_password.view.php';
        require __DIR__ . '/../View/partials/footer.php';
    }

    /**
     * POST /reset_password
     * Comprova el token i guarda la nova contrasenya.
     */
    public function processResetPasswordForm(): void
    {
        // Si ja està logejat, no té sentit fer un reset
        if (!empty($_SESSION['user_id'])) {
            redirect_to('profile');
        }

        $pageTitle = 'Valomen.gg | Reset Password';
        $pageCss   = 'form_generic.css';

        $token           = trim($_POST['token'] ?? '');
        $newPassword     = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        $errors = [
            'new_password'     => '',
            'confirm_password' => '',
            'global'           => '',
        ];

        // 1) Validem el token
        $tokenRow = $this->validateResetToken($token);
        if ($tokenRow === null) {
            $errors['global'] = 'This reset link is invalid or has expired.';

            require __DIR__ . '/../View/partials/header.php';
            require __DIR__ . '/../View/reset_password.view.php';
            require __DIR__ . '/../View/partials/footer.php';
            return;
        }

        // 2) Validem la nova contrasenya (mínim 8, 1 lletra, 1 número i 1 símbol)
        $validPassword = '/^(?=.*[A-Za-z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/';

        if ($newPassword === '') {
            $errors['new_password'] = 'New password is required.';
        } elseif (!preg_match($validPassword, $newPassword)) {
            $errors['new_password'] = 'Password must have at least 8 chars, 1 letter, 1 number and 1 symbol.';
        }

        if ($confirmPassword === '') {
            $errors['confirm_password'] = 'Please confirm your new password.';
        } elseif ($newPassword !== $confirmPassword) {
            $errors['confirm_password'] = 'Passwords do not match.';
        }

        // Mirem si hi ha algun error
        $hasErrors = false;
        foreach ($errors as $e) {
            if ($e !== '') { $hasErrors = true; break; }
        }

        if ($hasErrors) {
            require __DIR__ . '/../View/partials/header.php';
            require __DIR__ . '/../View/reset_password.view.php';
            require __DIR__ . '/../View/partials/footer.php';
            return;
        }

        // 3) Actualitzem la contrasenya de l'usuari
        $userId = (int)$tokenRow['user_id'];
        $this->userDao->updatePassword($userId, $newPassword);

        // 4) Marquem el token com usat (només es pot fer servir un cop)
        $this->resetDao->markAsUsed((int)$tokenRow['id']);

        // 5) Per seguretat: esborrem tokens de "remember me" antics
        $rememberDao = new RememberTokenDAO($this->db);
        $rememberDao->deleteByUserId($userId);

        redirect_to('login?reset=1');
    }

    /**
     * Munta una URL absoluta per posar-la als emails.
     * Exemple: https://domini.com/valomen/public/reset_password
     */
    private function buildAbsoluteUrl(string $path): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';

        return $scheme . '://' . $host . url($path);
    }

    /**
     * Valida el token i retorna la fila de la BD si és correcte.
     * Retorna null si és buit, incorrecte, caducat o ja s'ha usat.
     */
    private function validateResetToken(string $token): ?array
    {
        if ($token === '') {
            return null;
        }

        // El format esperat és: selector:validator
        $parts = explode(':', $token, 2);
        if (count($parts) !== 2) {
            return null;
        }

        [$selector, $validator] = $parts;

        if ($selector === '' || $validator === '') {
            return null;
        }

        // Busquem el token per selector
        $row = $this->resetDao->findBySelector($selector);
        if (!$row) {
            return null;
        }

        // Si ja s'ha usat, fora
        if (!empty($row['used_at'])) {
            return null;
        }

        // Comprovem si està caducat
        if (!empty($row['expires_at']) && $row['expires_at'] < date('Y-m-d H:i:s')) {
            return null;
        }

        // Compareu el hash del validator amb el que tenim guardat
        $calcHash = hash('sha256', $validator);

        if (!hash_equals($row['hashed_validator'], $calcHash)) {
            return null;
        }

        return $row;
    }
}