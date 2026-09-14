<?php
/**
 * ค่าคงที่และการตั้งค่าหลักของระบบสารบรรณ
 * ไฟล์นี้ถูก require เป็นอันดับแรกจาก public/index.php
 */

declare(strict_types=1);

// ---------- Path ----------
define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
define('STORAGE_PATH', BASE_PATH . '/storage');
define('UPLOAD_PATH', STORAGE_PATH . '/uploads');
define('LOG_PATH', STORAGE_PATH . '/logs');

// ---------- แอป ----------
define('APP_NAME', 'ระบบสารบรรณอิเล็กทรอนิกส์');
define('APP_SHORT_NAME', 'สารบรรณ');

/**
 * หาคำนำหน้า URL ของระบบจากสภาพแวดล้อมจริง
 *
 * - ถ้า URL ที่ขอเข้ามามี index.php อยู่แล้ว → ใช้ path ของ index.php เป็นฐาน
 * - ถ้าเป็น IIS (ปกติไม่มีโมดูล URL Rewrite) → บังคับใช้รูปแบบ index.php/... เพื่อให้ลิงก์ในระบบใช้ได้
 * - กรณีอื่น (php -S, Apache ที่มี rewrite) → ใช้โฟลเดอร์ที่ index.php อยู่
 */
function detect_base_url(): string
{
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    if ($script === '') {
        return '';
    }

    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    if (strpos($uri, $script) === 0) {
        return $script;
    }

    if (stripos($_SERVER['SERVER_SOFTWARE'] ?? '', 'Microsoft-IIS') !== false) {
        return $script;
    }

    $dir = str_replace('\\', '/', dirname($script));
    return $dir === '/' ? '' : $dir;
}

/**
 * BASE_URL: คำนำหน้าของทุกลิงก์ในระบบ — ตรวจหาเองอัตโนมัติให้เข้ากับที่ติดตั้ง
 *
 *  - php -S / Apache ที่มี mod_rewrite : ได้ ''  หรือชื่อโฟลเดอร์ย่อย  → /documents
 *  - IIS (ไม่มีโมดูล URL Rewrite)      : ได้ '.../public/index.php'   → /public/index.php/documents
 *
 * ถ้าต้องการกำหนดเอง ตั้ง environment variable ชื่อ APP_BASE_URL
 */
define('BASE_URL', rtrim(getenv('APP_BASE_URL') ?: detect_base_url(), '/'));

/**
 * ASSET_URL: คำนำหน้าของไฟล์ static (css/js/รูป)
 * ต้องไม่ผ่าน index.php เพราะเป็นไฟล์จริงที่เว็บเซิร์ฟเวอร์ส่งเองได้
 */
define('ASSET_URL', rtrim((string) preg_replace('#/index\.php$#', '', BASE_URL), '/'));

// ---------- ตัวเลือกของหนังสือ (ตามระเบียบงานสารบรรณ) ----------
const DOC_DIRECTIONS = [
    'incoming' => 'หนังสือรับ',
    'outgoing' => 'หนังสือส่ง',
];

const DOC_TYPES = [
    'ภายนอก',
    'ภายใน',
    'ประทับตรา',
    'สั่งการ',
    'ประชาสัมพันธ์',
    'เจ้าหน้าที่ทำขึ้น',
];

const DOC_SPEEDS = ['ปกติ', 'ด่วน', 'ด่วนมาก', 'ด่วนที่สุด'];

const DOC_SECRECY = ['ปกติ', 'ลับ', 'ลับมาก', 'ลับที่สุด'];

const DOC_STATUSES = [
    'pending'     => 'รอดำเนินการ',
    'in_progress' => 'กำลังดำเนินการ',
    'done'        => 'ดำเนินการแล้ว',
    'closed'      => 'ยุติเรื่อง',
];

/**
 * มุมมองสำเร็จรูปของหน้าทะเบียน — คำถามที่เจ้าหน้าที่ถามทุกวัน กดครั้งเดียวได้คำตอบ
 * แทนการตั้งค่าตัวกรองเองทีละช่อง (เงื่อนไขจริงอยู่ใน Document::buildFilters)
 */
const DOC_VIEWS = [
    'mine'   => 'ฝ่ายฉันค้างอยู่',
    'urgent' => 'ด่วนและยังไม่เสร็จ',
    'month'  => 'ลงทะเบียนเดือนนี้',
    'nodept' => 'ยังไม่ได้ส่งฝ่าย',
];

const USER_ROLES = [
    'admin' => 'ผู้ดูแลระบบ',
    'clerk' => 'เจ้าหน้าที่สารบรรณ',
    'staff' => 'เจ้าหน้าที่/หัวหน้าฝ่าย',
];

// ---------- ไฟล์แนบ ----------
const UPLOAD_MAX_BYTES = 10 * 1024 * 1024; // 10 MB
const UPLOAD_ALLOWED = [
    'pdf'  => 'application/pdf',
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png'  => 'image/png',
];

// ---------- เดือนภาษาไทย ----------
const THAI_MONTHS_FULL = [
    1 => 'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน',
    'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม',
];

const THAI_MONTHS_SHORT = [
    1 => 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.',
    'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.',
];

// ---------- Autoloader (PSR-4 อย่างง่าย: App\ -> app/) ----------
spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $file = APP_PATH . '/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});

require_once APP_PATH . '/Core/helpers.php';
