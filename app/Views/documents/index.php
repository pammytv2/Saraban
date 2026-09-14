<?php

use App\Core\Auth;
use App\Core\Csrf;

$totalPages = max(1, (int) ceil($total / $perPage));
$direction = $filters['direction'];

/** สร้าง query string โดยคงตัวกรองเดิมไว้ */
$qs = static function (array $overrides = []) use ($filters, $activeView): string {
    $params = array_filter(array_merge([
        'direction'  => $filters['direction'],
        'year'       => $filters['year'],
        'status'     => $filters['status'],
        'speed'      => $filters['speed'],
        'doc_type'   => $filters['doc_type'],
        'department' => $filters['department'],
        'date_from'  => $filters['date_from'],
        'date_to'    => $filters['date_to'],
        'q'          => $filters['q'],
        'view'       => $activeView,
    ], $overrides), static fn($v): bool => $v !== '' && $v !== null);
    return $params === [] ? '' : '?' . http_build_query($params);
};

/** เส้นทางปัจจุบัน ใช้พากลับมาที่เดิมหลังกดปุ่มในแถว */
$here = '/documents' . $qs(['page' => $page > 1 ? $page : null]);

/** ชื่อฝ่ายไว้แสดงในชิปตัวกรอง */
$deptName = '';
foreach ($departments as $dep) {
    if ((string) $filters['department'] === (string) $dep['id']) {
        $deptName = $dep['name'];
    }
}

/** ตัวกรองละเอียดที่เปิดอยู่ตอนนี้ (ใช้ตัดสินว่าจะกางแผงให้เลยหรือไม่) */
$advActive = ($filters['year'] !== '' || $filters['status'] !== '' || $filters['speed'] !== ''
    || $filters['doc_type'] !== '' || $filters['department'] !== ''
    || $filters['date_from'] !== '' || $filters['date_to'] !== '');

/** ชิปบอกว่าตอนนี้กรองอะไรอยู่ กดกากบาทเพื่อเอาออกทีละอัน */
$chips = [];
if ($activeView !== '') {
    $label = DOC_VIEWS[$activeView];
    if ($activeView === 'mine' && !$hasDept) {
        $label = 'ค้างดำเนินการ';
    }
    $chips[] = ['k' => 'มุมมอง', 'v' => $label, 'url' => $qs(['view' => null, 'page' => null])];
}
if ($filters['status'] !== '' && isset(DOC_STATUSES[$filters['status']])) {
    $chips[] = ['k' => 'สถานะ', 'v' => DOC_STATUSES[$filters['status']], 'url' => $qs(['status' => null, 'page' => null])];
}
if ($filters['speed'] !== '') {
    $chips[] = ['k' => 'ชั้นความเร็ว', 'v' => $filters['speed'], 'url' => $qs(['speed' => null, 'page' => null])];
}
if ($filters['doc_type'] !== '') {
    $chips[] = ['k' => 'ประเภทหนังสือ', 'v' => $filters['doc_type'], 'url' => $qs(['doc_type' => null, 'page' => null])];
}
if ($deptName !== '') {
    $chips[] = ['k' => 'ฝ่าย', 'v' => $deptName, 'url' => $qs(['department' => null, 'page' => null])];
}
if ($filters['year'] !== '') {
    $chips[] = ['k' => 'ปี พ.ศ.', 'v' => $filters['year'], 'url' => $qs(['year' => null, 'page' => null])];
}
if ($filters['date_from'] !== '' || $filters['date_to'] !== '') {
    $chips[] = [
        'k' => 'ลงทะเบียน',
        'v' => ($filters['date_from'] !== '' ? thai_date_short($filters['date_from']) : 'ต้นทาง')
             . ' – ' . ($filters['date_to'] !== '' ? thai_date_short($filters['date_to']) : 'ปัจจุบัน'),
        'url' => $qs(['date_from' => null, 'date_to' => null, 'page' => null]),
    ];
}
?>
<div class="page-head">
  <div>
    <h1><?= e($title) ?></h1>
    <div class="page-sub"><?= number_format($total) ?> ฉบับตามเงื่อนไขที่เลือกอยู่</div>
  </div>
  <div class="btn-row">
    <?php if ($direction !== ''): ?>
      <a class="btn btn-ghost" target="_blank"
         href="<?= e(url('/documents/print/register' . $qs(['page' => null]))) ?>">พิมพ์ทะเบียนคุม</a>
    <?php endif; ?>
    <?php if (Auth::isClerk()): ?>
      <a class="btn btn-primary"
         href="<?= e(url('/documents/create?direction=' . ($direction !== '' ? $direction : 'incoming'))) ?>">
        + ลงทะเบียนหนังสือ<?= $direction === 'outgoing' ? 'ส่ง' : 'รับ' ?>
      </a>
    <?php endif; ?>
  </div>
</div>

<form method="get" action="<?= e(url('/documents')) ?>" id="seek-form">
  <input type="hidden" name="view" value="<?= e($activeView) ?>">

  <!-- ค้นหาบรรทัดเดียว พิมพ์แล้วกรองให้เอง ไม่ต้องกดปุ่ม -->
  <div class="seek">
    <input type="text" name="q" id="seek-q" value="<?= e($filters['q']) ?>" autocomplete="off"
           placeholder="พิมพ์เพื่อค้นหา — เรื่อง เลขที่หนังสือ หรือชื่อหน่วยงาน">
    <span class="seek-kbd" aria-hidden="true">/</span>
    <button type="submit" class="seek-go">ค้นหา</button>
  </div>

  <!-- มุมมองสำเร็จรูป: คำถามที่ถามกันทุกวัน กดครั้งเดียวได้คำตอบ -->
  <nav class="views">
    <a class="view <?= $activeView === '' ? 'on' : '' ?>" href="<?= e(url('/documents' . $qs(['view' => null, 'page' => null]))) ?>">
      ทั้งหมด <span class="n"><?= (int) ($viewCounts[''] ?? 0) ?></span>
    </a>
    <?php foreach (DOC_VIEWS as $key => $label): ?>
      <?php if ($key === 'mine' && !$hasDept) { $label = 'ค้างดำเนินการ'; } ?>
      <?php if ($key === 'nodept' && !Auth::isClerk()) { continue; } ?>
      <a class="view <?= $activeView === $key ? 'on' : '' ?><?= $key === 'urgent' ? ' urgent' : '' ?>"
         href="<?= e(url('/documents' . $qs(['view' => $key, 'page' => null]))) ?>">
        <?= e($label) ?> <span class="n"><?= (int) ($viewCounts[$key] ?? 0) ?></span>
      </a>
    <?php endforeach; ?>
  </nav>

  <div class="chips">
    <?php foreach ($chips as $chip): ?>
      <span class="chip"><?= e($chip['k']) ?> <b><?= e($chip['v']) ?></b><a
        href="<?= e(url('/documents' . $chip['url'])) ?>" title="เอาตัวกรองนี้ออก">&times;</a></span>
    <?php endforeach; ?>

    <details class="adv" <?= $advActive ? 'open' : '' ?>>
      <summary>ตัวกรองละเอียด<span class="adv-hint"> — ประเภท · ปี · สถานะ · ช่วงวันที่</span></summary>
      <div class="adv-grid">
        <div class="field">
          <label for="f-direction">ประเภททะเบียน</label>
          <select name="direction" id="f-direction">
            <option value="">ทั้งหมด</option>
            <?php foreach (DOC_DIRECTIONS as $key => $label): ?>
              <option value="<?= e($key) ?>" <?= $filters['direction'] === $key ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label for="f-year">ปี พ.ศ.</label>
          <select name="year" id="f-year">
            <option value="">ทุกปี</option>
            <?php foreach ($years as $y): ?>
              <option value="<?= (int) $y ?>" <?= (string) $filters['year'] === (string) $y ? 'selected' : '' ?>><?= (int) $y ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label for="f-status">สถานะ</label>
          <select name="status" id="f-status">
            <option value="">ทั้งหมด</option>
            <?php foreach (DOC_STATUSES as $key => $label): ?>
              <option value="<?= e($key) ?>" <?= $filters['status'] === $key ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label for="f-speed">ชั้นความเร็ว</label>
          <select name="speed" id="f-speed">
            <option value="">ทั้งหมด</option>
            <?php foreach (DOC_SPEEDS as $s): ?>
              <option value="<?= e($s) ?>" <?= $filters['speed'] === $s ? 'selected' : '' ?>><?= e($s) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label for="f-doctype">ประเภทหนังสือ</label>
          <select name="doc_type" id="f-doctype">
            <option value="">ทั้งหมด</option>
            <?php foreach (DOC_TYPES as $t): ?>
              <option value="<?= e($t) ?>" <?= $filters['doc_type'] === $t ? 'selected' : '' ?>><?= e($t) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label for="f-dept">ฝ่ายเจ้าของเรื่อง</label>
          <select name="department" id="f-dept">
            <option value="">ทั้งหมด</option>
            <?php foreach ($departments as $dep): ?>
              <option value="<?= (int) $dep['id'] ?>" <?= (string) $filters['department'] === (string) $dep['id'] ? 'selected' : '' ?>>
                <?= e($dep['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label for="f-from">ลงทะเบียนตั้งแต่</label>
          <input type="date" name="date_from" id="f-from" value="<?= e($filters['date_from']) ?>">
        </div>
        <div class="field">
          <label for="f-to">ถึงวันที่</label>
          <input type="date" name="date_to" id="f-to" value="<?= e($filters['date_to']) ?>">
        </div>
        <div class="field">
          <label>&nbsp;</label>
          <div class="btn-row">
            <button type="submit" class="btn btn-primary">ใช้ตัวกรอง</button>
            <a class="btn btn-ghost" href="<?= e(url('/documents' . ($direction !== '' ? '?direction=' . $direction : ''))) ?>">ล้างทั้งหมด</a>
          </div>
        </div>
      </div>
    </details>
  </div>
</form>

<?php if ($rows === []): ?>
  <div class="empty-state">
    <p>ไม่พบหนังสือตามเงื่อนไขที่เลือกอยู่</p>
    <a class="btn btn-ghost" href="<?= e(url('/documents' . ($direction !== '' ? '?direction=' . $direction : ''))) ?>">ล้างตัวกรองทั้งหมด</a>
  </div>
<?php else: ?>
  <div class="table-wrap">
    <table class="data">
      <thead>
        <tr>
          <th class="nowrap">ทะเบียน</th>
          <th class="nowrap">ที่ / ลงวันที่</th>
          <th>เรื่อง</th>
          <th class="nowrap">ชั้นความเร็ว</th>
          <th class="nowrap">สถานะ</th>
          <th class="nowrap right">ทำต่อ</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($rows as $row): ?>
        <tr>
          <td class="nowrap">
            <?php if ($direction === ''): ?>
              <span class="badge <?= $row['direction'] === 'incoming' ? 'badge-dir-in' : 'badge-dir-out' ?>">
                <?= e(DOC_DIRECTIONS[$row['direction']]) ?>
              </span><br>
            <?php endif; ?>
            <strong><?= e(reg_label((int) $row['reg_number'], (int) $row['reg_year'])) ?></strong>
            <div class="muted"><?= e(thai_datetime($row['reg_datetime'])) ?></div>
          </td>
          <td class="nowrap">
            <?= e($row['doc_number'] ?: '-') ?>
            <div class="muted"><?= e(thai_date_short($row['doc_date'])) ?></div>
          </td>
          <td class="subject-cell">
            <?php
            $party = $row['direction'] === 'outgoing'
                ? ($row['to_org_name'] ?: $row['to_text'])
                : ($row['from_org_name'] ?: $row['from_text']);
            $partyLabel = $row['direction'] === 'outgoing' ? 'ถึง' : 'จาก';
            ?>
            <a href="<?= e(url('/documents/' . $row['id'])) ?>"><?= e($row['subject']) ?></a>
            <?php if ($row['secrecy'] !== 'ปกติ'): ?>
              <span class="badge badge-secret"><?= e($row['secrecy']) ?></span>
            <?php endif; ?>
            <div class="cell-meta">
              <?= e($partyLabel) ?> <?= e($party ?: '-') ?>
              <?php if (!empty($row['owner_department_name'])): ?>
                <span class="cell-sep">·</span> <?= e($row['owner_department_name']) ?>
              <?php else: ?>
                <span class="cell-sep">·</span> ยังไม่ได้ส่งฝ่าย
              <?php endif; ?>
            </div>
          </td>
          <td class="nowrap"><span class="<?= e(speed_class($row['speed'])) ?>"><?= e($row['speed']) ?></span></td>
          <td class="nowrap"><span class="<?= e(status_class($row['status'])) ?>"><?= e(DOC_STATUSES[$row['status']]) ?></span></td>
          <td class="nowrap right">
            <!-- ทำงานจบได้จากในแถว ไม่ต้องเปิดหน้ารายละเอียดก่อน -->
            <div class="row-acts">
              <a class="btn btn-sm btn-ghost" target="_blank"
                 href="<?= e(url('/documents/' . $row['id'] . '/print/cover')) ?>">ใบปะหน้า</a>
              <?php if (Auth::isClerk() && in_array($row['status'], ['pending', 'in_progress'], true)): ?>
                <form method="post" action="<?= e(url('/documents/' . $row['id'] . '/status')) ?>">
                  <?= Csrf::field() ?>
                  <input type="hidden" name="status" value="done">
                  <input type="hidden" name="back" value="<?= e($here) ?>">
                  <button type="submit" class="btn btn-sm btn-ghost">ทำเสร็จแล้ว</button>
                </form>
              <?php endif; ?>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php if ($totalPages > 1): ?>
    <div class="pagination">
      <?php if ($page > 1): ?>
        <a href="<?= e(url('/documents' . $qs(['page' => $page - 1]))) ?>">ก่อนหน้า</a>
      <?php endif; ?>

      <?php
      $start = max(1, $page - 2);
      $end = min($totalPages, $page + 2);
      for ($i = $start; $i <= $end; $i++):
      ?>
        <?php if ($i === $page): ?>
          <span class="current"><?= $i ?></span>
        <?php else: ?>
          <a href="<?= e(url('/documents' . $qs(['page' => $i]))) ?>"><?= $i ?></a>
        <?php endif; ?>
      <?php endfor; ?>

      <?php if ($page < $totalPages): ?>
        <a href="<?= e(url('/documents' . $qs(['page' => $page + 1]))) ?>">ถัดไป</a>
      <?php endif; ?>
    </div>
    <p class="muted" style="text-align:center">หน้า <?= $page ?> จาก <?= $totalPages ?></p>
  <?php endif; ?>
<?php endif; ?>

<script>
(function () {
  var form = document.getElementById('seek-form');
  var box  = document.getElementById('seek-q');
  if (!form || !box) return;

  // มี JS แล้วไม่ต้องใช้ปุ่ม ค้นหา — พิมพ์แล้วกรองให้เอง
  form.classList.add('live');

  var timer = null;
  box.addEventListener('input', function () {
    clearTimeout(timer);
    timer = setTimeout(function () { form.submit(); }, 450);
  });

  // กด / จากที่ไหนก็ได้เพื่อไปช่องค้นหา, Esc เพื่อล้างคำค้น
  document.addEventListener('keydown', function (ev) {
    var tag = (ev.target.tagName || '').toLowerCase();
    var typing = tag === 'input' || tag === 'select' || tag === 'textarea';
    if (ev.key === '/' && !typing) {
      ev.preventDefault();
      box.focus();
      box.select();
    } else if (ev.key === 'Escape' && ev.target === box && box.value !== '') {
      box.value = '';
      clearTimeout(timer);
      form.submit();
    }
  });

  // เอาเคอร์เซอร์ไว้ท้ายคำค้นเดิม จะได้พิมพ์ต่อได้ทันทีหลังหน้าโหลดใหม่
  if (box.value !== '') {
    box.focus();
    box.setSelectionRange(box.value.length, box.value.length);
  }
})();
</script>
