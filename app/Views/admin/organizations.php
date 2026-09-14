<?php

use App\Core\Csrf;

$editing = $editing ?? null;
?>
<div class="page-head">
  <h1>จัดการหน่วยงานภายนอก</h1>
</div>

<div class="card">
  <h2><?= $editing ? 'แก้ไข: ' . e($editing['name']) : 'เพิ่มหน่วยงานใหม่' ?></h2>
  <form method="post" action="<?= e(url('/admin/organizations')) ?>">
    <?= Csrf::field() ?>
    <?php if ($editing): ?>
      <input type="hidden" name="id" value="<?= (int) $editing['id'] ?>">
    <?php endif; ?>

    <div class="form-grid">
      <div class="field span-2">
        <label for="name">ชื่อหน่วยงาน <span class="req">*</span></label>
        <input type="text" id="name" name="name" required value="<?= e($editing['name'] ?? '') ?>">
      </div>
      <div class="field">
        <label for="province_id">จังหวัด</label>
        <select id="province_id" name="province_id">
          <option value="">— ไม่ระบุ —</option>
          <?php foreach ($provinces as $p): ?>
            <option value="<?= (int) $p['id'] ?>"
              <?= (string) ($editing['province_id'] ?? '') === (string) $p['id'] ? 'selected' : '' ?>>
              <?= e($p['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field span-2">
        <label for="address">ที่อยู่</label>
        <input type="text" id="address" name="address" value="<?= e($editing['address'] ?? '') ?>">
      </div>
      <div class="field">
        <label for="phone">โทรศัพท์</label>
        <input type="text" id="phone" name="phone" value="<?= e($editing['phone'] ?? '') ?>">
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
      <button type="submit" class="btn btn-primary"><?= $editing ? 'บันทึกการแก้ไข' : 'เพิ่มหน่วยงาน' ?></button>
      <?php if ($editing): ?>
        <a class="btn btn-ghost" href="<?= e(url('/admin/organizations')) ?>">ยกเลิก</a>
      <?php endif; ?>
    </div>
  </form>
</div>

<div class="table-wrap">
  <table class="data">
    <thead>
      <tr>
        <th>ชื่อหน่วยงาน</th>
        <th style="width:130px">จังหวัด</th>
        <th>ที่อยู่</th>
        <th style="width:120px">โทรศัพท์</th>
        <th class="center" style="width:100px">สถานะ</th>
        <th class="center nowrap" style="width:160px">จัดการ</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($organizations as $org): ?>
      <tr>
        <td><strong><?= e($org['name']) ?></strong></td>
        <td class="muted"><?= e($org['province_name'] ?? '-') ?></td>
        <td class="muted"><?= e($org['address'] ?: '-') ?></td>
        <td class="muted nowrap"><?= e($org['phone'] ?: '-') ?></td>
        <td class="center">
          <span class="badge <?= (int) $org['is_active'] === 1 ? 'badge-done' : 'badge-closed' ?>">
            <?= (int) $org['is_active'] === 1 ? 'ใช้งาน' : 'ปิด' ?>
          </span>
        </td>
        <td class="center nowrap">
          <a class="btn btn-sm btn-ghost" href="<?= e(url('/admin/organizations?edit=' . $org['id'])) ?>">แก้ไข</a>
          <form method="post" action="<?= e(url('/admin/organizations/' . $org['id'] . '/delete')) ?>"
                style="display:inline" onsubmit="return confirm('ยืนยันลบหน่วยงานนี้?')">
            <?= Csrf::field() ?>
            <button type="submit" class="btn btn-sm btn-danger">ลบ</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
