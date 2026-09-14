<?php

use App\Core\Csrf;

$editing = $editing ?? null;
?>
<div class="page-head">
  <h1>จัดการฝ่าย / กลุ่มงาน</h1>
</div>

<div class="card">
  <h2><?= $editing ? 'แก้ไข: ' . e($editing['name']) : 'เพิ่มฝ่าย/กลุ่มงานใหม่' ?></h2>
  <form method="post" action="<?= e(url('/admin/departments')) ?>">
    <?= Csrf::field() ?>
    <?php if ($editing): ?>
      <input type="hidden" name="id" value="<?= (int) $editing['id'] ?>">
    <?php endif; ?>

    <div class="form-grid">
      <div class="field">
        <label for="code">รหัสย่อ</label>
        <input type="text" id="code" name="code" value="<?= e($editing['code'] ?? '') ?>" placeholder="เช่น ADM">
      </div>
      <div class="field span-2">
        <label for="name">ชื่อฝ่าย/กลุ่มงาน <span class="req">*</span></label>
        <input type="text" id="name" name="name" required value="<?= e($editing['name'] ?? '') ?>">
      </div>
      <div class="field">
        <label>สถานะ</label>
        <label style="font-weight:400">
          <input type="checkbox" name="is_active" value="1" style="width:auto"
            <?= (int) ($editing['is_active'] ?? 1) === 1 ? 'checked' : '' ?>> เปิดใช้งาน
        </label>
      </div>
    </div>

    <div class="btn-row" style="margin-top:12px">
      <button type="submit" class="btn btn-primary"><?= $editing ? 'บันทึกการแก้ไข' : 'เพิ่มฝ่าย' ?></button>
      <?php if ($editing): ?>
        <a class="btn btn-ghost" href="<?= e(url('/admin/departments')) ?>">ยกเลิก</a>
      <?php endif; ?>
    </div>
  </form>
</div>

<div class="table-wrap">
  <table class="data">
    <thead>
      <tr>
        <th style="width:120px">รหัส</th>
        <th>ชื่อฝ่าย/กลุ่มงาน</th>
        <th class="center" style="width:110px">สถานะ</th>
        <th class="center nowrap" style="width:160px">จัดการ</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($departments as $dep): ?>
      <tr>
        <td><strong><?= e($dep['code']) ?></strong></td>
        <td><?= e($dep['name']) ?></td>
        <td class="center">
          <span class="badge <?= (int) $dep['is_active'] === 1 ? 'badge-done' : 'badge-closed' ?>">
            <?= (int) $dep['is_active'] === 1 ? 'ใช้งาน' : 'ปิด' ?>
          </span>
        </td>
        <td class="center nowrap">
          <a class="btn btn-sm btn-ghost" href="<?= e(url('/admin/departments?edit=' . $dep['id'])) ?>">แก้ไข</a>
          <form method="post" action="<?= e(url('/admin/departments/' . $dep['id'] . '/delete')) ?>"
                style="display:inline" onsubmit="return confirm('ยืนยันลบฝ่ายนี้?')">
            <?= Csrf::field() ?>
            <button type="submit" class="btn btn-sm btn-danger">ลบ</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
