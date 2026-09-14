<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class User extends Model
{
    /**
     * ตรวจรหัสผ่าน
     *
     * ⚠️ ตอนนี้เทียบแบบ plain text ตามที่ผู้ใช้กำหนดไว้
     * ถ้าต้องการความปลอดภัยให้เปลี่ยนเป็น:
     *     return password_verify($password, $user['password']);
     * และตอนสร้าง/แก้ไขผู้ใช้ใน AdminController::saveUser() ให้เก็บด้วย
     *     password_hash($password, PASSWORD_DEFAULT)
     * แก้แค่ 2 จุดนี้ ระบบส่วนอื่นไม่ต้องแตะ
     */
    public static function verify(array $user, string $password): bool
    {
        return hash_equals((string) $user['password'], $password);
    }

    public function findByUsername(string $username): ?array
    {
        return $this->one(
            'SELECT u.*, d.name AS department_name
               FROM users u
               LEFT JOIN departments d ON d.id = u.department_id
              WHERE u.username = ?
              LIMIT 1',
            [$username]
        );
    }

    public function find(int $id): ?array
    {
        return $this->one(
            'SELECT u.*, d.name AS department_name
               FROM users u
               LEFT JOIN departments d ON d.id = u.department_id
              WHERE u.id = ?',
            [$id]
        );
    }

    public function listAll(): array
    {
        return $this->all(
            'SELECT u.*, d.name AS department_name
               FROM users u
               LEFT JOIN departments d ON d.id = u.department_id
              ORDER BY FIELD(u.role, "admin", "clerk", "staff"), u.username'
        );
    }

    /** ผู้ใช้ที่เลือกเป็นผู้รับมอบหมายได้ */
    public function selectable(): array
    {
        return $this->all(
            'SELECT u.id, u.full_name, u.department_id, d.name AS department_name
               FROM users u
               LEFT JOIN departments d ON d.id = u.department_id
              WHERE u.is_active = 1
              ORDER BY d.name, u.full_name'
        );
    }

    public function save(?int $id, array $data): int
    {
        if ($id !== null) {
            // ไม่กรอกรหัสผ่านใหม่ = ไม่เปลี่ยนรหัสผ่านเดิม
            if (($data['password'] ?? '') === '') {
                unset($data['password']);
            }
            $this->update('users', $id, $data);
            return $id;
        }
        return $this->insert('users', $data);
    }

    public function remove(int $id): void
    {
        $this->delete('users', $id);
    }

    public function usernameExists(string $username, ?int $exceptId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM users WHERE username = ?';
        $params = [$username];
        if ($exceptId !== null) {
            $sql .= ' AND id <> ?';
            $params[] = $exceptId;
        }
        return (int) $this->value($sql, $params) > 0;
    }
}
