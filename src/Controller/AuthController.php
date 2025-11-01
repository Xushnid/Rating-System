<?php

namespace App\Controller;

use App\Auth;

class AuthController
{
    private $auth;

    public function __construct(Auth $auth)
    {
        $this->auth = $auth;
    }

    // Foydalanuvchini roli bo'yicha to'g'ri manzilga yo'naltiruvchi yordamchi funksiya
    private function redirectUserBasedOnRole($user)
    {
        switch ($user['role']) {
            case 'superadmin':
                header('Location: /admin');
                break;
            case 'facultyadmin':
                header('Location: /faculty-admin');
                break;
            case 'departmentadmin':
            case 'user':
            default:
                header('Location: /user');
                break;
        }
        exit;
    }

    // LOGIN METODINING TO'LIQ YANGI VERSIYASI
    public function login()
    {
        // Agar foydalanuvchi allaqachon tizimga kirgan bo'lsa, uni darhol o'z paneliga yo'naltirish
        if ($this->auth->isLoggedIn()) {
            $user = $this->auth->getCurrentUser();
            $this->redirectUserBasedOnRole($user);
        }

        $error = '';
        $username = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';
            
            // Login muvaffaqiyatli bo'lsa
            if ($this->auth->login($username, $password)) {
                $user = $this->auth->getCurrentUser();
                $this->redirectUserBasedOnRole($user);
            } else {
                $error = 'Login yoki parol xato.';
            }
        }

        require_once __DIR__ . '/../../views/auth/login.php';
    }

    public function logout()
    {
        $this->auth->logout();
        header('Location: /login');
        exit;
    }
}