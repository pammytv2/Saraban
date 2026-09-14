<?php
/**
 * Layout หลักของระบบ — เมนูเป็นแถบซ้ายแบบมีเลขกำกับ (ทิศทาง Swiss / editorial grid)
 * ตัวแปรที่ใช้: $content (จาก Controller::view), $title
 */

use App\Core\Auth;
use App\Core\Csrf;
use App\Models\Reference;

$user = Auth::user();
$settings = $settings ?? (new Reference())->settings();
$orgName = $settings['org_name'] ?? APP_NAME;
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
if (BASE_URL !== '' && strpos($currentPath, BASE_URL) === 0) {
    $currentPath = substr($currentPath, strlen(BASE_URL));
}

/** ตรวจว่าเมนูนี้กำลังถูกเปิดอยู่ไหม */
$isActive = static function (string $path, ?string $direction = null) use ($currentPath): bool {
    if ($path === '/') {
        return $currentPath === '/';
    }
    if (strpos($currentPath, $path) !== 0) {
        return false;
    }
    if ($direction !== null) {
        return ($_GET['direction'] ?? '') === $direction;
    }
    if ($path === '/documents' && isset($_GET['direction'])) {
        return false;
    }
    return true;
};

/** รายการเมนู: [path, direction, label] */
$mainNav = [
    ['/', null, 'หน้าแรก'],
    ['/documents', 'incoming', 'ทะเบียนหนังสือรับ'],
    ['/documents', 'outgoing', 'ทะเบียนหนังสือส่ง'],
    ['/documents', null, 'ค้นหาทั้งหมด'],
];
$adminNav = [
    ['/admin/users', null, 'ผู้ใช้งาน'],
    ['/admin/departments', null, 'ฝ่าย/กลุ่มงาน'],
    ['/admin/organizations', null, 'หน่วยงานภายนอก'],
    ['/admin/settings', null, 'ตั้งค่าหน่วยงาน'],
];

/** ลิงก์เมนูหนึ่งรายการ พร้อมเลขกำกับ */
$navLink = static function (array $item, int $n) use ($isActive): string {
    [$path, $direction, $label] = $item;
    $href = url($path . ($direction !== null ? '?direction=' . $direction : ''));
    $cls = $isActive($path, $direction) ? 'rail-link active' : 'rail-link';
    return '<a class="' . $cls . '" href="' . e($href) . '">'
        . '<span class="rail-num">' . str_pad((string) $n, 2, '0', STR_PAD_LEFT) . '</span>'
        . '<span>' . e($label) . '</span></a>';
};
?><!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e(($title ?? '') !== '' ? $title . ' — ' . APP_SHORT_NAME : APP_NAME) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600&family=IBM+Plex+Sans+Thai:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e(asset('/assets/css/app.css')) ?>">
</head>
<body>

<div class="shell">

  <aside class="rail">
    <div class="rail-top">
      <a class="rail-brand" href="<?= e(url('/')) ?>">
        <span class="rail-dot"></span>
        <span>
          <span class="rail-org"><?= e($orgName) ?></span>
          <span class="rail-sub">งานสารบรรณ</span>
        </span>
      </a>

      <nav class="rail-nav">
        <?php $n = 0; ?>
        <?php foreach ($mainNav as $item): ?>
          <?= $navLink($item, ++$n) ?>
        <?php endforeach; ?>

        <?php if (Auth::isAdmin()): ?>
          <div class="rail-section">ผู้ดูแลระบบ</div>
          <?php foreach ($adminNav as $item): ?>
            <?= $navLink($item, ++$n) ?>
          <?php endforeach; ?>
        <?php endif; ?>
      </nav>
    </div>

    <?php if ($user): ?>
      <div class="rail-user">
        <div class="rail-user-name"><?= e($user['full_name']) ?></div>
        <div class="rail-user-role">
          <?= e(USER_ROLES[$user['role']] ?? $user['role']) ?><?= $user['department'] ? ' · ' . e($user['department']) : '' ?>
        </div>
        <form method="post" action="<?= e(url('/logout')) ?>">
          <?= Csrf::field() ?>
          <button type="submit" class="rail-logout">ออกจากระบบ</button>
        </form>
      </div>
    <?php endif; ?>
  </aside>

  <main class="content">
    <?php if ($msg = flash('success')): ?>
      <div class="alert alert-success"><?= e($msg) ?></div>
    <?php endif; ?>
    <?php if ($msg = flash('error')): ?>
      <div class="alert alert-error"><?= e($msg) ?></div>
    <?php endif; ?>

    <?= $content ?>
  </main>

</div>

</body>
</html>
