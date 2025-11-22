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
        $this->db      = $db;
        $this->userDao = new UserDAO($db);
        $this->teamDao = new TeamDAO($db);
    }

    public function deleteUser(int $id): void
    {
        if ($id <= 0) {
            header('Location: index.php?page=admin&section=users');
            exit;
        }

        if (!empty($_SESSION['user_id']) && (int)$_SESSION['user_id'] === $id) {
            header('Location: index.php?page=admin&section=users');
            exit;
        }

        $this->userDao->deleteUserById($id);

        header('Location: index.php?page=admin&section=users');
        exit;
    }

    public function deleteTeam(int $id): void
    {
        if ($id <= 0) {
            header('Location: index.php?page=admin&section=teams');
            exit;
        }

        $this->teamDao->deleteTeamById($id);

        header('Location: index.php?page=admin&section=teams');
        exit;
    }

    public function showEditUser(int $id): array
    {
        $user = $this->userDao->getUserById($id);
        if (!$user) {
            header('Location: index.php?page=admin&section=users');
            exit;
        }

        $old = [
            'username' => $user['username'],
            'email'    => $user['email'],
            'points'   => (string)($user['points'] ?? 0),
            'admin'    => (string)($user['admin'] ?? 0),
        ];

        $errors = [
            'username' => '',
            'email'    => '',
            'points'   => '',
            'admin'    => '',
            'global'   => '',
        ];

        return [
            'old'    => $old,
            'errors' => $errors,
            'user'   => $user,
        ];
    }

    public function updateUser(int $id): void
    {
        $user = $this->userDao->getUserById($id);
        if (!$user) {
            header('Location: index.php?page=admin&section=users');
            exit;
        }

        $username = trim($_POST['username'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $points   = trim($_POST['points'] ?? '');
        $isAdmin  = isset($_POST['admin']) ? 1 : 0;

        $errors = [
            'username' => '',
            'email'    => '',
            'points'   => '',
            'admin'    => '',
            'global'   => '',
        ];

        if ($username === '') {
            $errors['username'] = 'Username is required.';
        } else {
            $existing = $this->userDao->findByUsername($username);
            if ($existing && (int)$existing['id'] !== $id) {
                $errors['username'] = 'This username is already taken.';
            }
        }

        if ($email === '') {
            $errors['email'] = 'Email is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email.';
        } else {
            $existing = $this->userDao->findByEmail($email);
            if ($existing && (int)$existing['id'] !== $id) {
                $errors['email'] = 'This email is already in use.';
            }
        }

        if ($points === '') {
            $errors['points'] = 'Points are required.';
        } elseif (!ctype_digit($points) || (int)$points < 0) {
            $errors['points'] = 'Points must be a non-negative integer.';
        }

        $hasErrors = false;
        foreach ($errors as $e) {
            if ($e !== '') {
                $hasErrors = true;
                break;
            }
        }

        $old = [
            'username' => $username,
            'email'    => $email,
            'points'   => $points,
            'admin'    => $isAdmin ? '1' : '0',
        ];

        if ($hasErrors) {
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

        $this->userDao->updateUser(
            $id,
            $username,
            $email,
            (int)$points,
            $isAdmin
        );

        header('Location: index.php?page=admin&section=users');
        exit;
    }

    public function showEditTeam(int $id): array
    {
        $team = $this->teamDao->getTeamById($id);
        if (!$team) {
            header('Location: index.php?page=admin&section=teams');
            exit;
        }

        $old = [
            'name'    => $team['name'],
            'country' => $team['country'],
        ];

        $errors = [
            'name'    => '',
            'country' => '',
            'global'  => '',
        ];

        return [
            'old'    => $old,
            'errors' => $errors,
            'team'   => $team,
        ];
    }

    public function updateTeam(int $id): void
    {
        $team = $this->teamDao->getTeamById($id);
        if (!$team) {
            header('Location: index.php?page=admin&section=teams');
            exit;
        }

        $name    = trim($_POST['name'] ?? '');
        $country = trim($_POST['country'] ?? '');

        $errors = [
            'name'    => '',
            'country' => '',
            'global'  => '',
        ];

        if ($name === '') {
            $errors['name'] = 'Name is required.';
        }

        if ($country === '') {
            $errors['country'] = 'Country code is required.';
        } elseif (strlen($country) > 5) {
            $errors['country'] = 'Country code is too long.';
        }

        $hasErrors = false;
        foreach ($errors as $e) {
            if ($e !== '') {
                $hasErrors = true;
                break;
            }
        }

        $old = [
            'name'    => $name,
            'country' => $country,
        ];

        if ($hasErrors) {
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

        $this->teamDao->updateTeam(
            $id,
            $name,
            $country
        );

        header('Location: index.php?page=admin&section=teams');
        exit;
    }
}