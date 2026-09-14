<?php

use App\Core\Auth;
use App\Core\Csrf;

?>
<div class="page-head">
  <h1>ภาพรวมงานสารบรรณ</h1>
  <?php if (Auth::isClerk()): ?>
    <div class="btn-row">
      <a class="btn btn-primary" href="<?= e(url('/documents/create?direction=incoming')) ?>">+ ลงทะเบียนหนังสือรับ</a>
      <a class="btn btn-blue" href="<?= e(url('/documents/create?direction=outgoing')) ?>">+ ลงทะเบียนหนังสือส่ง</a>
    </div>
  <?php endif; ?>
</div>

<div class="stat-grid">
  <div class="stat stat-in">
    <span class="label">หนังสือรับเดือนนี้</span>
    <span class="value"><?= (int) $stats['incoming_month'] ?></span>
    <span class="muted">ทั้งปี <?= (int) $stats['year_incoming'] ?> ฉบับ</span>
  </div>
  <div class="stat stat-out">
    <span class="label">หนังสือส่งเดือนนี้</span>
    <span class="value"><?= (int) $stats['outgoing_month'] ?></span>
    <span class="muted">ทั้งปี <?= (int) $stats['year_outgoing'] ?> ฉบับ</span>
  </div>
  <div class="stat stat-pending">
    <span class="label">ค้างดำเนินการ</span>
    <span class="value"><?= (int) $stats['pending'] ?></span>
    <span class="muted">รอดำเนินการ + กำลังดำเนินการ</span>
  </div>
  <div class="stat stat-urgent">
    <span class="label">ด่วนที่ยังไม่ปิดเรื่อง</span>
    <span class="value"><?= (int) $stats['urgent_open'] ?></span>
    <span class="muted">ชั้นความเร็ว ด่วนขึ้นไป</span>
  </div>
</div>

<div class="card">
  <h2>งานที่มอบหมายถึงฉัน (<?= count($myTasks) ?> รายการ)</h2>

  <?php if ($myTasks === []): ?>
    <div class="empty">ไม่มีงานค้างที่มอบหมายถึงคุณหรือฝ่ายของคุณ</div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="data">
        <thead>
          <tr>
            <th class="nowrap">ทะเบียน</th>
            <th>เรื่อง</th>
            <th class="nowrap">ชั้นความเร็ว</th>
            <th>คำสั่งการ</th>
            <th class="nowrap">มอบเมื่อ</th>
            <th class="center nowrap">ดำเนินการ</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($myTasks as $task): ?>
          <tr>
            <td class="nowrap">
              <span class="badge <?= $task['direction'] === 'incoming' ? 'badge-dir-in' : 'badge-dir-out' ?>">
                <?= e(DOC_DIRECTIONS[$task['direction']]) ?>
              </span><br>
              <span class="muted"><?= e(reg_label((int) $task['reg_number'], (int) $task['reg_year'])) ?></span>
            </td>
            <td class="subject-cell">
              <a href="<?= e(url('/documents/' . $task['document_id'])) ?>"><?= e($task['subject']) ?></a>
              <div class="muted">
                ที่ <?= e($task['doc_number'] ?: '-') ?> · ลงวันที่ <?= e(thai_date_short($task['doc_date'])) ?>
              </div>
            </td>
            <td class="nowrap"><span class="<?= e(speed_class($task['speed'])) ?>"><?= e($task['speed']) ?></span></td>
            <td class="subject-cell"><?= nl2br(e($task['instruction'] ?: '-')) ?></td>
            <td class="nowrap muted"><?= e(thai_datetime($task['assigned_at'])) ?></td>
            <td class="center">
              <form method="post" action="<?= e(url('/assignments/' . $task['id'] . '/respond')) ?>" style="min-width:180px">
                <?= Csrf::field() ?>
                <select name="status" style="margin-bottom:5px">
                  <?php foreach (DOC_STATUSES as $key => $label): ?>
                    <option value="<?= e($key) ?>" <?= $task['status'] === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                  <?php endforeach; ?>
                </select>
                <input type="text" name="response_note" placeholder="ผลการดำเนินการ" style="margin-bottom:5px">
                <button type="submit" class="btn btn-sm btn-primary">บันทึกผล</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php if ($recent !== []): ?>
  <div class="card">
    <h2>หนังสือที่ลงทะเบียนล่าสุด</h2>
    <div class="table-wrap">
      <table class="data">
        <thead>
          <tr>
            <th class="nowrap">ประเภท</th>
            <th class="nowrap">ทะเบียน</th>
            <th class="nowrap">ที่</th>
            <th>เรื่อง</th>
            <th class="nowrap">สถานะ</th>
            <th class="nowrap">ลงทะเบียนเมื่อ</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($recent as $row): ?>
          <tr>
            <td class="nowrap">
              <span class="badge <?= $row['direction'] === 'incoming' ? 'badge-dir-in' : 'badge-dir-out' ?>">
                <?= e(DOC_DIRECTIONS[$row['direction']]) ?>
              </span>
            </td>
            <td class="nowrap"><?= e(reg_label((int) $row['reg_number'], (int) $row['reg_year'])) ?></td>
            <td class="nowrap"><?= e($row['doc_number'] ?: '-') ?></td>
            <td class="subject-cell">
              <a href="<?= e(url('/documents/' . $row['id'])) ?>"><?= e($row['subject']) ?></a>
            </td>
            <td class="nowrap"><span class="<?= e(status_class($row['status'])) ?>"><?= e(DOC_STATUSES[$row['status']]) ?></span></td>
            <td class="nowrap muted"><?= e(thai_datetime($row['reg_datetime'])) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>
