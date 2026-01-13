<?php

require_once __DIR__ . '/../Model/DAO/UserDAO.php';

class UserProfileController
{
    private UserDAO $userDao;

    public function __construct(PDO $db)
    {
        $this->userDao = new UserDAO($db);
    }

    private function requireLogin(): int
    {
        if (empty($_SESSION['user_id'])) {
            redirect_to('login');
        }
        return (int)$_SESSION['user_id'];
    }

    private function loadUserOrRedirect(int $userId): array
    {
        $user = $this->userDao->getUserById($userId);
        if (!$user) {
            $_SESSION = [];
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_destroy();
            }
            redirect_to('login');
        }
        return $user;
    }

    public function showProfile(): void
    {
        $userId = $this->requireLogin();
        $user   = $this->loadUserOrRedirect($userId);

        $success = $_SESSION['profile_success'] ?? '';
        unset($_SESSION['profile_success']);

        $profileError = $_SESSION['profile_error'] ?? '';
        unset($_SESSION['profile_error']);

        // para el popup
        $pendingAvatar = $_SESSION['pending_avatar'] ?? '';

        $errors = [
            'username' => '',
            'avatar'   => '',
            'global'   => $profileError,
        ];

        $pageTitle = 'Valomen.gg | Profile';
        $pageCss   = 'profile.css';

        require __DIR__ . '/../View/partials/header.php';
        require __DIR__ . '/../View/user_profile.view.php';
        require __DIR__ . '/../View/partials/footer.php';
    }

    public function showChangeUsernameForm(): void
    {
        $userId = $this->requireLogin();
        $user   = $this->loadUserOrRedirect($userId);

        $errors = [
            'username' => '',
            'global'   => '',
        ];

        $pageTitle = 'Change username';
        $pageCss   = 'elements_admin.css';

        require __DIR__ . '/../View/partials/header.php';
        require __DIR__ . '/../View/change_username.view.php';
        require __DIR__ . '/../View/partials/footer.php';
    }

    public function changeUsernameAction(): void
    {
        $userId = $this->requireLogin();
        $user   = $this->loadUserOrRedirect($userId);

        $newUsername = trim($_POST['username'] ?? '');

        $errors = [
            'username' => '',
            'global'   => '',
        ];

        if ($newUsername === '') {
            $errors['username'] = 'Username is required.';
        } elseif (strlen($newUsername) < 4 || strlen($newUsername) > 20) {
            $errors['username'] = 'Username must be between 4 and 20 characters.';
        } elseif (!preg_match('/^[a-z0-9._]+$/', $newUsername)) {
            $errors['username'] = 'Username can only contain lowercase letters, numbers, "." or "_".';
        } elseif (preg_match('/^[._]/', $newUsername)) {
            $errors['username'] = 'Username cannot start with "." or "_".';
        } elseif (preg_match('/[._]$/', $newUsername)) {
            $errors['username'] = 'Username cannot end with "." or "_".';
        } elseif (preg_match('/[._]{2}/', $newUsername)) {
            $errors['username'] = 'Username cannot contain consecutive symbol characters.';
        } elseif ($this->userDao->isUsernameTaken($newUsername, $userId)) {
            $errors['username'] = 'That username is already taken.';
        }

        $hasErrors = false;
        foreach ($errors as $e) {
            if ($e !== '') { $hasErrors = true; break; }
        }

        if ($hasErrors) {
            $pageTitle = 'Change username';
            $pageCss   = 'elements_admin.css';
            $user['username'] = $newUsername;

            require __DIR__ . '/../View/partials/header.php';
            require __DIR__ . '/../View/change_username.view.php';
            require __DIR__ . '/../View/partials/footer.php';
            return;
        }

        $this->userDao->updateUserProfile($userId, $newUsername, $user['logo'] ?? null);
        $_SESSION['username'] = $newUsername;
        $_SESSION['profile_success'] = 'Username updated successfully.';

        redirect_to('profile');
    }

    public function showChangePasswordForm(): void
    {
        $userId = $this->requireLogin();
        $this->loadUserOrRedirect($userId);

        $errors = [
            'current_password' => '',
            'new_password'     => '',
            'confirm_password' => '',
            'global'           => '',
        ];

        $pageTitle = 'Change password';
        $pageCss   = 'elements_admin.css';

        require __DIR__ . '/../View/partials/header.php';
        require __DIR__ . '/../View/change_password.view.php';
        require __DIR__ . '/../View/partials/footer.php';
    }

    public function changePasswordAction(): void
    {
        $userId = $this->requireLogin();
        $user   = $this->loadUserOrRedirect($userId);

        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword     = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        $errors = [
            'current_password' => '',
            'new_password'     => '',
            'confirm_password' => '',
            'global'           => '',
        ];

        if ($currentPassword === '') {
            $errors['current_password'] = 'Please enter your current password.';
        } elseif (!password_verify($currentPassword, $user['passwd_hash'])) {
            $errors['current_password'] = 'Current password is incorrect.';
        }

        $validPassword = '/^(?=.*[A-Za-z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/';

        if ($newPassword === '') {
            $errors['new_password'] = 'New password is required.';
        } elseif (!preg_match($validPassword, $newPassword)) {
            $errors['new_password'] = 'Password must have at least 8 chars, 1 letter, 1 number and 1 symbol.';
        } elseif ($newPassword === $currentPassword) {
            $errors['new_password'] = 'New password must be different from current password.';
        }

        if ($confirmPassword === '') {
            $errors['confirm_password'] = 'Please confirm your new password.';
        } elseif ($newPassword !== $confirmPassword) {
            $errors['confirm_password'] = 'Passwords do not match.';
        }

        $hasErrors = false;
        foreach ($errors as $e) {
            if ($e !== '') { $hasErrors = true; break; }
        }

        if ($hasErrors) {
            $pageTitle = 'Change password';
            $pageCss   = 'elements_admin.css';

            require __DIR__ . '/../View/partials/header.php';
            require __DIR__ . '/../View/change_password.view.php';
            require __DIR__ . '/../View/partials/footer.php';
            return;
        }

        $this->userDao->updatePassword($userId, $newPassword);
        $_SESSION['profile_success'] = 'Password updated successfully.';

        redirect_to('profile');
    }

    public function uploadAvatarAction(): void
    {
        $userId = $this->requireLogin();
        $this->loadUserOrRedirect($userId);

        if (empty($_FILES['avatar']) || ($_FILES['avatar']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            $_SESSION['profile_error'] = 'Please select an image.';
            redirect_to('profile');
        }

        $newAvatarFilename = $this->handleAvatarUpload($_FILES['avatar'], $userId);

        if ($newAvatarFilename === null) {
            $_SESSION['profile_error'] = 'Invalid avatar image.';
            redirect_to('profile');
        }

        $_SESSION['pending_avatar'] = $newAvatarFilename;
        redirect_to('profile');
    }

    public function confirmAvatarAction(): void
    {
        $userId = $this->requireLogin();
        $user   = $this->loadUserOrRedirect($userId);

        $newAvatar = trim($_POST['avatar_filename'] ?? '');
        $decision  = $_POST['decision'] ?? 'cancel';

        $folder   = __DIR__ . '/../../public/assets/img/user-avatars/';
        $fullPath = $folder . $newAvatar;

        unset($_SESSION['pending_avatar']);

        if ($decision !== 'confirm') {
            if ($newAvatar !== '' && is_file($fullPath)) {
                @unlink($fullPath);
            }
            redirect_to('profile');
        }

        if ($newAvatar === '' || !is_file($fullPath)) {
            $_SESSION['profile_error'] = 'Avatar file not found.';
            redirect_to('profile');
        }

        $this->userDao->updateUserProfile($userId, $user['username'], $newAvatar);

        if (!empty($user['logo'])) {
            $oldPath = $folder . $user['logo'];
            if (is_file($oldPath)) {
                @unlink($oldPath);
            }
        }

        $_SESSION['user_logo']       = $newAvatar;
        $_SESSION['profile_success'] = 'Profile picture updated successfully.';

        redirect_to('profile');
    }

    private function handleAvatarUpload(array $file, int $userId): ?string
    {
        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) return null;

        if (!empty($file['size']) && $file['size'] > 4 * 1024 * 1024) return null;

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = $finfo ? finfo_file($finfo, $file['tmp_name']) : null;
        if ($finfo) finfo_close($finfo);

        $allowedMime = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!$mime || !in_array($mime, $allowedMime, true)) return null;

        $srcPath = $file['tmp_name'];

        $src = null;
        switch ($mime) {
            case 'image/jpeg': $src = imagecreatefromjpeg($srcPath); break;
            case 'image/png':  $src = imagecreatefrompng($srcPath);  break;
            case 'image/gif':  $src = imagecreatefromgif($srcPath);  break;
            case 'image/webp': $src = imagecreatefromwebp($srcPath); break;
        }
        if (!$src) return null;

        $origW = imagesx($src);
        $origH = imagesy($src);
        if ($origW <= 0 || $origH <= 0) { imagedestroy($src); return null; }

        if ($origW > $origH) {
            $cropSize = $origH;
            $cropX    = (int)(($origW - $origH) / 2);
            $cropY    = 0;
        } else {
            $cropSize = $origW;
            $cropX    = 0;
            $cropY    = (int)(($origH - $origW) / 2);
        }

        $crop = imagecrop($src, [
            'x' => $cropX, 'y' => $cropY,
            'width' => $cropSize, 'height' => $cropSize
        ]);

        imagedestroy($src);
        if (!$crop) return null;

        $finalSize = 500;
        $final = imagecreatetruecolor($finalSize, $finalSize);

        imagecopyresampled($final, $crop, 0,0,0,0, $finalSize,$finalSize, $cropSize,$cropSize);
        imagedestroy($crop);

        $safeName = 'user_' . $userId . '_' . time() . '.png';
        $folder   = __DIR__ . '/../../public/assets/img/user-avatars/';

        if (!is_dir($folder)) mkdir($folder, 0777, true);

        imagepng($final, $folder . $safeName);
        imagedestroy($final);

        return $safeName;
    }
}
