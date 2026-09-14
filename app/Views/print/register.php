<?php

$isIncoming = $direction === 'incoming';
?>
<div class="doc-head">
  <div class="org"><?= e($settings['org_name'] ?? APP_NAME) ?></div>
  <div class="sub" style="font-size:17px;font-weight:700;margin-top:4px">
    ทะเบียน<?= e(DOC_DIRECTIONS[$direction]) ?> ประจำปี พ.ศ. <?= e((string) ($filters['year'] ?: be_year())) ?>
  </div>
  <?php if (!empty($filters['date_from']) || !empty($filters['date_to'])): ?>
    <div class="sub">
      ช่วงวันที่ <?= e(thai_date($filters['date_from'] ?: null, 'ต้นปี')) ?>
      ถึง <?= e(thai_date($filters['date_to'] ?: null, 'ปัจจุบัน')) ?>
    </div>
  <?php endif; ?>
</div>

<table class="register">
  <thead>
    <tr>
      <th style="width:62px">ทะเบียนที่</th>
      <th style="width:78px">วันที่ลงทะเบียน</th>
      <th style="width:95px">ที่ (เลขที่หนังสือ)</th>
      <th style="width:70px">ลงวันที่</th>
      <th style="width:150px"><?= $isIncoming ? 'จาก' : 'ถึง' ?></th>
      <th>เรื่อง</th>
      <th style="width:110px">ฝ่ายเจ้าของเรื่อง</th>
      <th style="width:58px">ชั้นความเร็ว</th>
      <th style="width:70px">สถานะ</th>
      <th style="width:80px">หมายเหตุ</th>
    </tr>
  </thead>
  <tbody>
    <?php if ($rows === []): ?>
      <tr><td colspan="10" class="center" style="padding:22px">ไม่พบข้อมูลตามเงื่อนไขที่เลือก</td></tr>
    <?php else: ?>
      <?php foreach ($rows as $row): ?>
        <tr>
          <td class="center"><?= e(reg_label((int) $row['reg_number'], (int) $row['reg_year'])) ?></td>
          <td class="center"><?= e(thai_date_short($row['reg_datetime'])) ?></td>
          <td><?= e($row['doc_number'] ?: '-') ?></td>
          <td class="center"><?= e(thai_date_short($row['doc_date'])) ?></td>
          <td>
            <?php
            $party = $isIncoming
                ? ($row['from_org_name'] ?: $row['from_text'])
                : ($row['to_org_name'] ?: $row['to_text']);
            echo e($party ?: '-');
            ?>
          </td>
          <td>
            <?= e($row['subject']) ?>
            <?php if ($row['secrecy'] !== 'ปกติ'): ?>
              <strong>[<?= e($row['secrecy']) ?>]</strong>
            <?php endif; ?>
          </td>
          <td><?= e($row['owner_department_name'] ?: '-') ?></td>
          <td class="center"><?= e($row['speed']) ?></td>
          <td class="center"><?= e(DOC_STATUSES[$row['status']]) ?></td>
          <td></td>
        </tr>
      <?php endforeach; ?>
    <?php endif; ?>
  </tbody>
</table>

<div class="print-meta">
  <span>รวมทั้งสิ้น <?= count($rows) ?> ฉบับ</span>
  <span>พิมพ์เมื่อ <?= e(thai_datetime(date('Y-m-d H:i:s'))) ?></span>
</div>

<div class="sign-row" style="margin-top:26px">
  <div class="sign">
    <div class="line"></div>
    (........................................................)<br>
    เจ้าหน้าที่สารบรรณ ผู้จัดทำ
  </div>
  <div class="sign">
    <div class="line"></div>
    (........................................................)<br>
    ผู้ตรวจสอบ
  </div>
</div>
