<?php

require_once __DIR__ . '/../Model/DAO/UserDAO.php';
require_once __DIR__ . '/../Model/DAO/TeamDAO.php';

class AdminPanelController
{
    private UserDAO $userDao;
    private TeamDAO $teamDao;
    private PDO $db;

    public function __construct(PDO $db)
    {
        // guardo la connexió a la bd per després
        $this->db      = $db;
        // creo els DAO d'usuaris i equips per poder cridar les funcions
        $this->userDao = new UserDAO($db);
        $this->teamDao = new TeamDAO($db);
    }

    public function deleteUser(int $id): void
    {
        if (empty($_SESSION['user_id']) || empty($_SESSION['is_admin'])) {
            header('Location: ../');
            exit;
        }

        // si l'id no és vàlid, torno a la pàgina d'usuaris
        if ($id <= 0) {
            header('Location: ../admin?section=users');
            exit;
        }

        // no deixo que un usuari es borri ell mateix
        if (!empty($_SESSION['user_id']) && (int)$_SESSION['user_id'] === $id) {
            header('Location: ../admin?section=users');
            exit;
        }

        // crido al DAO per eliminar l'usuari de la base de dades
        $this->userDao->deleteUserById($id);

        // un cop eliminat, torno al llistat d'usuaris
        header('Location: ../admin?section=users');
        exit;
    }

    public function deleteTeam(int $id): void
    {
        if (empty($_SESSION['user_id']) || empty($_SESSION['is_admin'])) {
            header('Location: ../');
            exit;
        }

        // comprovo que l'id d'equip sigui correcte
        if ($id <= 0) {
            header('Location: ../admin?section=teams');
            exit;
        }

        // elimino l'equip amb el DAO
        $this->teamDao->deleteTeamById($id);

        // torno al llistat d'equips
        header('Location: ../admin?section=teams');
        exit;
    }

    public function showEditUser(int $id): array
    {
        if (empty($_SESSION['user_id']) || empty($_SESSION['is_admin'])) {
            header('Location: ../');
            exit;
        }
        
        // busco l'usuari a la bd
        $user = $this->userDao->getUserById($id);
        if (!$user) {
            // si no existeix, el envio a la llista d'usuaris
            header('Location: ../admin?section=users');
            exit;
        }

        // old tindrà els valors actuals per omplir el formulari
        $old = [
            'username' => $user['username'],
            'email'    => $user['email'],
            'points'   => (string)($user['points'] ?? 0),
            'admin'    => (string)($user['admin'] ?? 0),
        ];

        // array d'errors buits per començar
        $errors = [
            'username' => '',
            'email'    => '',
            'points'   => '',
            'admin'    => '',
            'global'   => '',
        ];

        // retorno tot el que la vista necessita
        return [
            'old'    => $old,
            'errors' => $errors,
            'user'   => $user,
        ];
    }

    public function updateUser(int $id): void
    {
        if (empty($_SESSION['user_id']) || empty($_SESSION['is_admin'])) {
            header('Location: ../');
            exit;
        }

        // comprovo que l'usuari existeix abans d'editar
        $user = $this->userDao->getUserById($id);
        if (!$user) {
            header('Location: ../admin?section=users');
            exit;
        }

        // agafo les dades del formulari i les netejo una mica
        $username = trim($_POST['username'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $points   = trim($_POST['points'] ?? '');
        // checkbox → 1 si està marcat, sinó 0
        $isAdmin  = isset($_POST['admin']) ? 1 : 0;

        // inicialitzo errors en blanc
        $errors = [
            'username' => '',
            'email'    => '',
            'points'   => '',
            'admin'    => '',
            'global'   => '',
        ];

        // validació de username
        if ($username === '') {
            $errors['username'] = 'Username is required.';
        } else {
            // miro si ja hi ha un altre usuari amb aquest username
            $existing = $this->userDao->findByUsername($username);
            if ($existing && (int)$existing['id'] !== $id) {
                $errors['username'] = 'This username is already taken.';
            }
        }

        // validació d'email
        if ($email === '') {
            $errors['email'] = 'Email is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email.';
        } else {
            // comprovo que cap altre usuari tingui aquest email
            $existing = $this->userDao->findByEmail($email);
            if ($existing && (int)$existing['id'] !== $id) {
                $errors['email'] = 'This email is already in use.';
            }
        }

        // validació de punts
        if ($points === '') {
            $errors['points'] = 'Points are required.';
        } elseif (!ctype_digit($points) || (int)$points < 0) {
            // només deixo enters positius o 0
            $errors['points'] = 'Points must be a non-negative integer.';
        }

        // miro si hi ha algun error
        $hasErrors = false;
        foreach ($errors as $e) {
            if ($e !== '') {
                $hasErrors = true;
                break;
            }
        }

        // guardo el que ha posat l'admin per poder-ho tornar a pintar a la vista
        $old = [
            'username' => $username,
            'email'    => $email,
            'points'   => $points,
            'admin'    => $isAdmin ? '1' : '0',
        ];

        if ($hasErrors) {
            // si hi ha errors torno a carregar la vista d'edició amb errors i old
            $data = [
                'old'    => $old,
                'errors' => $errors,
                'user'   => $user,
            ];

            $pageTitle = 'Edit user';
            $pageCss   = 'admin.css';

            require __DIR__ . '/../View/partials/header.php';
            require __DIR__ . '/../View/user_edit.view.php';
            require __DIR__ . '/../View/partials/footer.php';
            return;
        }

        // si tot està bé, actualitzo l'usuari a la bd
        $this->userDao->updateUser(
            $id,
            $username,
            $email,
            (int)$points,
            $isAdmin
        );

        // després de guardar, torno al llistat d'usuaris
        header('Location: ../admin?section=users');
        exit;
    }

    public function showEditTeam(int $id): array
    {
        if (empty($_SESSION['user_id']) || empty($_SESSION['is_admin'])) {
            header('Location: ../');
            exit;
        }
    
        // busco l'equip per id
        $team = $this->teamDao->getTeamById($id);
        if (!$team) {
            // si no existeix, torno a la secció d'equips
            header('Location: ../admin?section=teams');
            exit;
        }

        // valors inicials del formulari
        $old = [
            'name'    => $team['name'],
            'country' => $team['country'],
        ];

        // inicialitzo errors buits
        $errors = [
            'name'    => '',
            'country' => '',
            'global'  => '',
        ];

        // retorno dades perquè la vista pugui pintar el formulari
        return [
            'old'    => $old,
            'errors' => $errors,
            'team'   => $team,
        ];
    }

    public function updateTeam(int $id): void
    {
        if (empty($_SESSION['user_id']) || empty($_SESSION['is_admin'])) {
            header('Location: ../');
            exit;
        }

        // comprovo que l'equip existeix
        $team = $this->teamDao->getTeamById($id);
        if (!$team) {
            header('Location: ../admin?section=teams');
            exit;
        }

        // llegeixo els camps del formulari
        $name    = trim($_POST['name'] ?? '');
        $country = trim($_POST['country'] ?? '');

        // preparo array d'errors
        $errors = [
            'name'    => '',
            'country' => '',
            'global'  => '',
        ];

        // validació del nom
        if ($name === '') {
            $errors['name'] = 'Name is required.';
        }

        // validació del codi de país
        if ($country === '') {
            $errors['country'] = 'Country code is required.';
        } elseif (strlen($country) > 5) {
            // per no deixar valors molt llargs aquí
            $errors['country'] = 'Country code is too long.';
        }

        // miro si hi ha algun error
        $hasErrors = false;
        foreach ($errors as $e) {
            if ($e !== '') {
                $hasErrors = true;
                break;
            }
        }

        // guardo el que s'ha escrit per si hem de tornar a mostrar el formulari
        $old = [
            'name'    => $name,
            'country' => $country,
        ];

        if ($hasErrors) {
            // hi ha errors → torno a la vista d'edició amb old + errors
            $data = [
                'old'    => $old,
                'errors' => $errors,
                'team'   => $team,
            ];

            $pageTitle = 'Edit team';
            $pageCss   = 'admin.css';

            require __DIR__ . '/../View/partials/header.php';
            require __DIR__ . '/../View/team_edit.view.php';
            require __DIR__ . '/../View/partials/footer.php';
            return;
        }

        // tot correcte, actualitzo l'equip a la base de dades
        $this->teamDao->updateTeam(
            $id,
            $name,
            $country
        );

        // redirecció al llistat d'equips
        header('Location: ../admin?section=teams');
        exit;
    }

    public function showCreateTeam(): array
    {
        if (empty($_SESSION['user_id']) || empty($_SESSION['is_admin'])) {
            header('Location: ../');
            exit;
        }

        // valors buits per quan creem un equip nou
        return [
            'old'    => [
                'name'    => '',
                'country' => '',
            ],
            'errors' => [
                'name'    => '',
                'country' => '',
                'global'  => '',
            ]
        ];
    }

    public function createTeamFromPost(): array
    {
        if (empty($_SESSION['user_id']) || empty($_SESSION['is_admin'])) {
            header('Location: ../');
            exit;
        }

        // agafo els valors del formulari de creació
        $name    = trim($_POST['name'] ?? '');
        $country = trim($_POST['country'] ?? '');

        // inicialitzo errors
        $errors = [
            'name'    => '',
            'country' => '',
            'global'  => '',
        ];

        // comprovo que el nom no estigui buit
        if ($name === '') {
            $errors['name'] = 'Name is required.';
        }

        // comprovo que el país no estigui buit
        if ($country === '') {
            $errors['country'] = 'Country is required.';
        }

        // miro si hi ha errors
        $hasErrors = false;
        foreach ($errors as $e) {
            if ($e !== '') {
                $hasErrors = true;
                break;
            }
        }

        if ($hasErrors) {
            // si hi ha errors, retorno old + errors perquè la vista els mostri
            return [
                'old'    => [
                    'name'    => $name,
                    'country' => $country,
                ],
                'errors' => $errors
            ];
        }

        // si tot bé, creo l'equip amb el DAO
        $teamDao = new TeamDAO($this->db);
        $teamDao->createTeam($name, $country);

        // i redirigeixo a la secció d'equips
        header('Location: ../admin?section=teams');
        exit;
    }

    public function deleteUserAction(): void
    {
        if (empty($_SESSION['user_id']) || empty($_SESSION['is_admin'])) {
            header('Location: ../');
            exit;
        }

        $id = (int)($_GET['id'] ?? 0);
        $this->deleteUser($id);
    }

    public function deleteTeamAction(): void
    {
        if (empty($_SESSION['user_id']) || empty($_SESSION['is_admin'])) {
            header('Location: ../');
            exit;
        }

        $id = (int)($_GET['id'] ?? 0);
        $this->deleteTeam($id);
    }

    public function editUserFormAction(): void
    {
        if (empty($_SESSION['user_id']) || empty($_SESSION['is_admin'])) {
            header('Location: ../');
            exit;
        }

        $id = (int)($_GET['id'] ?? 0);

        $data = $this->showEditUser($id);

        $pageTitle = 'Edit user';
        $pageCss   = 'elements_admin.css';

        $old    = $data['old'];
        $errors = $data['errors'];
        $user   = $data['user'];

        require __DIR__ . '/../View/partials/header.php';
        require __DIR__ . '/../View/user_edit.view.php';
        require __DIR__ . '/../View/partials/footer.php';
    }

    public function editUserPostAction(): void
    {
        if (empty($_SESSION['user_id']) || empty($_SESSION['is_admin'])) {
            header('Location: ../');
            exit;
        }

        $id = (int)($_GET['id'] ?? 0);
        $this->updateUser($id);
    }

    public function editTeamFormAction(): void
    {
        if (empty($_SESSION['user_id']) || empty($_SESSION['is_admin'])) {
            header('Location: ../');
            exit;
        }

        $id = (int)($_GET['id'] ?? 0);

        $data = $this->showEditTeam($id);

        $pageTitle = 'Edit team';
        $pageCss   = 'elements_admin.css';

        $old    = $data['old'];
        $errors = $data['errors'];
        $team   = $data['team'];

        require __DIR__ . '/../View/partials/header.php';
        require __DIR__ . '/../View/team_edit.view.php';
        require __DIR__ . '/../View/partials/footer.php';
    }

    public function editTeamPostAction(): void
    {
        if (empty($_SESSION['user_id']) || empty($_SESSION['is_admin'])) {
            header('Location: ../');
            exit;
        }

        $id = (int)($_GET['id'] ?? 0);
        $this->updateTeam($id);
    }

    public function createTeamFormAction(): void
    {
        if (empty($_SESSION['user_id']) || empty($_SESSION['is_admin'])) {
            header('Location: ../');
            exit;
        }

        $data = $this->showCreateTeam();

        $old    = $data['old'];
        $errors = $data['errors'];

        $pageTitle = 'Create team';
        $pageCss   = 'elements_admin.css';

        require __DIR__ . '/../View/partials/header.php';
        require __DIR__ . '/../View/team_create.view.php';
        require __DIR__ . '/../View/partials/footer.php';
    }

    public function createTeamPostAction(): void
    {
        if (empty($_SESSION['user_id']) || empty($_SESSION['is_admin'])) {
            header('Location: ../');
            exit;
        }

        $data = $this->createTeamFromPost();
        // si hi ha errors, createTeamFromPost retorna dades, així que les tornem a pintar
        if (is_array($data)) {
            $old    = $data['old'];
            $errors = $data['errors'];

            $pageTitle = 'Create team';
            $pageCss   = 'elements_admin.css';

            require __DIR__ . '/../View/partials/header.php';
            require __DIR__ . '/../View/team_create.view.php';
            require __DIR__ . '/../View/partials/footer.php';
        }
        // si no hi ha errors, ja fa header() i exit dins del mètode
    }

}