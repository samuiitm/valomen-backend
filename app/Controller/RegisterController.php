<?php

require_once __DIR__ . '/../Model/DAO/UserDAO.php';

class RegisterController
{
    private UserDAO $userDao;

    public function __construct(UserDAO $userDao)
    {
        $this->userDao = $userDao;
    }

    public function register(string $username, string $email, string $password, string $confirmPassword): array
    {
        $errors = [
            'username'         => '',
            'email'            => '',
            'password'         => '',
            'confirm_password' => '',
        ];

        $username = trim($username);
        $email    = trim($email);

        $validEmail    = '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,10}$/';
        $validPassword = '/^(?=.*[A-Za-z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/';

        if ($username === '') $errors['username'] = 'Username is required.';
        if ($email === '')    $errors['email']    = 'Email is required.';
        if ($password === '') $errors['password'] = 'Password is required.';
        if ($confirmPassword === '') $errors['confirm_password'] = 'Please confirm your password.';

        if ($errors['username'] === '') {
            $len = strlen($username);

            if ($len < 4 || $len > 20) {
                $errors['username'] = 'Username must be between 4 and 20 characters.';
            } elseif (!preg_match('/^[a-z0-9._]+$/', $username)) {
                $errors['username'] = 'Username can only contain lowercase letters, numbers, "." or "_".';
            } elseif (preg_match('/^[._]/', $username)) {
                $errors['username'] = 'Username cannot start with "." or "_".';
            } elseif (preg_match('/[._]$/', $username)) {
                $errors['username'] = 'Username cannot end with "." or "_".';
            } elseif (preg_match('/[._]{2}/', $username) || preg_match('/[._][._]/', $username)) {
                $errors['username'] = 'Username cannot contain consecutive symbol characters.';
            }
        }


        if ($errors['username'] === '') {
            if ($this->userDao->findByUsername($username)) {
                $errors['username'] = 'That username is already taken.';
            }
        }

        if ($errors['email'] === '' && !preg_match($validEmail, $email)) {
            $errors['email'] = 'Invalid email address.';
        }

        if ($errors['email'] === '') {
            if ($this->userDao->findByEmail($email)) {
                $errors['email'] = 'That email is already in use.';
            }
        }

        if ($errors['password'] === '' && !preg_match($validPassword, $password)) {
            $errors['password'] = 'Password must have at least 8 chars, 1 letter, 1 number and 1 symbol.';
        }

        if ($errors['confirm_password'] === '' && $password !== $confirmPassword) {
            $errors['confirm_password'] = 'Passwords do not match.';
        }

        foreach ($errors as $e) {
            if ($e !== '') {
                return [
                    'success' => false,
                    'errors'  => $errors
                ];
            }
        }

        $userId = $this->userDao->createUser($username, $email, $password, 0);

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