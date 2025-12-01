<?php

require_once __DIR__ . '/../Model/DAO/UserDAO.php';

class UserProfileController
{
    private UserDAO $userDao;

    public function __construct(PDO $db)
    {
        // creo el DAO d'usuaris amb la connexió que em passen
        $this->userDao = new UserDAO($db);
    }

    public function getProfileData(int $userId): array
    {
        // busco l'usuari per id
        $user = $this->userDao->getUserById($userId);
        if (!$user) {
            // si no existeix, el mando a la home
            header('Location: index.php?page=home');
            exit;
        }

        // retorno l'usuari i estructura bàsica per errors i success
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
        // comprovo que l'usuari loguejat coincideix amb l'id del perfil
        if (empty($_SESSION['user_id']) || (int)$_SESSION['user_id'] !== $userId) {
            return [
                'user'    => null,
                'errors'  => ['global' => 'Not authorized.'],
                'success' => false,
            ];
        }

        // torno a agafar l'usuari de la bd
        $user = $this->userDao->getUserById($userId);
        if (!$user) {
            return [
                'user'    => null,
                'errors'  => ['global' => 'User not found.'],
                'success' => false,
            ];
        }

        // agafo el nou username del formulari
        $username = trim($_POST['username'] ?? '');

        // inicialitzo els errors en blanc
        $errors = [
            'username' => '',
            'avatar'   => '',
            'global'   => '',
        ];

        // una validació molt simple del username (només que no estigui buit)
        if ($username === '') {
            $errors['username'] = 'Username is required.';
        }

        $newAvatarFilename = null;

        // miro si m'han pujat una imatge nova d'avatar
        if (!empty($_FILES['avatar']) && is_array($_FILES['avatar'])) {
            if ($_FILES['avatar']['error'] !== UPLOAD_ERR_NO_FILE) {
                // delego tota la lògica de pujada i tractament a aquesta funció
                $result = $this->handleAvatarUpload($_FILES['avatar'], $userId);
                if ($result === null) {
                    // si retorna null és que hi ha hagut algun problema
                    $errors['avatar'] = 'Invalid avatar image.';
                } else {
                    // si està bé, guardo el nom del fitxer nou
                    $newAvatarFilename = $result;
                }
            }
        }

        // comprovo si hi ha algun error
        $hasErrors = false;
        foreach ($errors as $e) {
            if ($e !== '') {
                $hasErrors = true;
                break;
            }
        }

        if ($hasErrors) {
            // si hi ha errors, preparo les dades per tornar a pintar el formulari
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

        // si no hi ha errors, decideixo quin logo quedarà al final
        $finalLogo = $newAvatarFilename !== null ? $newAvatarFilename : $user['logo'];

        // actualitzo el perfil a la base de dades
        $this->userDao->updateUserProfile($userId, $username, $finalLogo);

        // també actualitzo la sessió perquè es vegi el canvi a la navbar, etc.
        $_SESSION['username']  = $username;
        $_SESSION['user_logo'] = $finalLogo;

        // torno a carregar l'usuari actualitzat
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
        // primer miro si hi ha hagut algun error bàsic amb la pujada
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        // només accepto aquests tipus de fitxer
        $allowed = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($file['type'], $allowed, true)) {
            return null;
        }

        // ruta temporal on PHP ha guardat el fitxer
        $srcPath = $file['tmp_name'];

        // creo la imatge font segons el tipus
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

        // si no s'ha pogut crear la imatge, fallo
        if (!$src) {
            return null;
        }

        // agafo amplada i alçada originals
        $origW = imagesx($src);
        $origH = imagesy($src);

        // si per lo que sigui són 0, no té sentit continuar
        if ($origW <= 0 || $origH <= 0) {
            imagedestroy($src);
            return null;
        }

        // decideixo quin tros quadrat tallo (centre)
        if ($origW > $origH) {
            // més ample que alt → tallo pels costats
            $cropSize = $origH;
            $cropX = (int)(($origW - $origH) / 2);
            $cropY = 0;
        } else {
            // més alt que ample → tallo per dalt/baix
            $cropSize = $origW;
            $cropX = 0;
            $cropY = (int)(($origH - $origW) / 2);
        }

        // faig el crop quadrat
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

        // creo una imatge nova de mida final (per ex. 500x500)
        $finalSize = 500;
        $final = imagecreatetruecolor($finalSize, $finalSize);

        // redimensiono el quadrat al tamany final
        imagecopyresampled(
            $final,
            $crop,
            0, 0, 0, 0,
            $finalSize, $finalSize,
            $cropSize, $cropSize
        );

        // allibero memòria de les imatges temporals
        imagedestroy($src);
        imagedestroy($crop);

        // creo un nom de fitxer bastant simple i únic
        $safeName = 'user_' . $userId . '_' . time() . '.png';

        // carpeta on guardaré els avatars
        $folder = __DIR__ . '/../../public/assets/img/user-avatars/';
        if (!is_dir($folder)) {
            // si la carpeta no existeix, la creo
            mkdir($folder, 0777, true);
        }

        // ruta completa on es guardarà la imatge final
        $savePath = $folder . $safeName;

        // guardo la imatge com a PNG
        imagepng($final, $savePath);
        // allibero també aquesta imatge
        imagedestroy($final);

        // retorno només el nom del fitxer (això és el que es guarda a la bd)
        return $safeName;
    }
}