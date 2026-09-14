<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class Attachment extends Model
{
    public function add(int $documentId, array $file, ?int $userId): int
    {
        return $this->insert('document_attachments', [
            'document_id'   => $documentId,
            'original_name' => $file['original_name'],
            'stored_name'   => $file['stored_name'],
            'mime'          => $file['mime'],
            'size'          => $file['size'],
            'uploaded_by'   => $userId,
        ]);
    }

    public function forDocument(int $documentId): array
    {
        return $this->all(
            'SELECT a.*, u.full_name AS uploaded_by_name
               FROM document_attachments a
               LEFT JOIN users u ON u.id = a.uploaded_by
              WHERE a.document_id = ?
              ORDER BY a.uploaded_at',
            [$documentId]
        );
    }

    public function find(int $id): ?array
    {
        return $this->one(
            'SELECT a.*, d.secrecy, d.id AS doc_id
               FROM document_attachments a
               JOIN documents d ON d.id = a.document_id
              WHERE a.id = ?',
            [$id]
        );
    }

    public function remove(int $id): void
    {
        $this->delete('document_attachments', $id);
    }
}
