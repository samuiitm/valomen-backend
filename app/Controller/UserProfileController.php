<?php

require_once __DIR__ . '/../Model/DAO/UserDAO.php';

class UserProfileController
{
    private UserDAO $userDao;

    public function __construct(PDO $db)
    {
        $this->userDao = new UserDAO($db);
    }

    public function getProfileData(int $userId): array
    {
        $user = $this->userDao->getUserById($userId);
        if (!$user) {
            header('Location: index.php?page=home');
            exit;
        }

        return [
            'user'    => $user,
            'errors'  => [
                'username' => '',
                'avatar'   => '',
                'global'   => '',
            ],
            'success' => false,
        ];
    }

    public function updateProfile(int $userId): array
    {
        if (empty($_SESSION['user_id']) || (int)$_SESSION['user_id'] !== $userId) {
            return [
                'user'    => null,
                'errors'  => ['global' => 'Not authorized.'],
                'success' => false,
            ];
        }

        $user = $this->userDao->getUserById($userId);
        if (!$user) {
            return [
                'user'    => null,
                'errors'  => ['global' => 'User not found.'],
                'success' => false,
            ];
        }

        $username = trim($_POST['username'] ?? '');

        $errors = [
            'username' => '',
            'avatar'   => '',
            'global'   => '',
        ];

        if ($username === '') {
            $errors['username'] = 'Username is required.';
        }

        $newAvatarFilename = null;

        if (!empty($_FILES['avatar']) && is_array($_FILES['avatar'])) {
            if ($_FILES['avatar']['error'] !== UPLOAD_ERR_NO_FILE) {
                $result = $this->handleAvatarUpload($_FILES['avatar'], $userId);
                if ($result === null) {
                    $errors['avatar'] = 'Invalid avatar image.';
                } else {
                    $newAvatarFilename = $result;
                }
            }
        }

        $hasErrors = false;
        foreach ($errors as $e) {
            if ($e !== '') {
                $hasErrors = true;
                break;
            }
        }

        if ($hasErrors) {
            $userData = [
                'id'       => $user['id'],
                'username' => $username !== '' ? $username : $user['username'],
                'email'    => $user['email'],
                'logo'     => $user['logo'],
            ];

            return [
                'user'    => $userData,
                'errors'  => $errors,
                'success' => false,
            ];
        }

        $finalLogo = $newAvatarFilename !== null ? $newAvatarFilename : $user['logo'];

        $this->userDao->updateUserProfile($userId, $username, $finalLogo);

        $_SESSION['username']  = $username;
        $_SESSION['user_logo'] = $finalLogo;

        $updatedUser = $this->userDao->getUserById($userId);

        return [
            'user'    => $updatedUser,
            'errors'  => [
                'username' => '',
                'avatar'   => '',
                'global'   => '',
            ],
            'success' => true,
        ];
    }

    private function handleAvatarUpload(array $file, int $userId): ?string
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $allowed = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($file['type'], $allowed, true)) {
            return null;
        }

        $srcPath = $file['tmp_name'];

        switch ($file['type']) {
            case 'image/jpeg':
                $src = imagecreatefromjpeg($srcPath);
                break;
            case 'image/png':
                $src = imagecreatefrompng($srcPath);
                break;
            case 'image/gif':
                $src = imagecreatefromgif($srcPath);
                break;
            default:
                return null;
        }

        if (!$src) {
            return null;
        }

        $origW = imagesx($src);
        $origH = imagesy($src);

        if ($origW <= 0 || $origH <= 0) {
            imagedestroy($src);
            return null;
        }

        if ($origW > $origH) {
            $cropSize = $origH;
            $cropX = (int)(($origW - $origH) / 2);
            $cropY = 0;
        } else {
            $cropSize = $origW;
            $cropX = 0;
            $cropY = (int)(($origH - $origW) / 2);
        }

        $crop = imagecrop($src, [
            'x' => $cropX,
            'y' => $cropY,
            'width' => $cropSize,
            'height' => $cropSize
        ]);

        if (!$crop) {
            imagedestroy($src);
            return null;
        }

        $finalSize = 500;
        $final = imagecreatetruecolor($finalSize, $finalSize);

        imagecopyresampled(
            $final,
            $crop,
            0, 0, 0, 0,
            $finalSize, $finalSize,
            $cropSize, $cropSize
        );

        imagedestroy($src);
        imagedestroy($crop);

        $safeName = 'user_' . $userId . '_' . time() . '.png';

        $folder = __DIR__ . '/../../public/assets/img/user-avatars/';
        if (!is_dir($folder)) {
            mkdir($folder, 0777, true);
        }

        $savePath = $folder . $safeName;

        imagepng($final, $savePath);
        imagedestroy($final);

        return $safeName;
    }
}