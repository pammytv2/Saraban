<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * ข้อมูลอ้างอิง: ฝ่ายงาน / หน่วยงานภายนอก / จังหวัด / ตั้งค่าระบบ
 */
final class Reference extends Model
{
    // ---------- ฝ่าย/กลุ่มงาน ----------
    public function departments(bool $onlyActive = true): array
    {
        $sql = 'SELECT * FROM departments';
        if ($onlyActive) {
            $sql .= ' WHERE is_active = 1';
        }
        return $this->all($sql . ' ORDER BY name');
    }

    public function findDepartment(int $id): ?array
    {
        return $this->one('SELECT * FROM departments WHERE id = ?', [$id]);
    }

    public function saveDepartment(?int $id, array $data): int
    {
        if ($id !== null) {
            $this->update('departments', $id, $data);
            return $id;
        }
        return $this->insert('departments', $data);
    }

    public function removeDepartment(int $id): void
    {
        $this->delete('departments', $id);
    }

    // ---------- หน่วยงานภายนอก ----------
    public function organizations(bool $onlyActive = true): array
    {
        $sql = 'SELECT o.*, p.name AS province_name
                  FROM organizations o
                  LEFT JOIN provinces p ON p.id = o.province_id';
        if ($onlyActive) {
            $sql .= ' WHERE o.is_active = 1';
        }
        return $this->all($sql . ' ORDER BY o.name');
    }

    public function findOrganization(int $id): ?array
    {
        return $this->one('SELECT * FROM organizations WHERE id = ?', [$id]);
    }

    public function saveOrganization(?int $id, array $data): int
    {
        if ($id !== null) {
            $this->update('organizations', $id, $data);
            return $id;
        }
        return $this->insert('organizations', $data);
    }

    public function removeOrganization(int $id): void
    {
        $this->delete('organizations', $id);
    }

    // ---------- จังหวัด ----------
    public function provinces(): array
    {
        return $this->all('SELECT id, name FROM provinces ORDER BY name');
    }

    // ---------- ตั้งค่าระบบ ----------
    /** @return array<string,string> */
    public function settings(): array
    {
        $rows = $this->all('SELECT `key`, `value` FROM settings');
        $out = [];
        foreach ($rows as $row) {
            $out[$row['key']] = (string) $row['value'];
        }
        return $out;
    }

    public function setting(string $key, string $default = ''): string
    {
        $value = $this->value('SELECT `value` FROM settings WHERE `key` = ?', [$key]);
        return $value === null ? $default : (string) $value;
    }

    public function saveSettings(array $pairs): void
    {
        foreach ($pairs as $key => $value) {
            $this->run(
                'INSERT INTO settings (`key`, `value`) VALUES (?, ?)
                 ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)',
                [$key, $value]
            );
        }
    }
}
