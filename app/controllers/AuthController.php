<?php

require_once __DIR__ . '/../models/User.php';

class AuthController
{
    public function showLogin(): void
    {
        if (Auth::check()) {
            redirect(Auth::dashboardRoute(Auth::role()));
        }
        $errors = [];
        $old = [];
        require __DIR__ . '/../views/auth/login.php';
    }

    public function login(): void
    {
        Csrf::requireValid();

        $email = trim($_POST['email'] ?? '');
        $password = (string)($_POST['password'] ?? '');

        $errors = [];
        if ($email === '' || $password === '') {
            $errors['login'] = 'Please enter both email and password.';
        } elseif (!Auth::attempt($email, $password)) {
            $errors['login'] = 'Invalid credentials, or this account is inactive.';
        }

        if (!empty($errors)) {
            $old = $_POST;
            require __DIR__ . '/../views/auth/login.php';
            return;
        }

        redirect(Auth::dashboardRoute(Auth::role()));
    }

    public function logout(): void
    {
        Csrf::requireValid();
        Auth::logout();
        redirect('login');
    }

    public function showRegister(): void
    {
        if (Auth::check()) {
            redirect(Auth::dashboardRoute(Auth::role()));
        }
        $errors = [];
        $old = [];
        require __DIR__ . '/../views/auth/register.php';
    }

    public function register(): void
    {
        Csrf::requireValid();

        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $password = (string)($_POST['password'] ?? '');
        $confirm = (string)($_POST['password_confirm'] ?? '');

        $v = new Validator();
        $v->required($_POST, 'name', 'Name')->maxLength($_POST, 'name', 100, 'Name')
          ->required($_POST, 'email', 'Email')->email($_POST, 'email', 'Email')
          ->required($_POST, 'phone', 'Phone')->phone($_POST, 'phone', 'Phone')
          ->required($_POST, 'password', 'Password')->minLength($_POST, 'password', 6, 'Password');

        $errors = $v->errors();
        if ($password !== '' && $password !== $confirm) {
            $errors['password_confirm'] = 'Passwords do not match.';
        }
        if (empty($errors['email']) && User::emailExists($email)) {
            $errors['email'] = 'This email is already registered.';
        }

        if (!empty($errors)) {
            $old = $_POST;
            require __DIR__ . '/../views/auth/register.php';
            return;
        }

        User::create($name, $email, $password, 'citizen', $phone);
        flash_set('success', 'Account created. Please log in.');
        redirect('login');
    }
}
