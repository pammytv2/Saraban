<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * ประวัติการดำเนินการของหนังสือ (audit trail)
 */
final class DocLog extends Model
{
    public function add(int $documentId, ?int $userId, string $action, ?string $detail = null): void
    {
        $this->insert('document_logs', [
            'document_id' => $documentId,
            'user_id'     => $userId,
            'action'      => $action,
            'detail'      => $detail,
        ]);
    }

    public function forDocument(int $documentId): array
    {
        return $this->all(
            'SELECT l.*, u.full_name AS user_name
               FROM document_logs l
               LEFT JOIN users u ON u.id = l.user_id
              WHERE l.document_id = ?
              ORDER BY l.created_at DESC, l.id DESC',
            [$documentId]
        );
    }

    public static function actionLabel(string $action): string
    {
        switch ($action) {
            case 'created':   return 'ลงทะเบียน';
            case 'updated':   return 'แก้ไขข้อมูล';
            case 'assigned':  return 'เกษียณหนังสือ';
            case 'status':    return 'เปลี่ยนสถานะ';
            case 'responded': return 'รายงานผลการดำเนินการ';
            case 'attached':  return 'แนบไฟล์';
            case 'detached':  return 'ลบไฟล์แนบ';
            case 'printed':   return 'พิมพ์เอกสาร';
            default:          return $action;
        }
    }
}
