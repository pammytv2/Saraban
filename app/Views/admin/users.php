<?php

use App\Core\Csrf;

$editing = $editing ?? null;
?>
<div class="page-head">
  <h1>จัดการผู้ใช้งาน</h1>
</div>

<div class="alert alert-info">
  ⚠️ ระบบนี้เก็บรหัสผ่านเป็นข้อความธรรมดาตามที่กำหนดไว้ — ผู้ที่เข้าถึงฐานข้อมูลได้จะเห็นรหัสผ่านของทุกคน
  หากต้องการเข้ารหัส ให้แก้ที่ <code>App\Models\User::verify()</code> และ <code>AdminController::saveUser()</code>
</div>

<div class="card">
  <h2><?= $editing ? 'แก้ไขผู้ใช้: ' . e($editing['username']) : 'เพิ่มผู้ใช้ใหม่' ?></h2>
  <form method="post" action="<?= e(url('/admin/users')) ?>">
    <?= Csrf::field() ?>
    <?php if ($editing): ?>
      <input type="hidden" name="id" value="<?= (int) $editing['id'] ?>">
    <?php endif; ?>

    <div class="form-grid">
      <div class="field">
        <label for="username">ชื่อผู้ใช้ <span class="req">*</span></label>
        <input type="text" id="username" name="username" required value="<?= e($editing['username'] ?? '') ?>">
      </div>
      <div class="field">
        <label for="password">รหัสผ่าน <?= $editing ? '' : '<span class="req">*</span>' ?></label>
        <input type="text" id="password" name="password" value=""
               placeholder="<?= $editing ? 'เว้นว่าง = ไม่เปลี่ยนรหัสผ่านเดิม' : 'กำหนดรหัสผ่าน' ?>">
      </div>
      <div class="field">
        <label for="full_name">ชื่อ-นามสกุล <span class="req">*</span></label>
        <input type="text" id="full_name" name="full_name" required value="<?= e($editing['full_name'] ?? '') ?>">
      </div>
      <div class="field">
        <label for="position">ตำแหน่ง</label>
        <input type="text" id="position" name="position" value="<?= e($editing['position'] ?? '') ?>">
      </div>
      <div class="field">
        <label for="department_id">ฝ่าย/กลุ่มงาน</label>
        <select id="department_id" name="department_id">
          <option value="">— ไม่ระบุ —</option>
          <?php foreach ($departments as $dep): ?>
            <option value="<?= (int) $dep['id'] ?>"
              <?= (string) ($editing['department_id'] ?? '') === (string) $dep['id'] ? 'selected' : '' ?>>
              <?= e($dep['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label for="role">บทบาท</label>
        <select id="role" name="role">
          <?php foreach (USER_ROLES as $key => $label): ?>
            <option value="<?= e($key) ?>" <?= ($editing['role'] ?? 'staff') === $key ? 'selected' : '' ?>>
              <?= e($label) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <span class="hint">ผู้ดูแลระบบ = ทุกสิทธิ์ · เจ้าหน้าที่สารบรรณ = ลงทะเบียน/เกษียณ · เจ้าหน้าที่ฝ่าย = เห็นเฉพาะงานของตน</span>
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
      <button type="submit" class="btn btn-primary"><?= $editing ? 'บันทึกการแก้ไข' : 'เพิ่มผู้ใช้' ?></button>
      <?php if ($editing): ?>
        <a class="btn btn-ghost" href="<?= e(url('/admin/users')) ?>">ยกเลิก</a>
      <?php endif; ?>
    </div>
  </form>
</div>

<div class="table-wrap">
  <table class="data">
    <thead>
      <tr>
        <th>ชื่อผู้ใช้</th>
        <th>ชื่อ-นามสกุล</th>
        <th>ตำแหน่ง</th>
        <th>ฝ่าย</th>
        <th>บทบาท</th>
        <th class="center">สถานะ</th>
        <th class="center nowrap">จัดการ</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($users as $u): ?>
      <tr>
        <td><strong><?= e($u['username']) ?></strong></td>
        <td><?= e($u['full_name']) ?></td>
        <td class="muted"><?= e($u['position'] ?: '-') ?></td>
        <td class="muted"><?= e($u['department_name'] ?: '-') ?></td>
        <td><?= e(USER_ROLES[$u['role']] ?? $u['role']) ?></td>
        <td class="center">
          <span class="badge <?= (int) $u['is_active'] === 1 ? 'badge-done' : 'badge-closed' ?>">
            <?= (int) $u['is_active'] === 1 ? 'ใช้งาน' : 'ปิด' ?>
          </span>
        </td>
        <td class="center nowrap">
          <a class="btn btn-sm btn-ghost" href="<?= e(url('/admin/users?edit=' . $u['id'])) ?>">แก้ไข</a>
          <?php if ((int) $u['id'] !== 1): ?>
            <form method="post" action="<?= e(url('/admin/users/' . $u['id'] . '/delete')) ?>"
                  style="display:inline" onsubmit="return confirm('ยืนยันลบผู้ใช้ <?= e($u['username']) ?>?')">
              <?= Csrf::field() ?>
              <button type="submit" class="btn btn-sm btn-danger">ลบ</button>
            </form>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
