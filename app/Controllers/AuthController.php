<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Models\Reference;

final class AuthController extends Controller
{
    public function showLogin(): void
    {
        if (Auth::check()) {
            redirect('/');
        }

        $this->view('auth/login', [
            'orgName' => (new Reference())->setting('org_name', APP_NAME),
            'error'   => flash('error'),
        ], '');
    }

    public function login(): void
    {
        $this->requireCsrf();

        $username = $this->input('username');
        $password = (string) ($_POST['password'] ?? '');

        if ($username === '' || $password === '') {
            flash('error', 'กรุณากรอกชื่อผู้ใช้และรหัสผ่าน');
            redirect('/login');
        }

        if (!Auth::attempt($username, $password)) {
            flash('error', 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง');
            redirect('/login');
        }

        $intended = $_SESSION['_intended'] ?? null;
        unset($_SESSION['_intended']);

        if (is_string($intended) && $intended !== '' && strpos($intended, '/login') === false) {
            header('Location: ' . $intended);
            exit;
        }
        redirect('/');
    }

    public function logout(): void
    {
        $this->requireCsrf();
        Auth::logout();
        redirect('/login');
    }
}
