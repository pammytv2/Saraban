<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * การเกษียณ/มอบหมายหนังสือ
 */
final class Assignment extends Model
{
    public function create(array $data): int
    {
        return $this->insert('document_assignments', $data);
    }

    public function find(int $id): ?array
    {
        return $this->one(
            'SELECT a.*, d.subject, d.direction, d.reg_number, d.reg_year
               FROM document_assignments a
               JOIN documents d ON d.id = a.document_id
              WHERE a.id = ?',
            [$id]
        );
    }

    /** รายการเกษียณของหนังสือฉบับหนึ่ง */
    public function forDocument(int $documentId): array
    {
        return $this->all(
            'SELECT a.*,
                    dept.name AS department_name,
                    u.full_name AS assigned_to_name,
                    b.full_name AS assigned_by_name
               FROM document_assignments a
               LEFT JOIN departments dept ON dept.id = a.department_id
               LEFT JOIN users u ON u.id = a.assigned_to
               LEFT JOIN users b ON b.id = a.assigned_by
              WHERE a.document_id = ?
              ORDER BY a.assigned_at',
            [$documentId]
        );
    }

    /**
     * งานที่มอบหมายถึงผู้ใช้คนนี้ (ตรงตัว หรือถึงฝ่ายที่สังกัด)
     */
    public function myTasks(int $userId, ?int $departmentId, bool $onlyOpen = true, int $limit = 50): array
    {
        $sql =
            'SELECT a.*,
                    d.subject, d.direction, d.reg_number, d.reg_year, d.speed, d.doc_number, d.doc_date,
                    dept.name AS department_name,
                    b.full_name AS assigned_by_name
               FROM document_assignments a
               JOIN documents d ON d.id = a.document_id
               LEFT JOIN departments dept ON dept.id = a.department_id
               LEFT JOIN users b ON b.id = a.assigned_by
              WHERE (a.assigned_to = ?' . ($departmentId !== null ? ' OR (a.assigned_to IS NULL AND a.department_id = ?)' : '') . ')';

        $params = [$userId];
        if ($departmentId !== null) {
            $params[] = $departmentId;
        }

        if ($onlyOpen) {
            $sql .= ' AND a.status IN ("pending","in_progress")';
        }

        $sql .= ' ORDER BY FIELD(d.speed, "ด่วนที่สุด","ด่วนมาก","ด่วน","ปกติ"), a.assigned_at DESC
                  LIMIT ' . (int) $limit;

        return $this->all($sql, $params);
    }

    /** ผู้ใช้คนนี้เกี่ยวข้องกับหนังสือฉบับนี้ไหม (ใช้ตรวจสิทธิ์ของ role staff) */
    public function userCanAccessDocument(int $documentId, int $userId, ?int $departmentId): bool
    {
        $sql = 'SELECT COUNT(*) FROM document_assignments
                 WHERE document_id = ? AND (assigned_to = ?';
        $params = [$documentId, $userId];
        if ($departmentId !== null) {
            $sql .= ' OR department_id = ?';
            $params[] = $departmentId;
        }
        $sql .= ')';

        return (int) $this->value($sql, $params) > 0;
    }

    public function respond(int $id, string $status, string $note): void
    {
        $this->update('document_assignments', $id, [
            'status'        => $status,
            'response_note' => $note !== '' ? $note : null,
            'responded_at'  => in_array($status, ['done', 'closed'], true) ? date('Y-m-d H:i:s') : null,
        ]);
    }
}
