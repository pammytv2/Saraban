<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Upload;
use App\Models\Assignment;
use App\Models\Attachment;
use App\Models\DocLog;

final class AttachmentController extends Controller
{
    public function upload(string $id): void
    {
        $this->requireClerk();
        $this->requireCsrf();
        $docId = (int) $id;

        try {
            $stored = Upload::store($_FILES['file'] ?? []);
        } catch (\Throwable $e) {
            flash('error', 'แนบไฟล์ไม่สำเร็จ: ' . $e->getMessage());
            redirect('/documents/' . $docId);
            return;
        }

        (new Attachment())->add($docId, $stored, Auth::id());
        (new DocLog())->add($docId, Auth::id(), 'attached', 'แนบไฟล์ ' . $stored['original_name']);

        flash('success', 'แนบไฟล์เรียบร้อยแล้ว');
        redirect('/documents/' . $docId);
    }

    /**
     * ส่งไฟล์ให้ผู้ใช้ — ไฟล์อยู่นอก docroot จึงต้องผ่านเมธอดนี้เท่านั้น
     * ตรวจสิทธิ์ก่อนเสมอ (staff ต้องเป็นผู้เกี่ยวข้องกับหนังสือฉบับนั้น)
     */
    public function download(string $id): void
    {
        $this->requireLogin();

        $attachments = new Attachment();
        $file = $attachments->find((int) $id);
        if ($file === null) {
            $this->abort(404, 'ไม่พบไฟล์แนบ');
        }

        if (!Auth::isClerk()) {
            $ok = (new Assignment())->userCanAccessDocument(
                (int) $file['document_id'],
                (int) Auth::id(),
                Auth::departmentId()
            );
            if (!$ok) {
                $this->abort(403, 'คุณไม่มีสิทธิ์เปิดไฟล์แนบของหนังสือฉบับนี้');
            }
        }

        $path = Upload::path($file['stored_name']);
        if (!is_file($path)) {
            $this->abort(404, 'ไฟล์หายไปจากเซิร์ฟเวอร์');
        }

        header('Content-Type: ' . $file['mime']);
        header('Content-Length: ' . (string) filesize($path));
        header('Content-Disposition: inline; filename="' . rawurlencode($file['original_name']) . '"');
        header('X-Content-Type-Options: nosniff');
        readfile($path);
        exit;
    }

    public function destroy(string $id): void
    {
        $this->requireClerk();
        $this->requireCsrf();

        $attachments = new Attachment();
        $file = $attachments->find((int) $id);
        if ($file === null) {
            $this->abort(404, 'ไม่พบไฟล์แนบ');
        }

        $docId = (int) $file['document_id'];
        $path = Upload::path($file['stored_name']);
        if (is_file($path)) {
            @unlink($path);
        }
        $attachments->remove((int) $id);
        (new DocLog())->add($docId, Auth::id(), 'detached', 'ลบไฟล์แนบ ' . $file['original_name']);

        flash('success', 'ลบไฟล์แนบเรียบร้อยแล้ว');
        redirect('/documents/' . $docId);
    }
}
