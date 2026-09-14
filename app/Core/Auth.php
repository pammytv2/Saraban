<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\User;

/**
 * จัดการการเข้าสู่ระบบด้วย session
 */
final class Auth
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function attempt(string $username, string $password): bool
    {
        $user = (new User())->findByUsername($username);
        if ($user === null || (int) $user['is_active'] !== 1) {
            return false;
        }
        if (!User::verify($user, $password)) {
            return false;
        }

        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id'            => (int) $user['id'],
            'username'      => $user['username'],
            'full_name'     => $user['full_name'],
            'position'      => $user['position'],
            'role'          => $user['role'],
            'department_id' => $user['department_id'] !== null ? (int) $user['department_id'] : null,
            'department'    => $user['department_name'] ?? null,
        ];
        return true;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    public static function check(): bool
    {
        return isset($_SESSION['user']);
    }

    /** ข้อมูลผู้ใช้ที่ล็อกอินอยู่ (null ถ้ายังไม่ล็อกอิน) */
    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function id(): ?int
    {
        return isset($_SESSION['user']) ? (int) $_SESSION['user']['id'] : null;
    }

    public static function role(): ?string
    {
        return $_SESSION['user']['role'] ?? null;
    }

    public static function departmentId(): ?int
    {
        return $_SESSION['user']['department_id'] ?? null;
    }

    public static function isAdmin(): bool
    {
        return self::role() === 'admin';
    }

    /** admin และ clerk คือกลุ่มที่ลงทะเบียน/แก้ไขหนังสือได้ */
    public static function isClerk(): bool
    {
        return in_array(self::role(), ['admin', 'clerk'], true);
    }
}
