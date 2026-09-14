<?php

use App\Models\Document;

$isIncoming = $doc['direction'] === 'incoming';
?>
<div class="doc-head">
  <div class="org"><?= e($settings['org_name'] ?? APP_NAME) ?></div>
  <?php if (!empty($settings['org_address'])): ?>
    <div class="sub"><?= e($settings['org_address']) ?></div>
  <?php endif; ?>
</div>

<div class="doc-title">
  ใบปะหน้า<?= e(DOC_DIRECTIONS[$doc['direction']]) ?>
</div>

<table class="sheet-table">
  <tr>
    <td class="label">ทะเบียนรับ-ส่งเลขที่</td>
    <td style="width:38%">
      <strong style="font-size:18px"><?= e(reg_label((int) $doc['reg_number'], (int) $doc['reg_year'])) ?></strong>
    </td>
    <td class="label">ลงทะเบียนเมื่อ</td>
    <td><?= e(thai_datetime($doc['reg_datetime'])) ?></td>
  </tr>
  <tr>
    <td class="label">ที่ (เลขที่หนังสือ)</td>
    <td><?= e($doc['doc_number'] ?: '-') ?></td>
    <td class="label">ลงวันที่</td>
    <td><?= e(thai_date($doc['doc_date'])) ?></td>
  </tr>
  <tr>
    <td class="label">จาก</td>
    <td colspan="3"><?= e(Document::partyName($doc, 'from')) ?></td>
  </tr>
  <tr>
    <td class="label">ถึง</td>
    <td colspan="3"><?= e(Document::partyName($doc, 'to')) ?></td>
  </tr>
  <tr>
    <td class="label">เรื่อง</td>
    <td colspan="3"><strong><?= e($doc['subject']) ?></strong></td>
  </tr>
  <tr>
    <td class="label">ประเภทหนังสือ</td>
    <td><?= e($doc['doc_type']) ?></td>
    <td class="label">ฝ่ายเจ้าของเรื่อง</td>
    <td><?= e($doc['owner_department_name'] ?: '-') ?></td>
  </tr>
  <tr>
    <td class="label">ชั้นความเร็ว</td>
    <td>
      <?php foreach (DOC_SPEEDS as $s): ?>
        <span style="margin-right:14px;white-space:nowrap"><?= $doc['speed'] === $s ? '☑' : '☐' ?> <?= e($s) ?></span>
      <?php endforeach; ?>
    </td>
    <td class="label">ชั้นความลับ</td>
    <td>
      <?php foreach (DOC_SECRECY as $s): ?>
        <span style="margin-right:10px;white-space:nowrap"><?= $doc['secrecy'] === $s ? '☑' : '☐' ?> <?= e($s) ?></span>
      <?php endforeach; ?>
    </td>
  </tr>
  <?php if (!$isIncoming): ?>
    <tr>
      <td class="label">ผู้ลงนาม</td>
      <td colspan="3"><?= e($doc['signer'] ?: '-') ?></td>
    </tr>
  <?php endif; ?>
  <?php if (!empty($doc['detail'])): ?>
    <tr>
      <td class="label">รายละเอียด</td>
      <td colspan="3"><?= nl2br(e($doc['detail'])) ?></td>
    </tr>
  <?php endif; ?>
</table>

<!-- ช่องเกษียณหนังสือ -->
<div class="memo-box">
  <div class="memo-head">การเกษียณหนังสือ / คำสั่งการ</div>
  <div class="memo-body">
    <?php if ($assignments === []): ?>
      <div style="color:#666">(ยังไม่มีการเกษียณ — ใช้พื้นที่ด้านล่างสำหรับเขียนด้วยลายมือ)</div>
    <?php else: ?>
      <?php foreach ($assignments as $a): ?>
        <div class="memo-entry">
          <div><strong>มอบ <?= e($a['department_name'] ?: 'ไม่ระบุฝ่าย') ?></strong>
            <?= !empty($a['assigned_to_name']) ? ' (' . e($a['assigned_to_name']) . ')' : '' ?>
          </div>
          <?php if (!empty($a['instruction'])): ?>
            <div><?= nl2br(e($a['instruction'])) ?></div>
          <?php endif; ?>
          <div class="who">
            โดย <?= e($a['assigned_by_name'] ?: '-') ?> · <?= e(thai_datetime($a['assigned_at'])) ?>
            · สถานะ: <?= e(DOC_STATUSES[$a['status']]) ?>
          </div>
          <?php if (!empty($a['response_note'])): ?>
            <div class="who">ผลการดำเนินการ: <?= e($a['response_note']) ?> (<?= e(thai_datetime($a['responded_at'])) ?>)</div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<div class="sign-row">
  <div class="sign">
    <div class="line"></div>
    (........................................................)<br>
    เจ้าหน้าที่สารบรรณ
  </div>
  <div class="sign">
    <div class="line"></div>
    (........................................................)<br>
    ผู้บังคับบัญชา / ผู้สั่งการ
  </div>
</div>

<div class="print-meta">
  <span>สถานะปัจจุบัน: <?= e(DOC_STATUSES[$doc['status']]) ?></span>
  <span>พิมพ์เมื่อ <?= e(thai_datetime(date('Y-m-d H:i:s'))) ?></span>
</div>
