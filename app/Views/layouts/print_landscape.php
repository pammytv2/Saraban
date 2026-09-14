<?php
/** Layout สำหรับหน้าพิมพ์ A4 แนวนอน (ทะเบียนคุม) */
?><!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title ?? 'พิมพ์ทะเบียน') ?></title>
<link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e(asset('/assets/css/print.css')) ?>">
<style>@page { size: A4 landscape; margin: 10mm; }</style>
</head>
<body class="print-body">

<div class="print-toolbar no-print">
  <button type="button" onclick="window.print()">พิมพ์ทะเบียน (Ctrl+P)</button>
  <a href="<?= e(url('/documents?direction=' . ($direction ?? 'incoming'))) ?>">กลับหน้าทะเบียน</a>
  <span style="font-size:13px;opacity:.8">ตั้งค่าหน้ากระดาษเป็นแนวนอน (Landscape) ก่อนพิมพ์</span>
</div>

<div class="sheet landscape">
  <?= $content ?>
</div>

</body>
</html>
