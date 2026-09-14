<?php

use App\Core\Csrf;

?>
<div class="page-head">
  <h1>ตั้งค่าหน่วยงาน</h1>
</div>

<div class="card">
  <h2>ข้อมูลหน่วยงานเจ้าของระบบ</h2>
  <p class="hint">
    ข้อมูลนี้จะแสดงบนหัวเว็บ หัวใบปะหน้าหนังสือ และหัวทะเบียนคุมที่พิมพ์ออกมา
  </p>

  <form method="post" action="<?= e(url('/admin/settings')) ?>">
    <?= Csrf::field() ?>

    <div class="form-grid">
      <div class="field span-full">
        <label for="org_name">ชื่อหน่วยงาน</label>
        <input type="text" id="org_name" name="org_name" value="<?= e($settings['org_name'] ?? '') ?>">
      </div>
      <div class="field">
        <label for="org_code_prefix">รหัสหนังสือของหน่วยงาน</label>
        <input type="text" id="org_code_prefix" name="org_code_prefix"
               value="<?= e($settings['org_code_prefix'] ?? '') ?>" placeholder="เช่น ศธ 04999">
        <span class="hint">ใช้เป็นค่าตั้งต้นของเลขที่หนังสือส่ง</span>
      </div>
      <div class="field">
        <label for="org_phone">โทรศัพท์</label>
        <input type="text" id="org_phone" name="org_phone" value="<?= e($settings['org_phone'] ?? '') ?>">
      </div>
      <div class="field span-full">
        <label for="org_address">ที่อยู่</label>
        <input type="text" id="org_address" name="org_address" value="<?= e($settings['org_address'] ?? '') ?>">
      </div>
    </div>

    <div class="btn-row" style="margin-top:12px">
      <button type="submit" class="btn btn-primary">บันทึกการตั้งค่า</button>
    </div>
  </form>
</div>
