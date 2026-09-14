<?php
/** หน้าแสดงข้อผิดพลาด (404 / 403 / 419) — เป็นเอกสารเต็มใบ ไม่ครอบด้วย layout หลัก */
?><!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e((string) $status) ?> — <?= e(APP_NAME) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600&family=IBM+Plex+Sans+Thai:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e(asset('/assets/css/app.css')) ?>">
</head>
<body>

<div class="error-page">
  <div class="error-code"><?= e((string) $status) ?></div>
  <p class="error-message"><?= e($message) ?></p>
  <a class="btn btn-primary" href="<?= e(url('/')) ?>">กลับหน้าแรก</a>
</div>

</body>
</html>
