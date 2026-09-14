<?php
/** Layout สำหรับหน้าพิมพ์ A4 แนวตั้ง */
?><!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title ?? 'พิมพ์เอกสาร') ?></title>
<link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e(asset('/assets/css/print.css')) ?>">
<style>@page { size: A4 portrait; margin: 15mm; }</style>
</head>
<body class="print-body">

<div class="print-toolbar no-print">
  <button type="button" onclick="window.print()">พิมพ์เอกสาร (Ctrl+P)</button>
  <a href="<?= e(url('/documents/' . ($doc['id'] ?? ''))) ?>">กลับหน้ารายละเอียด</a>
  <span style="font-size:13px;opacity:.8">เลือก "บันทึกเป็น PDF" ในหน้าต่างพิมพ์เพื่อเก็บเป็นไฟล์</span>
</div>

<div class="sheet">
  <?= $content ?>
</div>

</body>
</html>
