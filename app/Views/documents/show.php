<?php

use App\Core\Auth;
use App\Core\Csrf;
use App\Models\DocLog;
use App\Models\Document;

$isIncoming = $doc['direction'] === 'incoming';
?>
<div class="page-head">
  <h1>
    <span class="badge <?= $isIncoming ? 'badge-dir-in' : 'badge-dir-out' ?>"><?= e(DOC_DIRECTIONS[$doc['direction']]) ?></span>
    ทะเบียนเลขที่ <?= e(reg_label((int) $doc['reg_number'], (int) $doc['reg_year'])) ?>
  </h1>
  <div class="btn-row">
    <a class="btn btn-ghost" target="_blank" href="<?= e(url('/documents/' . $doc['id'] . '/print/cover')) ?>">พิมพ์ใบปะหน้า</a>
    <?php if (Auth::isClerk()): ?>
      <a class="btn btn-blue" href="<?= e(url('/documents/' . $doc['id'] . '/edit')) ?>">แก้ไขข้อมูล</a>
    <?php endif; ?>
    <a class="btn btn-ghost" href="<?= e(url('/documents?direction=' . $doc['direction'])) ?>">กลับหน้าทะเบียน</a>
  </div>
</div>

<div class="card">
  <h2><?= e($doc['subject']) ?></h2>

  <div class="detail-grid">
    <div>
      <div class="detail-row"><span class="k">ที่ (เลขที่หนังสือ)</span><span class="v"><?= e($doc['doc_number'] ?: '-') ?></span></div>
      <div class="detail-row"><span class="k">ลงวันที่</span><span class="v"><?= e(thai_date($doc['doc_date'])) ?></span></div>
      <div class="detail-row"><span class="k">จาก</span><span class="v"><?= e(Document::partyName($doc, 'from')) ?></span></div>
      <div class="detail-row"><span class="k">ถึง</span><span class="v"><?= e(Document::partyName($doc, 'to')) ?></span></div>
      <div class="detail-row"><span class="k">ประเภทหนังสือ</span><span class="v"><?= e($doc['doc_type']) ?></span></div>
    </div>
    <div>
      <div class="detail-row">
        <span class="k">ชั้นความเร็ว</span>
        <span class="v"><span class="<?= e(speed_class($doc['speed'])) ?>"><?= e($doc['speed']) ?></span></span>
      </div>
      <div class="detail-row">
        <span class="k">ชั้นความลับ</span>
        <span class="v">
          <span class="badge <?= $doc['secrecy'] === 'ปกติ' ? 'badge-normal' : 'badge-secret' ?>"><?= e($doc['secrecy']) ?></span>
        </span>
      </div>
      <div class="detail-row">
        <span class="k">สถานะ</span>
        <span class="v"><span class="<?= e(status_class($doc['status'])) ?>"><?= e(DOC_STATUSES[$doc['status']]) ?></span></span>
      </div>
      <div class="detail-row"><span class="k">ฝ่ายเจ้าของเรื่อง</span><span class="v"><?= e($doc['owner_department_name'] ?: '-') ?></span></div>
      <?php if (!$isIncoming): ?>
        <div class="detail-row"><span class="k">ผู้ลงนาม</span><span class="v"><?= e($doc['signer'] ?: '-') ?></span></div>
      <?php endif; ?>
      <div class="detail-row">
        <span class="k">ลงทะเบียนเมื่อ</span>
        <span class="v"><?= e(thai_datetime($doc['reg_datetime'])) ?> <span class="muted">โดย <?= e($doc['created_by_name'] ?: '-') ?></span></span>
      </div>
    </div>
  </div>

  <?php if (!empty($doc['detail'])): ?>
    <div style="margin-top:14px">
      <label>รายละเอียด / การปฏิบัติ</label>
      <div class="instruction"><?= nl2br(e($doc['detail'])) ?></div>
    </div>
  <?php endif; ?>

  <?php if (Auth::isClerk()): ?>
    <div style="margin-top:16px;padding-top:14px;border-top:1px solid var(--line)">
      <form method="post" action="<?= e(url('/documents/' . $doc['id'] . '/status')) ?>" class="btn-row" style="align-items:center">
        <?= Csrf::field() ?>
        <label style="margin-right:4px">เปลี่ยนสถานะหนังสือ</label>
        <select name="status" style="width:auto">
          <?php foreach (DOC_STATUSES as $key => $label): ?>
            <option value="<?= e($key) ?>" <?= $doc['status'] === $key ? 'selected' : '' ?>><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-sm btn-primary">บันทึกสถานะ</button>
      </form>
    </div>
  <?php endif; ?>
</div>

<!-- ---------- การเกษียณหนังสือ ---------- -->
<div class="card">
  <h2>การเกษียณ / มอบหมาย (<?= count($assignments) ?> รายการ)</h2>

  <?php if ($assignments === []): ?>
    <div class="empty">ยังไม่มีการเกษียณหนังสือฉบับนี้</div>
  <?php else: ?>
    <?php foreach ($assignments as $a): ?>
      <div class="assign-box">
        <div class="head">
          <div>
            <strong><?= e($a['department_name'] ?: 'ไม่ระบุฝ่าย') ?></strong>
            <?php if (!empty($a['assigned_to_name'])): ?>
              <span class="muted">· ผู้รับผิดชอบ: <?= e($a['assigned_to_name']) ?></span>
            <?php endif; ?>
          </div>
          <span class="<?= e(status_class($a['status'])) ?>"><?= e(DOC_STATUSES[$a['status']]) ?></span>
        </div>

        <div class="muted" style="margin-bottom:7px">
          เกษียณโดย <?= e($a['assigned_by_name'] ?: '-') ?> เมื่อ <?= e(thai_datetime($a['assigned_at'])) ?>
        </div>

        <?php if (!empty($a['instruction'])): ?>
          <div class="instruction"><strong>คำสั่งการ:</strong> <?= nl2br(e($a['instruction'])) ?></div>
        <?php endif; ?>

        <?php if (!empty($a['response_note'])): ?>
          <div class="instruction instruction-done">
            <strong>ผลการดำเนินการ:</strong> <?= nl2br(e($a['response_note'])) ?>
            <div class="muted">เมื่อ <?= e(thai_datetime($a['responded_at'])) ?></div>
          </div>
        <?php endif; ?>

        <?php
        $canRespond = Auth::isClerk()
            || (int) ($a['assigned_to'] ?? 0) === (int) Auth::id()
            || ($a['department_id'] !== null && (int) $a['department_id'] === (int) Auth::departmentId());
        ?>
        <?php if ($canRespond): ?>
          <form method="post" action="<?= e(url('/assignments/' . $a['id'] . '/respond')) ?>"
                style="margin-top:10px;display:flex;gap:8px;flex-wrap:wrap;align-items:center">
            <?= Csrf::field() ?>
            <select name="status" style="width:auto">
              <?php foreach (DOC_STATUSES as $key => $label): ?>
                <option value="<?= e($key) ?>" <?= $a['status'] === $key ? 'selected' : '' ?>><?= e($label) ?></option>
              <?php endforeach; ?>
            </select>
            <input type="text" name="response_note" placeholder="ผลการดำเนินการ" style="flex:1;min-width:200px"
                   value="<?= e($a['response_note'] ?? '') ?>">
            <button type="submit" class="btn btn-sm btn-primary">บันทึกผล</button>
          </form>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>

  <?php if (Auth::isClerk()): ?>
    <div style="margin-top:14px;padding-top:14px;border-top:1px solid var(--line)">
      <h2 style="border:none;padding:0;margin-bottom:10px">เกษียณหนังสือถึง</h2>
      <form method="post" action="<?= e(url('/documents/' . $doc['id'] . '/assign')) ?>">
        <?= Csrf::field() ?>
        <div class="form-grid">
          <div class="field">
            <label for="department_id">ฝ่าย/กลุ่มงาน</label>
            <select id="department_id" name="department_id">
              <option value="">— ไม่ระบุ —</option>
              <?php foreach ($departments as $dep): ?>
                <option value="<?= (int) $dep['id'] ?>"
                  <?= (string) ($doc['owner_department_id'] ?? '') === (string) $dep['id'] ? 'selected' : '' ?>>
                  <?= e($dep['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field">
            <label for="assigned_to">ผู้รับผิดชอบ (ถ้าระบุตัวบุคคล)</label>
            <select id="assigned_to" name="assigned_to">
              <option value="">— ทั้งฝ่าย —</option>
              <?php foreach ($users as $u): ?>
                <option value="<?= (int) $u['id'] ?>">
                  <?= e($u['full_name']) ?><?= $u['department_name'] ? ' (' . e($u['department_name']) . ')' : '' ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field span-full">
            <label for="instruction">คำสั่งการ / ความเห็น</label>
            <textarea id="instruction" name="instruction" rows="2" placeholder="เช่น มอบกลุ่มนโยบายและแผนดำเนินการรายงานภายในกำหนด"></textarea>
          </div>
        </div>
        <button type="submit" class="btn btn-primary">บันทึกการเกษียณ</button>
      </form>
    </div>
  <?php endif; ?>
</div>

<!-- ---------- ไฟล์แนบ ---------- -->
<div class="card">
  <h2>ไฟล์แนบ (<?= count($attachments) ?> ไฟล์)</h2>

  <?php if ($attachments === []): ?>
    <div class="empty">ยังไม่มีไฟล์แนบ</div>
  <?php else: ?>
    <?php foreach ($attachments as $file): ?>
      <div class="file-row">
        <div>
          <a href="<?= e(url('/attachments/' . $file['id'])) ?>" target="_blank"><?= e($file['original_name']) ?></a>
          <div class="muted">
            <?= e(human_size((int) $file['size'])) ?> ·
            อัปโหลดโดย <?= e($file['uploaded_by_name'] ?: '-') ?> เมื่อ <?= e(thai_datetime($file['uploaded_at'])) ?>
          </div>
        </div>
        <?php if (Auth::isClerk()): ?>
          <form method="post" action="<?= e(url('/attachments/' . $file['id'] . '/delete')) ?>"
                onsubmit="return confirm('ยืนยันลบไฟล์นี้?')">
            <?= Csrf::field() ?>
            <button type="submit" class="btn btn-sm btn-danger">ลบ</button>
          </form>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>

  <?php if (Auth::isClerk()): ?>
    <form method="post" action="<?= e(url('/documents/' . $doc['id'] . '/attachments')) ?>"
          enctype="multipart/form-data" style="margin-top:14px;display:flex;gap:9px;flex-wrap:wrap;align-items:center">
      <?= Csrf::field() ?>
      <input type="file" name="file" accept=".pdf,.jpg,.jpeg,.png" required style="width:auto;flex:1;min-width:240px">
      <button type="submit" class="btn btn-primary">แนบไฟล์</button>
      <span class="hint">รองรับ PDF / JPG / PNG ขนาดไม่เกิน <?= e(human_size(UPLOAD_MAX_BYTES)) ?></span>
    </form>
  <?php endif; ?>
</div>

<!-- ---------- ประวัติการดำเนินการ ---------- -->
<div class="card">
  <h2>ประวัติการดำเนินการ</h2>
  <?php if ($logs === []): ?>
    <div class="empty">ยังไม่มีประวัติ</div>
  <?php else: ?>
    <ul class="timeline">
      <?php foreach ($logs as $log): ?>
        <li>
          <strong><?= e(DocLog::actionLabel($log['action'])) ?></strong>
          <?= $log['detail'] ? ' — ' . e($log['detail']) : '' ?>
          <div class="when"><?= e(thai_datetime($log['created_at'])) ?> · <?= e($log['user_name'] ?: 'ระบบ') ?></div>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</div>
