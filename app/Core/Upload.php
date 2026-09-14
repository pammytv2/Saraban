<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * จัดการไฟล์แนบ — ตรวจชนิด/ขนาด แล้วเก็บด้วยชื่อสุ่มใน storage/uploads
 * (อยู่นอก docroot จึงเข้าถึงตรงจาก URL ไม่ได้ ต้องผ่าน AttachmentController)
 */
final class Upload
{
    /**
     * @param array $file แถวหนึ่งจาก $_FILES
     * @return array{original_name:string, stored_name:string, mime:string, size:int}
     */
    public static function store(array $file): array
    {
        if (!isset($file['error']) || is_array($file['error'])) {
            throw new RuntimeException('ข้อมูลไฟล์ไม่ถูกต้อง');
        }

        switch ($file['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_NO_FILE:
                throw new RuntimeException('ไม่ได้เลือกไฟล์');
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                throw new RuntimeException(
                    'ไฟล์ใหญ่เกินที่ PHP อนุญาต (upload_max_filesize = '
                    . ini_get('upload_max_filesize') . ') — แก้ได้ที่ php.ini'
                );
            case UPLOAD_ERR_NO_TMP_DIR:
                throw new RuntimeException(
                    'เซิร์ฟเวอร์ไม่มีโฟลเดอร์ชั่วคราวสำหรับรับไฟล์ — '
                    . 'ให้ตั้ง upload_tmp_dir ใน php.ini ให้ชี้ไปโฟลเดอร์ที่ผู้ใช้ของเว็บเซิร์ฟเวอร์เขียนได้'
                );
            case UPLOAD_ERR_CANT_WRITE:
                throw new RuntimeException('เซิร์ฟเวอร์เขียนไฟล์ลงดิสก์ไม่ได้ — ตรวจสิทธิ์ของโฟลเดอร์ปลายทาง');
            default:
                throw new RuntimeException('อัปโหลดไฟล์ไม่สำเร็จ (รหัส ' . $file['error'] . ')');
        }

        if ($file['size'] > UPLOAD_MAX_BYTES) {
            throw new RuntimeException('ไฟล์ใหญ่เกิน ' . human_size(UPLOAD_MAX_BYTES));
        }

        $ext = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
        if (!array_key_exists($ext, UPLOAD_ALLOWED)) {
            throw new RuntimeException('อนุญาตเฉพาะไฟล์ ' . implode(', ', array_keys(UPLOAD_ALLOWED)));
        }

        // ตรวจชนิดไฟล์จากเนื้อไฟล์จริง ไม่เชื่อนามสกุลหรือ header ที่ client ส่งมา
        $mime = self::detectMime($file['tmp_name']);
        if ($mime === null || !in_array($mime, UPLOAD_ALLOWED, true)) {
            throw new RuntimeException('ชนิดไฟล์ไม่ถูกต้อง ไม่ตรงกับนามสกุลไฟล์');
        }
        // นามสกุลต้องสอดคล้องกับเนื้อไฟล์ด้วย (กันเปลี่ยนนามสกุลหลอก)
        if (UPLOAD_ALLOWED[$ext] !== $mime) {
            throw new RuntimeException('ชนิดไฟล์ไม่ตรงกับนามสกุล .' . $ext);
        }

        if (!is_dir(UPLOAD_PATH) && !mkdir(UPLOAD_PATH, 0775, true) && !is_dir(UPLOAD_PATH)) {
            throw new RuntimeException('สร้างโฟลเดอร์เก็บไฟล์ไม่สำเร็จ');
        }

        $storedName = date('Ymd') . '_' . bin2hex(random_bytes(12)) . '.' . $ext;
        $target = UPLOAD_PATH . '/' . $storedName;

        if (!move_uploaded_file($file['tmp_name'], $target)) {
            throw new RuntimeException('บันทึกไฟล์ลงเซิร์ฟเวอร์ไม่สำเร็จ');
        }

        return [
            'original_name' => mb_substr((string) $file['name'], 0, 255),
            'stored_name'   => $storedName,
            'mime'          => $mime,
            'size'          => (int) $file['size'],
        ];
    }

    /**
     * ตรวจชนิดไฟล์จากเนื้อไฟล์จริง
     * ใช้ ext-fileinfo ถ้ามี ถ้าไม่มี (บาง host ปิดไว้) จะถอยไปตรวจ magic bytes เอง
     * เพื่อไม่ให้ระบบพึ่งส่วนขยายที่อาจไม่ได้เปิด
     */
    private static function detectMime(string $path): ?string
    {
        if (class_exists('finfo')) {
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($path);
            if (is_string($mime) && $mime !== '') {
                return $mime;
            }
        }

        $handle = fopen($path, 'rb');
        if ($handle === false) {
            return null;
        }
        $head = (string) fread($handle, 16);
        fclose($handle);

        if (strncmp($head, '%PDF-', 5) === 0) {
            return 'application/pdf';
        }
        if (strncmp($head, "\xFF\xD8\xFF", 3) === 0) {
            return 'image/jpeg';
        }
        if (strncmp($head, "\x89PNG\r\n\x1a\n", 8) === 0) {
            return 'image/png';
        }

        return null;
    }

    public static function path(string $storedName): string
    {
        return UPLOAD_PATH . '/' . basename($storedName);
    }
}
