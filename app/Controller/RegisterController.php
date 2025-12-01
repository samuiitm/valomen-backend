<?php

require_once __DIR__ . '/../Model/DAO/UserDAO.php';

class RegisterController
{
    private UserDAO $userDao;

    public function __construct(UserDAO $userDao)
    {
        // guardo el DAO d'usuaris per poder crear i buscar usuaris
        $this->userDao = $userDao;
    }

    public function register(string $username, string $email, string $password, string $confirmPassword): array
    {
        // aquí guardo tots els possibles errors del formulari
        $errors = [
            'username'         => '',
            'email'            => '',
            'password'         => '',
            'confirm_password' => '',
        ];

        // trec espais del principi i del final
        $username = trim($username);
        $email    = trim($email);

        // regex per validar email i contrasenya
        $validEmail    = '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,10}$/';
        $validPassword = '/^(?=.*[A-Za-z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/';

        // comprovo que no hi hagi camps buits
        if ($username === '') $errors['username'] = 'Username is required.';
        if ($email === '')    $errors['email']    = 'Email is required.';
        if ($password === '') $errors['password'] = 'Password is required.';
        if ($confirmPassword === '') $errors['confirm_password'] = 'Please confirm your password.';

        // validació més concreta del username
        if ($errors['username'] === '') {
            $len = strlen($username);

            // longitud mínima i màxima
            if ($len < 4 || $len > 20) {
                $errors['username'] = 'Username must be between 4 and 20 characters.';
            // només deixo lletres en minúscula, números, punt i guió baix
            } elseif (!preg_match('/^[a-z0-9._]+$/', $username)) {
                $errors['username'] = 'Username can only contain lowercase letters, numbers, "." or "_".';
            // no pot començar per punt o guió baix
            } elseif (preg_match('/^[._]/', $username)) {
                $errors['username'] = 'Username cannot start with "." or "_".';
            // ni acabar amb punt o guió baix
            } elseif (preg_match('/[._]$/', $username)) {
                $errors['username'] = 'Username cannot end with "." or "_".';
            // ni tenir símbols duplicats seguits (.. o __ o semblant)
            } elseif (preg_match('/[._]{2}/', $username) || preg_match('/[._][._]/', $username)) {
                $errors['username'] = 'Username cannot contain consecutive symbol characters.';
            }
        }

        // si el format del username és correcte, miro si ja existeix a la bd
        if ($errors['username'] === '') {
            if ($this->userDao->findByUsername($username)) {
                $errors['username'] = 'That username is already taken.';
            }
        }

        // validació del format d'email amb regex
        if ($errors['email'] === '' && !preg_match($validEmail, $email)) {
            $errors['email'] = 'Invalid email address.';
        }

        // si el format d'email és bo, comprovo que no estigui repetit
        if ($errors['email'] === '') {
            if ($this->userDao->findByEmail($email)) {
                $errors['email'] = 'That email is already in use.';
            }
        }

        // validació de la contrasenya (força mínima)
        if ($errors['password'] === '' && !preg_match($validPassword, $password)) {
            // mínim 8 caràcters, 1 lletra, 1 número i 1 símbol
            $errors['password'] = 'Password must have at least 8 chars, 1 letter, 1 number and 1 symbol.';
        }

        // comprovo que les dues contrasenyes coincideixin
        if ($errors['confirm_password'] === '' && $password !== $confirmPassword) {
            $errors['confirm_password'] = 'Passwords do not match.';
        }

        // si hi ha algun error, retorno directament sense crear l'usuari
        foreach ($errors as $e) {
            if ($e !== '') {
                return [
                    'success' => false,
                    'errors'  => $errors
                ];
            }
        }

        // si tot és correcte, crido al DAO per crear l'usuari (hash es fa dins del DAO)
        $userId = $this->userDao->createUser($username, $email, $password, 0);

        // retorno èxit i l'id del nou usuari
        return [
            'success' => true,
            'user_id' => $userId,
            'errors'  => [
                'username'         => '',
                'email'            => '',
                'password'         => '',
                'confirm_password' => '',
            ],
        ];
    }
}