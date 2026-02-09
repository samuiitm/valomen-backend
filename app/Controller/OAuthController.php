<?php

require_once __DIR__ . '/../Model/DAO/UserDAO.php';
require_once __DIR__ . '/../Model/DAO/OAuthIdentityDAO.php';

class OAuthController
{
    private PDO $db;
    private UserDAO $userDao;
    private OAuthIdentityDAO $oauthDao;

    public function __construct(PDO $db)
    {
        $this->db = $db;
        $this->userDao = new UserDAO($db);
        $this->oauthDao = new OAuthIdentityDAO($db);
    }

    /* =========================================================
       GOOGLE (HYBRIDAUTH)
       ========================================================= */

    public function googleRedirect(): void
    {
        // Aquí comencem el login de Google
        $this->handleGoogle();
    }

    public function googleCallback(): void
    {
        // Aquí Google torna després de fer login
        $this->handleGoogle();
    }

    private function handleGoogle(): void
    {
        // Carrego Hybridauth (ve de Composer)
        $autoload = __DIR__ . '/../../vendor/autoload.php';
        if (!file_exists($autoload)) {
            $_SESSION['profile_error'] = 'Falta Hybridauth. Has de fer composer install.';
            redirect_to('login');
        }
        require_once $autoload;

        // Config bàsica de Google amb callback absolut
        $config = [
            'callback' => full_url('auth/google/callback'),
            'keys' => [
                'id' => GOOGLE_CLIENT_ID,
                'secret' => GOOGLE_CLIENT_SECRET,
            ],
            'scope' => 'openid email profile',
        ];

        try {
            // Demano a Google que autentiqui l'usuari
            $google = new \Hybridauth\Provider\Google($config);
            $google->authenticate();

            // Agafo dades del perfil
            $profile = $google->getUserProfile();

            $providerUserId = (string)($profile->identifier ?? '');
            $email = $profile->email ?? null;
            $name  = $profile->displayName ?? null;

            // Sense email no podem crear ni enllaçar el compte
            if ($providerUserId === '' || empty($email)) {
                $_SESSION['profile_error'] = 'Google OAuth: no hem pogut llegir el teu email.';
                redirect_to('login');
            }

            // Aquí fem servir el teu sistema actual per entrar o crear usuari
            $this->loginOrCreateFromOAuth('google', $providerUserId, $email, $name);
        } catch (Throwable $e) {
            $_SESSION['profile_error'] = 'Google OAuth: ' . $e->getMessage();
            redirect_to('login');
        }
    }

    /* =========================================================
       GITHUB (MANUAL)
       ========================================================= */

    public function githubRedirect(): void
    {
        // Creo un state per seguretat
        $_SESSION['oauth_state'] = bin2hex(random_bytes(16));

        // Callback absolut
        $redirectUri = full_url('auth/github/callback');

        $params = [
            'client_id' => GITHUB_CLIENT_ID,
            'redirect_uri' => $redirectUri,
            'scope' => 'read:user user:email',
            'state' => $_SESSION['oauth_state'],
        ];

        $authUrl = 'https://github.com/login/oauth/authorize?' . http_build_query($params);

        header('Location: ' . $authUrl);
        exit;
    }

    public function githubCallback(): void
    {
        // Comprovo que el state coincideix
        $state = $_GET['state'] ?? '';
        if (empty($_SESSION['oauth_state']) || !hash_equals($_SESSION['oauth_state'], $state)) {
            $_SESSION['profile_error'] = 'OAuth state mismatch.';
            redirect_to('login');
        }
        unset($_SESSION['oauth_state']);

        // GitHub torna un code
        $code = $_GET['code'] ?? '';
        if ($code === '') {
            $_SESSION['profile_error'] = 'GitHub OAuth: missing code.';
            redirect_to('login');
        }

        $redirectUri = full_url('auth/github/callback');

        // Canvio el code per un access token
        $token = $this->httpPostForm(
            'https://github.com/login/oauth/access_token',
            [
                'client_id' => GITHUB_CLIENT_ID,
                'client_secret' => GITHUB_CLIENT_SECRET,
                'code' => $code,
                'redirect_uri' => $redirectUri,
            ],
            ['Accept: application/json']
        );

        if (empty($token['access_token'])) {
            $_SESSION['profile_error'] = 'GitHub OAuth: token error.';
            redirect_to('login');
        }

        $accessToken = $token['access_token'];

        // Demano el perfil
        $user = $this->httpGetJson(
            'https://api.github.com/user',
            [
                'Authorization: Bearer ' . $accessToken,
                'User-Agent: Valomen.gg',
                'Accept: application/vnd.github+json',
            ]
        );

        $providerUserId = isset($user['id']) ? (string)$user['id'] : '';
        $email = $user['email'] ?? null;

        // Si l’email no ve aquí, el busco a /user/emails
        if (empty($email)) {
            $emails = $this->httpGetJson(
                'https://api.github.com/user/emails',
                [
                    'Authorization: Bearer ' . $accessToken,
                    'User-Agent: Valomen.gg',
                    'Accept: application/vnd.github+json',
                ]
            );

            if (is_array($emails)) {
                foreach ($emails as $e) {
                    if (!empty($e['primary']) && !empty($e['verified']) && !empty($e['email'])) {
                        $email = $e['email'];
                        break;
                    }
                }
                if (empty($email) && !empty($emails[0]['email'])) {
                    $email = $emails[0]['email'];
                }
            }
        }

        if ($providerUserId === '' || empty($email)) {
            $_SESSION['profile_error'] = 'GitHub OAuth: could not read your email.';
            redirect_to('login');
        }

        $this->loginOrCreateFromOAuth('github', $providerUserId, $email, $user['login'] ?? null);
    }

    /* =========================================================
       CORE: entrar o crear usuari
       ========================================================= */

    private function loginOrCreateFromOAuth(string $provider, string $providerUserId, string $email, ?string $suggestedName): void
    {
        // Si aquesta identitat ja existeix, entrem directament
        $identity = $this->oauthDao->findByProviderUser($provider, $providerUserId);
        if ($identity) {
            $u = $this->userDao->getUserById((int)$identity['user_id']);
            if ($u) {
                $this->setSessionFromUser($u);
                redirect_to('');
            }
        }

        // Si no hi ha identitat, miro si ja existeix usuari per email
        $existingUser = $this->userDao->findByEmail($email);

        if ($existingUser) {
            // Enllaço la identitat amb el compte existent
            $this->oauthDao->createIdentity((int)$existingUser['id'], $provider, $providerUserId, $email);
            $this->setSessionFromUser($existingUser);
            redirect_to('');
        }

        // Si no existeix res, creo un usuari nou
        $username = $this->makeUniqueUsername($suggestedName ?: $email);

        // Contrasenya aleatòria perquè la BD la demana
        $randomPass = bin2hex(random_bytes(16));
        $userId = $this->userDao->createUser($username, $email, $randomPass, 0);

        // Guardo la identitat OAuth
        $this->oauthDao->createIdentity((int)$userId, $provider, $providerUserId, $email);

        // Inicio sessió
        $u = $this->userDao->getUserById((int)$userId);
        if ($u) {
            $this->setSessionFromUser($u);
        }

        redirect_to('');
    }

    private function makeUniqueUsername(string $base): string
    {
        // Netejo el text per fer un username acceptable
        $base = strtolower($base);
        $base = explode('@', $base)[0];
        $base = preg_replace('/[^a-z0-9._]/', '', $base);
        $base = trim($base, '._');

        if ($base === '') {
            $base = 'user';
        }

        if (strlen($base) < 4) {
            $base .= '0000';
        }

        $candidate = substr($base, 0, 20);

        // Si ja existeix, vaig provant amb un sufix
        $tries = 0;
        while ($this->userDao->findByUsername($candidate)) {
            $suffix = (string)random_int(10, 99);
            $candidate = substr($base, 0, 18) . $suffix;
            $tries++;

            if ($tries > 30) {
                $candidate = 'user' . random_int(1000, 9999);
                break;
            }
        }

        return $candidate;
    }

    private function setSessionFromUser(array $user): void
    {
        // Guardo el mínim a sessió per considerar-lo logejat
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['username']  = $user['username'];
        $_SESSION['is_admin']  = (int)($user['admin'] ?? 0);
        $_SESSION['user_logo'] = $user['logo'] ?? null;
    }

    /* =========================================================
       HELPERS HTTP
       ========================================================= */

    private function httpPostForm(string $url, array $data, array $headers = []): array
    {
        $headers[] = 'Content-Type: application/x-www-form-urlencoded';

        $opts = [
            'http' => [
                'method' => 'POST',
                'header' => implode("\r\n", $headers),
                'content' => http_build_query($data),
                'ignore_errors' => true,
                'timeout' => 15,
            ],
        ];

        $res = file_get_contents($url, false, stream_context_create($opts));
        $json = json_decode($res ?: '', true);

        return is_array($json) ? $json : [];
    }

    private function httpGetJson(string $url, array $headers = []): array
    {
        $opts = [
            'http' => [
                'method' => 'GET',
                'header' => implode("\r\n", $headers),
                'ignore_errors' => true,
                'timeout' => 15,
            ],
        ];

        $res = file_get_contents($url, false, stream_context_create($opts));
        $json = json_decode($res ?: '', true);

        return is_array($json) ? $json : [];
    }
}