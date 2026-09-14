<?php
/**
 * ฟังก์ชันช่วยเหลือทั่วไป ใช้ได้ทั้งใน Controller และ View
 */

declare(strict_types=1);

/** escape ข้อความก่อนแสดงใน HTML */
function e($value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** สร้าง URL โดยเติม BASE_URL ให้อัตโนมัติ */
function url(string $path = '/'): string
{
    if ($path === '' || $path[0] !== '/') {
        $path = '/' . $path;
    }
    return BASE_URL . $path;
}

/** URL ของไฟล์ static (css/js/รูป) — ไม่ผ่าน index.php */
function asset(string $path): string
{
    if ($path === '' || $path[0] !== '/') {
        $path = '/' . $path;
    }
    return ASSET_URL . $path;
}

/** ส่ง header redirect แล้วจบการทำงาน */
function redirect(string $path): void
{
    header('Location: ' . url($path));
    exit;
}

/** ปี พ.ศ. จากวันที่ (รับ Y-m-d หรือ timestamp string) */
function be_year(?string $date = null): int
{
    $ts = $date ? strtotime($date) : time();
    return (int) date('Y', $ts) + 543;
}

/** วันที่ไทยแบบเต็ม เช่น 14 กันยายน 2569 */
function thai_date(?string $date, string $fallback = '-'): string
{
    if (empty($date)) {
        return $fallback;
    }
    $ts = strtotime($date);
    if ($ts === false) {
        return $fallback;
    }
    return (int) date('j', $ts) . ' ' . THAI_MONTHS_FULL[(int) date('n', $ts)] . ' ' . (((int) date('Y', $ts)) + 543);
}

/** วันที่ไทยแบบสั้น เช่น 14 ก.ย. 69 */
function thai_date_short(?string $date, string $fallback = '-'): string
{
    if (empty($date)) {
        return $fallback;
    }
    $ts = strtotime($date);
    if ($ts === false) {
        return $fallback;
    }
    return (int) date('j', $ts) . ' ' . THAI_MONTHS_SHORT[(int) date('n', $ts)] . ' ' . substr((string) (((int) date('Y', $ts)) + 543), 2);
}

/** วันที่+เวลาไทย เช่น 14 ก.ย. 69 09:35 น. */
function thai_datetime(?string $datetime, string $fallback = '-'): string
{
    if (empty($datetime)) {
        return $fallback;
    }
    $ts = strtotime($datetime);
    if ($ts === false) {
        return $fallback;
    }
    return thai_date_short($datetime) . ' ' . date('H:i', $ts) . ' น.';
}

/**
 * แปลงวันที่ไทยที่ผู้ใช้พิมพ์ (วว/ดด/ปปปป พ.ศ.) เป็น Y-m-d สำหรับเก็บลงฐานข้อมูล
 * รับ พ.ศ. เป็นหลัก แต่ถ้าใส่ ค.ศ. มาก็ยังอ่านออก คืน null เมื่อรูปแบบไม่ถูกต้อง
 */
function parse_thai_date(?string $value): ?string
{
    $value = trim((string) $value);
    if ($value === '') {
        return null;
    }
    // เผื่อกรณีเบราว์เซอร์เก่าส่ง Y-m-d มาตรงๆ
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1) {
        return $value;
    }
    if (preg_match('#^(\d{1,2})/(\d{1,2})/(\d{4})$#', $value, $m) !== 1) {
        return null;
    }
    [$day, $month, $year] = [(int) $m[1], (int) $m[2], (int) $m[3]];
    if ($year > 2400) {
        $year -= 543;
    }
    if (!checkdate($month, $day, $year)) {
        return null;
    }
    return sprintf('%04d-%02d-%02d', $year, $month, $day);
}

/** เหมือน parse_thai_date แต่มีเวลาต่อท้าย เช่น 14/09/2569 10:32 */
function parse_thai_datetime(?string $value): ?string
{
    $value = trim((string) $value);
    if ($value === '') {
        return null;
    }
    $time = '00:00:00';
    if (preg_match('/\s+(\d{1,2}):(\d{2})(?::(\d{2}))?$/', $value, $m) === 1) {
        $time = sprintf('%02d:%02d:%02d', (int) $m[1], (int) $m[2], (int) ($m[3] ?? 0));
        $value = trim(substr($value, 0, -strlen($m[0])));
    }
    $date = parse_thai_date($value);
    return $date === null ? null : $date . ' ' . $time;
}

/** Y-m-d ในฐานข้อมูล → วว/ดด/ปปปป (พ.ศ.) สำหรับเติมกลับในช่องกรอก */
function thai_date_input(?string $date): string
{
    if (empty($date)) {
        return '';
    }
    $ts = strtotime($date);
    return $ts === false ? '' : date('d/m/', $ts) . ((int) date('Y', $ts) + 543);
}

/** Y-m-d H:i:s → วว/ดด/ปปปป ชช:นน (พ.ศ.) */
function thai_datetime_input(?string $datetime): string
{
    if (empty($datetime)) {
        return '';
    }
    $ts = strtotime($datetime);
    return $ts === false ? '' : thai_date_input($datetime) . ' ' . date('H:i', $ts);
}

/** เลขทะเบียนรูปแบบมาตรฐาน เช่น 0123/2569 */
function reg_label(?int $number, ?int $year): string
{
    if (!$number || !$year) {
        return '-';
    }
    return str_pad((string) $number, 4, '0', STR_PAD_LEFT) . '/' . $year;
}

/** ขนาดไฟล์อ่านง่าย */
function human_size(int $bytes): string
{
    if ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    }
    if ($bytes >= 1024) {
        return number_format($bytes / 1024, 1) . ' KB';
    }
    return $bytes . ' B';
}

/** คลาส CSS ของป้ายสถานะ */
function status_class(string $status): string
{
    switch ($status) {
        case 'pending':     return 'badge badge-pending';
        case 'in_progress': return 'badge badge-progress';
        case 'done':        return 'badge badge-done';
        case 'closed':      return 'badge badge-closed';
        default:            return 'badge';
    }
}

/** คลาส CSS ของชั้นความเร็ว (ด่วนขึ้นสีแรงขึ้น) */
function speed_class(string $speed): string
{
    switch ($speed) {
        case 'ด่วน':        return 'badge badge-speed-1';
        case 'ด่วนมาก':     return 'badge badge-speed-2';
        case 'ด่วนที่สุด':  return 'badge badge-speed-3';
        default:            return 'badge badge-normal';
    }
}

/** ค่า query string เดิม ใช้คงค่าในฟอร์มค้นหา */
function q(string $key, string $default = ''): string
{
    return isset($_GET[$key]) ? (string) $_GET[$key] : $default;
}

/** เก็บข้อความแจ้งเตือนข้ามหน้า */
function flash(string $type, ?string $message = null)
{
    if ($message !== null) {
        $_SESSION['_flash'][$type] = $message;
        return null;
    }
    $value = $_SESSION['_flash'][$type] ?? null;
    unset($_SESSION['_flash'][$type]);
    return $value;
}
