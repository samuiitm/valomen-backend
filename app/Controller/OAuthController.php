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

    /* =========================
       GOOGLE
       ========================= */

    public function googleRedirect(): void
    {
        // Guardem un "state" per seguretat (evita CSRF)
        $_SESSION['oauth_state'] = bin2hex(random_bytes(16));

        // Google demana una URL absoluta de callback
        $redirectUri = full_url('auth/google/callback');

        $params = [
            'client_id' => GOOGLE_CLIENT_ID,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $_SESSION['oauth_state'],
            'prompt' => 'select_account',
        ];

        // Redirigim cap a Google (això és fora del nostre projecte)
        $authUrl = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
        header('Location: ' . $authUrl);
        exit;
    }

    public function googleCallback(): void
    {
        // Comprovem que el "state" sigui correcte
        $state = $_GET['state'] ?? '';
        if (empty($_SESSION['oauth_state']) || !hash_equals($_SESSION['oauth_state'], $state)) {
            $_SESSION['profile_error'] = 'OAuth state mismatch.';
            redirect_to('login');
            exit;
        }
        unset($_SESSION['oauth_state']);

        // Si no ve el "code", no podem continuar
        $code = $_GET['code'] ?? '';
        if ($code === '') {
            $_SESSION['profile_error'] = 'Google OAuth: missing code.';
            redirect_to('login');
            exit;
        }

        // Canviem el "code" per un access token
        $redirectUri = full_url('auth/google/callback');
        $token = $this->httpPostForm('https://oauth2.googleapis.com/token', [
            'client_id' => GOOGLE_CLIENT_ID,
            'client_secret' => GOOGLE_CLIENT_SECRET,
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $redirectUri,
        ]);

        if (empty($token['access_token'])) {
            $_SESSION['profile_error'] = 'Google OAuth: token error.';
            redirect_to('login');
            exit;
        }

        // Demanem dades de l'usuari amb el token
        $userInfo = $this->httpGetJson(
            'https://openidconnect.googleapis.com/v1/userinfo',
            ['Authorization: Bearer ' . $token['access_token']]
        );

        $providerUserId = $userInfo['sub'] ?? '';
        $email = $userInfo['email'] ?? null;

        if ($providerUserId === '' || empty($email)) {
            $_SESSION['profile_error'] = 'Google OAuth: could not read your email.';
            redirect_to('login');
            exit;
        }

        $this->loginOrCreateFromOAuth('google', $providerUserId, $email, $userInfo['name'] ?? null);
    }

    /* =========================
       GITHUB
       ========================= */

    public function githubRedirect(): void
    {
        $_SESSION['oauth_state'] = bin2hex(random_bytes(16));

        // GitHub també demana callback absolut
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
        $state = $_GET['state'] ?? '';
        if (empty($_SESSION['oauth_state']) || !hash_equals($_SESSION['oauth_state'], $state)) {
            $_SESSION['profile_error'] = 'OAuth state mismatch.';
            redirect_to('login');
            exit;
        }
        unset($_SESSION['oauth_state']);

        $code = $_GET['code'] ?? '';
        if ($code === '') {
            $_SESSION['profile_error'] = 'GitHub OAuth: missing code.';
            redirect_to('login');
            exit;
        }

        $redirectUri = full_url('auth/github/callback');

        // Demanem el token (GitHub ens pot tornar JSON si enviem Accept)
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
            exit;
        }

        $accessToken = $token['access_token'];

        // Demanem el perfil bàsic
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

        // Si l'email ve buit, el busquem a /user/emails
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
            exit;
        }

        $this->loginOrCreateFromOAuth('github', $providerUserId, $email, $user['login'] ?? null);
    }

    /* =========================
       CORE
       ========================= */

    private function loginOrCreateFromOAuth(string $provider, string $providerUserId, string $email, ?string $suggestedName): void
    {
        // 1) Si ja existeix la identitat OAuth, loguem l'usuari
        $identity = $this->oauthDao->findByProviderUser($provider, $providerUserId);
        if ($identity) {
            $u = $this->userDao->getUserById((int)$identity['user_id']);
            if ($u) {
                $this->setSessionFromUser($u);
                redirect_to('');
                exit;
            }
        }

        // 2) Si no, mirem si ja hi ha un usuari amb aquest email
        $existingUser = $this->userDao->findByEmail($email);
        if ($existingUser) {
            $this->oauthDao->createIdentity((int)$existingUser['id'], $provider, $providerUserId, $email);
            $this->setSessionFromUser($existingUser);
            redirect_to('');
            exit;
        }

        // 3) Si no existeix res, creem un usuari nou
        $username = $this->makeUniqueUsername($suggestedName ?: $email);

        // Password aleatòria (no la farà servir, però la BD la demana)
        $randomPass = bin2hex(random_bytes(16));
        $userId = $this->userDao->createUser($username, $email, $randomPass, 0);

        $this->oauthDao->createIdentity((int)$userId, $provider, $providerUserId, $email);

        $u = $this->userDao->getUserById((int)$userId);
        if ($u) {
            $this->setSessionFromUser($u);
        }

        redirect_to('');
        exit;
    }

    private function makeUniqueUsername(string $base): string
    {
        // Convertim email/login en un username “net”
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

        // Si ja existeix, provem afegint números
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
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['username']  = $user['username'];
        $_SESSION['is_admin']  = (int)($user['admin'] ?? 0);
        $_SESSION['user_logo'] = $user['logo'] ?? null;
    }

    /* =========================
       HTTP HELPERS
       ========================= */

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