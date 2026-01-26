<?php

// DAOs per treballar amb usuaris i identitats OAuth
require_once __DIR__ . '/../Model/DAO/UserDAO.php';
require_once __DIR__ . '/../Model/DAO/OAuthIdentityDAO.php';

class OAuthController
{
    private PDO $db;
    private UserDAO $userDao;
    private OAuthIdentityDAO $oauthDao;

    public function __construct(PDO $db)
    {
        // Guardem la connexió i preparem els DAOs
        $this->db = $db;
        $this->userDao = new UserDAO($db);
        $this->oauthDao = new OAuthIdentityDAO($db);
    }

    /* =========================
       GOOGLE
       ========================= */

    public function googleRedirect(): void
    {
        // "State" per seguretat (evita trucs amb la sessió)
        $_SESSION['oauth_state'] = bin2hex(random_bytes(16));

        // Google demana callback absolut
        $redirectUri = full_url('auth/google/callback');

        // Paràmetres que enviem a Google per començar l'OAuth
        $params = [
            'client_id' => GOOGLE_CLIENT_ID,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $_SESSION['oauth_state'],
            'prompt' => 'select_account',
        ];

        // Redirigim l'usuari a Google
        $authUrl = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
        header('Location: ' . $authUrl);
        exit;
    }

    public function googleCallback(): void
    {
        // Comprovem que el "state" sigui el mateix que havíem guardat
        $state = $_GET['state'] ?? '';
        if (empty($_SESSION['oauth_state']) || !hash_equals($_SESSION['oauth_state'], $state)) {
            $_SESSION['profile_error'] = 'OAuth state mismatch.';
            redirect_to('login');
        }
        unset($_SESSION['oauth_state']);

        // Google ens retorna un "code"
        $code = $_GET['code'] ?? '';
        if ($code === '') {
            $_SESSION['profile_error'] = 'Google OAuth: missing code.';
            redirect_to('login');
        }

        // Canviem el "code" per un access_token
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
        }

        // Amb el token, demanem dades de l'usuari (email i id)
        $userInfo = $this->httpGetJson(
            'https://openidconnect.googleapis.com/v1/userinfo',
            ['Authorization: Bearer ' . $token['access_token']]
        );

        $providerUserId = $userInfo['sub'] ?? '';
        $email = $userInfo['email'] ?? null;

        if ($providerUserId === '' || empty($email)) {
            $_SESSION['profile_error'] = 'Google OAuth: could not read your email.';
            redirect_to('login');
        }

        // Loguem o creem/enllacem el compte
        $this->loginOrCreateFromOAuth('google', $providerUserId, $email, $userInfo['name'] ?? null);
    }

    /* =========================
       GITHUB
       ========================= */

    public function githubRedirect(): void
    {
        // State per seguretat
        $_SESSION['oauth_state'] = bin2hex(random_bytes(16));

        // Callback absolut
        $redirectUri = full_url('auth/github/callback');

        $params = [
            'client_id' => GITHUB_CLIENT_ID,
            'redirect_uri' => $redirectUri,
            'scope' => 'read:user user:email',
            'state' => $_SESSION['oauth_state'],
        ];

        // Redirigim cap a GitHub
        $authUrl = 'https://github.com/login/oauth/authorize?' . http_build_query($params);
        header('Location: ' . $authUrl);
        exit;
    }

    public function githubCallback(): void
    {
        // Comprovem state
        $state = $_GET['state'] ?? '';
        if (empty($_SESSION['oauth_state']) || !hash_equals($_SESSION['oauth_state'], $state)) {
            $_SESSION['profile_error'] = 'OAuth state mismatch.';
            redirect_to('login');
        }
        unset($_SESSION['oauth_state']);

        // GitHub ens retorna un code
        $code = $_GET['code'] ?? '';
        if ($code === '') {
            $_SESSION['profile_error'] = 'GitHub OAuth: missing code.';
            redirect_to('login');
        }

        $redirectUri = full_url('auth/github/callback');

        // Canviem el code per access_token (demanem JSON amb Accept)
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

        // Si GitHub no dona email aquí, el busquem a /user/emails
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

        // Loguem o creem/enllacem el compte
        $this->loginOrCreateFromOAuth('github', $providerUserId, $email, $user['login'] ?? null);
    }

    /* =========================
       CORE: login/crear/enllacar
       ========================= */

    private function loginOrCreateFromOAuth(string $provider, string $providerUserId, string $email, ?string $suggestedName): void
    {
        // 1) Si aquesta identitat ja existeix, entrem directament
        $identity = $this->oauthDao->findByProviderUser($provider, $providerUserId);
        if ($identity) {
            $u = $this->userDao->getUserById((int)$identity['user_id']);
            if ($u) {
                $this->setSessionFromUser($u);
                redirect_to('');
            }
        }

        // 2) Si no hi ha identitat, provem si ja existeix un usuari amb aquest email
        $existingUser = $this->userDao->findByEmail($email);
        if ($existingUser) {
            // Enllacem GitHub/Google amb el compte existent
            $this->oauthDao->createIdentity((int)$existingUser['id'], $provider, $providerUserId, $email);
            $this->setSessionFromUser($existingUser);
            redirect_to('');
        }

        // 3) Si no existeix res, creem un usuari nou
        $username = $this->makeUniqueUsername($suggestedName ?: $email);

        // Posem una contrasenya aleatòria perquè la BD la demana
        $randomPass = bin2hex(random_bytes(16));
        $userId = $this->userDao->createUser($username, $email, $randomPass, 0);

        // Guardem la identitat OAuth a la taula oauth_identities
        $this->oauthDao->createIdentity((int)$userId, $provider, $providerUserId, $email);

        // Iniciem sessió amb l'usuari acabat de crear
        $u = $this->userDao->getUserById((int)$userId);
        if ($u) {
            $this->setSessionFromUser($u);
        }

        redirect_to('');
    }

    private function makeUniqueUsername(string $base): string
    {
        // Netegem el nom per tenir un username "segur" i amb el format permès
        $base = strtolower($base);
        $base = explode('@', $base)[0];                   // si era email, agafem només abans de @
        $base = preg_replace('/[^a-z0-9._]/', '', $base); // deixem només caràcters permesos
        $base = trim($base, '._');

        if ($base === '') $base = 'user';

        // Assegurem un mínim de 4 caràcters
        if (strlen($base) < 4) {
            $base .= '0000';
        }

        $candidate = substr($base, 0, 20);

        // Si ja existeix, afegim un sufix fins trobar-ne un de lliure
        $i = 1;
        while ($this->userDao->findByUsername($candidate)) {
            $suffix = (string)random_int(10, 99);
            $candidate = substr($base, 0, 18) . $suffix;
            $i++;

            // Fallback per si passa alguna cosa rara
            if ($i > 30) {
                $candidate = 'user' . random_int(1000, 9999);
                break;
            }
        }

        return $candidate;
    }

    private function setSessionFromUser(array $user): void
    {
        // Guardem el mínim necessari a la sessió per considerar l'usuari "logejat"
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['username']  = $user['username'];
        $_SESSION['is_admin']  = (int)($user['admin'] ?? 0);
        $_SESSION['user_logo'] = $user['logo'] ?? null;
    }

    /* =========================
       HTTP helpers
       ========================= */

    private function httpPostForm(string $url, array $data, array $headers = []): array
    {
        // Petició POST simple enviant dades com formulari
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
        // Petició GET simple que espera resposta JSON
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