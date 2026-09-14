<?php

use App\Core\Csrf;

?><!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>เข้าสู่ระบบ — <?= e(APP_NAME) ?></title>
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600&family=IBM+Plex+Sans+Thai:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e(asset('/assets/css/app.css')) ?>">
</head>
<body>

<div class="login-page">
  <form class="login-card" method="post" action="<?= e(url('/login')) ?>">
    <?= Csrf::field() ?>

    <h1><?= e($orgName) ?></h1>
    <?php if ($orgName !== APP_NAME): ?>
      <p class="sub"><?= e(APP_NAME) ?></p>
    <?php else: ?>
      <p class="sub">เข้าสู่ระบบเพื่อจัดการทะเบียนหนังสือรับ-ส่ง</p>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
      <div class="alert alert-error"><?= e($error) ?></div>
    <?php endif; ?>

    <div class="field">
      <label for="username">ชื่อผู้ใช้</label>
      <input type="text" id="username" name="username" autocomplete="username" autofocus required>
    </div>

    <div class="field">
      <label for="password">รหัสผ่าน</label>
      <input type="password" id="password" name="password" autocomplete="current-password" required>
    </div>

    <button type="submit" class="btn btn-primary">เข้าสู่ระบบ</button>

    <div class="demo-note">
      <strong>บัญชีทดสอบ (ข้อมูลตัวอย่าง)</strong><br>
      ผู้ดูแลระบบ: <code>admin</code> / <code>admin123</code><br>
      เจ้าหน้าที่สารบรรณ: <code>clerk</code> / <code>clerk123</code><br>
      เจ้าหน้าที่ฝ่าย: <code>staff1</code> / <code>staff123</code><br>
      <span style="color:#b45309">โปรดเปลี่ยนรหัสผ่านทั้งหมดก่อนใช้งานจริง</span>
    </div>
  </form>
</div>

</body>
</html>
