<?php

use App\Core\Csrf;

$isEdit = $doc !== null;
$action = $isEdit ? url('/documents/' . $doc['id']) : url('/documents');
$isOutgoing = $direction === 'outgoing';
$old = $old ?? [];
$remember = $remember ?? [];

/**
 * ค่าที่จะเติมในช่อง — เรียงจากสิ่งที่ผู้ใช้เพิ่งพิมพ์ (กรณีบันทึกไม่ผ่าน)
 * แล้วค่อยเป็นค่าเดิมของหนังสือ แล้วค่อยเป็นค่าที่จำไว้จากฉบับก่อน
 */
$val = static function (string $key, string $default = '') use ($old, $doc, $remember): string {
    if (array_key_exists($key, $old)) {
        return (string) $old[$key];
    }
    if ($doc !== null && array_key_exists($key, $doc)) {
        return (string) ($doc[$key] ?? '');
    }
    if (array_key_exists($key, $remember) && $remember[$key] !== null) {
        return (string) $remember[$key];
    }
    return $default;
};

/** ชื่อหน่วยงานที่จะโชว์ในช่องเดียว (เลือกจากรายการแล้วใช้ชื่อในรายการ ไม่งั้นใช้ที่พิมพ์เอง) */
$partyText = static function (string $side) use ($old, $doc): string {
    if (array_key_exists($side . '_text', $old)) {
        return (string) $old[$side . '_text'];
    }
    if ($doc !== null) {
        return (string) ($doc[$side . '_org_name'] ?? '') ?: (string) ($doc[$side . '_text'] ?? '');
    }
    return '';
};

$docDateValue = array_key_exists('doc_date', $old)
    ? (string) $old['doc_date']
    : ($isEdit ? thai_date_input($doc['doc_date']) : '');

$regDatetimeValue = array_key_exists('reg_datetime', $old)
    ? (string) $old['reg_datetime']
    : thai_datetime_input(date('Y-m-d H:i:s'));

$selectedDept = $val('owner_department_id');
$docTypeValue = $val('doc_type', 'ภายนอก');
$speedValue   = $val('speed', 'ปกติ');
$secrecyValue = $val('secrecy', 'ปกติ');

/** ฝั่งที่เป็นพระเอกของหนังสือใบนี้: หนังสือรับสนใจ "จาก" หนังสือส่งสนใจ "ถึง" */
$mainSide  = $isOutgoing ? 'to' : 'from';
$otherSide = $isOutgoing ? 'from' : 'to';
$sideLabel = ['from' => 'จาก (หน่วยงานผู้ส่ง)', 'to' => 'ถึง (ผู้รับ)'];
$sidePlaceholder = [
    'from' => 'พิมพ์ชื่อหน่วยงาน — เลือกจากรายการ หรือพิมพ์ชื่อใหม่ได้เลย',
    'to'   => 'เช่น ผู้อำนวยการโรงเรียนในสังกัดทุกโรง',
];

/** ช่องหน่วยงานแบบช่องเดียว: พิมพ์ค้นหาก็ได้ พิมพ์ชื่อที่ไม่มีในระบบก็ได้ */
$partyField = static function (string $side) use ($partyText, $val, $organizations, $sideLabel, $sidePlaceholder): void {
    ?>
    <div class="field combo" data-combo>
      <label for="<?= $side ?>_text"><?= e($sideLabel[$side]) ?></label>
      <input type="text" id="<?= $side ?>_text" name="<?= $side ?>_text" data-combo-input autocomplete="off"
             value="<?= e($partyText($side)) ?>" placeholder="<?= e($sidePlaceholder[$side]) ?>">
      <input type="hidden" name="<?= $side ?>_org_id" data-combo-id value="<?= e($val($side . '_org_id')) ?>">
      <div class="combo-list" data-combo-list hidden></div>
      <span class="hint">ช่องเดียวจบ — ถ้าชื่อตรงกับหน่วยงานในระบบจะผูกให้อัตโนมัติ</span>
      <noscript>
        <label for="<?= $side ?>_org_id_plain">หรือเลือกจากรายการหน่วยงาน</label>
        <select id="<?= $side ?>_org_id_plain" name="<?= $side ?>_org_id">
          <option value="">— ไม่ระบุ —</option>
          <?php foreach ($organizations as $org): ?>
            <option value="<?= (int) $org['id'] ?>" <?= $val($side . '_org_id') === (string) $org['id'] ? 'selected' : '' ?>>
              <?= e($org['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </noscript>
    </div>
    <?php
};
?>
<div class="page-head">
  <div>
    <h1><?= e($title) ?></h1>
    <div class="page-sub">
      <?php if ($isEdit): ?>
        แก้ไขได้ทุกช่อง ยกเว้นเลขทะเบียน
      <?php else: ?>
        บังคับกรอกแค่ “เรื่อง” ช่องเดียว ที่เหลือเติมทีหลังได้
      <?php endif; ?>
    </div>
  </div>
  <?php if ($isEdit): ?>
    <div class="will">
      เลขทะเบียน
      <b><?= e(reg_label((int) $doc['reg_number'], (int) $doc['reg_year'])) ?></b>
      <span>ลงทะเบียน <?= e(thai_datetime($doc['reg_datetime'])) ?></span>
    </div>
  <?php else: ?>
    <div class="will">
      จะได้เลขทะเบียน
      <b><?= e(reg_label($nextReg, be_year())) ?></b>
      <span>ออกเลขจริงตอนกดบันทึก</span>
    </div>
  <?php endif; ?>
</div>

<?php if (!$isEdit && !empty($justSaved)): ?>
  <!-- เพิ่งบันทึกฉบับก่อนไป ยืนยันพร้อมทางไปต่อ แล้วกรอกฉบับถัดไปได้เลย -->
  <div class="saved-strip">
    <span>บันทึกแล้ว เลขทะเบียน <b><?= e($justSaved['label']) ?></b></span>
    <a class="btn btn-sm btn-ghost" target="_blank"
       href="<?= e(url('/documents/' . $justSaved['id'] . '/print/cover')) ?>">พิมพ์ใบปะหน้า</a>
    <a class="btn btn-sm btn-ghost" href="<?= e(url('/documents/' . $justSaved['id'])) ?>">เปิดดู</a>
    <span class="sp"></span>
    <span class="hint">ฝ่ายและประเภทหนังสือถูกจำไว้ให้แล้ว กรอกฉบับถัดไปต่อได้เลย</span>
  </div>
<?php endif; ?>

<form method="post" action="<?= e($action) ?>" class="doc-form">
  <?= Csrf::field() ?>
  <input type="hidden" name="direction" value="<?= e($direction) ?>">

  <!-- 1. สิ่งที่ต้องอ่านจากหนังสือในมือ เรียงตามลำดับที่คนกรอกจริง -->
  <section class="group">
    <h2>กรอกจากหนังสือที่อยู่ในมือ</h2>

    <div class="field">
      <label for="subject">เรื่อง <span class="req">*</span></label>
      <input class="input-lg" type="text" id="subject" name="subject" required maxlength="500" autocomplete="off"
             value="<?= e($val('subject')) ?>"
             placeholder="เช่น ขอเชิญประชุมผู้บริหารสถานศึกษา ครั้งที่ 5/2569">
    </div>

    <?php $partyField($mainSide); ?>

    <div class="field-pair">
      <div class="field">
        <label for="doc_number">ที่ (เลขที่หนังสือ)</label>
        <input class="input-mono" type="text" id="doc_number" name="doc_number" autocomplete="off"
               value="<?= e($val('doc_number')) ?>"
               placeholder="<?= $isOutgoing ? e(($settings['org_code_prefix'] ?? '') . '/') : 'ศธ 04002/ว 112' ?>">
      </div>
      <div class="field">
        <label for="doc_date">ลงวันที่ (ตามหนังสือ)</label>
        <div class="dateline">
          <input class="input-mono" type="text" id="doc_date" name="doc_date" maxlength="10" inputmode="numeric"
                 autocomplete="off" data-thai-date value="<?= e($docDateValue) ?>" placeholder="วว/ดด/ปปปป">
          <span class="quick">
            <button type="button" data-date-offset="0">วันนี้</button>
            <button type="button" data-date-offset="-1">เมื่อวาน</button>
          </span>
        </div>
        <span class="hint" data-date-echo>เป็นปี พ.ศ. — พิมพ์แค่ตัวเลข ระบบใส่ / ให้เอง</span>
      </div>
    </div>

    <div class="field">
      <label>ฝ่ายเจ้าของเรื่อง</label>
      <div class="pick">
        <?php foreach ($departments as $dep): ?>
          <label class="pick-item <?= $selectedDept === (string) $dep['id'] ? 'on' : '' ?>">
            <input type="radio" name="owner_department_id" value="<?= (int) $dep['id'] ?>"
                   <?= $selectedDept === (string) $dep['id'] ? 'checked' : '' ?>>
            <?= e($dep['name']) ?>
          </label>
        <?php endforeach; ?>
        <label class="pick-item <?= $selectedDept === '' ? 'on' : '' ?>">
          <input type="radio" name="owner_department_id" value="" <?= $selectedDept === '' ? 'checked' : '' ?>>
          ยังไม่ระบุ
        </label>
      </div>
    </div>

    <?php if (!$isEdit): ?>
      <label class="remember">
        <input type="checkbox" name="remember_department" value="1" checked>
        จำฝ่ายนี้ไว้ให้ฉบับถัดไป
      </label>
    <?php endif; ?>
  </section>

  <!-- 2. ที่เหลือพับไว้ แต่โชว์ค่าปัจจุบันบนหัวข้อ จะได้ไม่ต้องกางมาดู -->
  <section class="group">
    <h2>ส่วนที่มักไม่ต้องแตะ</h2>

    <details class="fold">
      <summary>
        <span class="cap">ประเภท / ชั้นความเร็ว / ชั้นความลับ</span>
        <span class="val" data-fold-summary><?= e($docTypeValue . ' · ' . $speedValue . ' · ' . $secrecyValue) ?></span>
        <span class="act">แก้ไข</span>
      </summary>
      <div class="fold-body">
        <div class="form-grid">
          <div class="field">
            <label for="doc_type">ประเภทหนังสือ</label>
            <select id="doc_type" name="doc_type" data-fold-part>
              <?php foreach (DOC_TYPES as $type): ?>
                <option value="<?= e($type) ?>" <?= $docTypeValue === $type ? 'selected' : '' ?>><?= e($type) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field">
            <label for="speed">ชั้นความเร็ว</label>
            <select id="speed" name="speed" data-fold-part>
              <?php foreach (DOC_SPEEDS as $s): ?>
                <option value="<?= e($s) ?>" <?= $speedValue === $s ? 'selected' : '' ?>><?= e($s) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field">
            <label for="secrecy">ชั้นความลับ</label>
            <select id="secrecy" name="secrecy" data-fold-part>
              <?php foreach (DOC_SECRECY as $s): ?>
                <option value="<?= e($s) ?>" <?= $secrecyValue === $s ? 'selected' : '' ?>><?= e($s) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <?php if (!$isEdit): ?>
            <div class="field">
              <label for="reg_datetime">วันเวลาที่ลงทะเบียน</label>
              <input class="input-mono" type="text" id="reg_datetime" name="reg_datetime" autocomplete="off"
                     value="<?= e($regDatetimeValue) ?>" placeholder="วว/ดด/ปปปป ชช:นน">
              <span class="hint">ปกติใช้เวลาปัจจุบัน แก้ได้ถ้าลงย้อนหลัง — ใช้กำหนดปี พ.ศ. ของเลขทะเบียน</span>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </details>

    <details class="fold" <?= $partyText($otherSide) !== '' ? 'open' : '' ?>>
      <summary>
        <span class="cap"><?= $otherSide === 'to' ? 'ถึง (ผู้รับ)' : 'จาก (ผู้ส่ง)' ?></span>
        <span class="val"><?= e($partyText($otherSide) ?: ($otherSide === 'to' ? 'ไม่ระบุ — หน่วยงานเราเป็นผู้รับ' : 'ไม่ระบุ — หน่วยงานเราเป็นผู้ส่ง')) ?></span>
        <span class="act">แก้ไข</span>
      </summary>
      <div class="fold-body">
        <?php $partyField($otherSide); ?>
      </div>
    </details>

    <details class="fold" <?= $val('detail') !== '' ? 'open' : '' ?>>
      <summary>
        <span class="cap">รายละเอียด / การปฏิบัติ</span>
        <span class="val"><?= e($val('detail') !== '' ? mb_substr($val('detail'), 0, 48) : 'ยังไม่ได้กรอก') ?></span>
        <span class="act">เพิ่ม</span>
      </summary>
      <div class="fold-body">
        <div class="field">
          <label for="detail">รายละเอียด</label>
          <textarea id="detail" name="detail" rows="3"
                    placeholder="สรุปสาระสำคัญ หรือสิ่งที่ต้องดำเนินการ"><?= e($val('detail')) ?></textarea>
        </div>
      </div>
    </details>

    <?php if ($isOutgoing): ?>
      <details class="fold" <?= $val('signer') !== '' ? 'open' : '' ?>>
        <summary>
          <span class="cap">ผู้ลงนาม</span>
          <span class="val"><?= e($val('signer') ?: 'ยังไม่ได้ระบุ') ?></span>
          <span class="act">แก้ไข</span>
        </summary>
        <div class="fold-body">
          <div class="field">
            <label for="signer">ผู้ลงนาม</label>
            <input type="text" id="signer" name="signer" value="<?= e($val('signer')) ?>"
                   placeholder="ชื่อผู้ลงนามในหนังสือ">
          </div>
        </div>
      </details>
    <?php endif; ?>
    <div class="fold-end"></div>
  </section>

  <!-- แถบบันทึกติดขอบล่าง เห็นตลอด ไม่ต้องเลื่อนลงไปหา -->
  <div class="form-bar">
    <button type="submit" class="btn btn-primary"><?= $isEdit ? 'บันทึกการแก้ไข' : 'บันทึกและออกเลขทะเบียน' ?></button>
    <?php if (!$isEdit): ?>
      <button type="submit" class="btn btn-ghost" name="save_next" value="1">บันทึกแล้วลงฉบับถัดไป</button>
    <?php endif; ?>
    <a class="btn btn-ghost" href="<?= e($isEdit ? url('/documents/' . $doc['id']) : url('/documents?direction=' . $direction)) ?>">ยกเลิก</a>
    <span class="keys">
      <kbd>Enter</kbd> ช่องถัดไป · <kbd>Ctrl</kbd>+<kbd>Enter</kbd> บันทึก · <kbd>Esc</kbd> ปิดส่วนที่กางอยู่
    </span>
  </div>
</form>

<script type="application/json" id="org-data"><?= json_encode(
    array_map(static fn(array $o): array => ['id' => (int) $o['id'], 'name' => $o['name']], $organizations),
    JSON_UNESCAPED_UNICODE
) ?></script>

<script>
(function () {
  var form = document.querySelector('.doc-form');
  if (!form) return;

  var ORGS = [];
  try { ORGS = JSON.parse(document.getElementById('org-data').textContent); } catch (err) { ORGS = []; }

  // ---------- ช่องหน่วยงาน: ช่องเดียว พิมพ์ค้นหาหรือพิมพ์ชื่อใหม่ก็ได้ ----------
  form.querySelectorAll('[data-combo]').forEach(function (combo) {
    var input  = combo.querySelector('[data-combo-input]');
    var hidden = combo.querySelector('[data-combo-id]');
    var list   = combo.querySelector('[data-combo-list]');
    var cursor = -1;

    function close() { list.hidden = true; cursor = -1; }

    function draw() {
      var term = input.value.trim();
      var hits = ORGS.filter(function (o) { return term === '' || o.name.indexOf(term) !== -1; }).slice(0, 7);
      list.innerHTML = '';
      hits.forEach(function (org, i) {
        var b = document.createElement('button');
        b.type = 'button';
        b.textContent = org.name;
        if (i === cursor) b.className = 'cur';
        b.addEventListener('mousedown', function (ev) {
          ev.preventDefault();
          input.value = org.name;
          hidden.value = org.id;
          close();
        });
        list.appendChild(b);
      });
      list.hidden = hits.length === 0;
    }

    input.addEventListener('input', function () {
      hidden.value = '';            // พิมพ์เองแล้วถือว่ายังไม่ได้ผูกกับหน่วยงานในระบบ
      cursor = -1;
      draw();
    });
    input.addEventListener('focus', draw);
    input.addEventListener('blur', function () { setTimeout(close, 120); });
    input.addEventListener('keydown', function (ev) {
      var items = list.querySelectorAll('button');
      if (ev.key === 'ArrowDown') {
        ev.preventDefault();
        cursor = Math.min(cursor + 1, items.length - 1);
        draw();
      } else if (ev.key === 'ArrowUp') {
        ev.preventDefault();
        cursor = Math.max(cursor - 1, 0);
        draw();
      } else if (ev.key === 'Enter' && cursor >= 0 && !list.hidden) {
        ev.preventDefault();
        var pick = list.querySelectorAll('button')[cursor];
        if (pick) pick.dispatchEvent(new MouseEvent('mousedown'));
      } else if (ev.key === 'Escape') {
        close();
      }
    });
  });

  // ---------- วันที่แบบไทย: พิมพ์ตัวเลขล้วน แล้วอ่านทวนให้เป็นคำ ----------
  var MONTHS = ['มกราคม','กุมภาพันธ์','มีนาคม','เมษายน','พฤษภาคม','มิถุนายน',
                'กรกฎาคม','สิงหาคม','กันยายน','ตุลาคม','พฤศจิกายน','ธันวาคม'];

  form.querySelectorAll('[data-thai-date]').forEach(function (box) {
    var wrap = box.closest('.field');
    var echo = wrap ? wrap.querySelector('[data-date-echo]') : null;

    function speak() {
      if (!echo) return;
      var m = box.value.match(/^(\d{2})\/(\d{2})\/(\d{4})$/);
      if (!m) {
        echo.textContent = 'เป็นปี พ.ศ. — พิมพ์แค่ตัวเลข ระบบใส่ / ให้เอง';
        echo.classList.remove('hint-bad');
        return;
      }
      var d = +m[1], mo = +m[2], y = +m[3];
      var bad = mo < 1 || mo > 12 || d < 1 || d > 31 || y < 2400;
      echo.textContent = bad ? 'วันที่ไม่ถูกต้อง' : (d + ' ' + MONTHS[mo - 1] + ' ' + y);
      echo.classList.toggle('hint-bad', bad);
    }

    box.addEventListener('input', function () {
      var digits = box.value.replace(/\D/g, '').slice(0, 8);
      if (digits.length > 4) {
        box.value = digits.slice(0, 2) + '/' + digits.slice(2, 4) + '/' + digits.slice(4);
      } else if (digits.length > 2) {
        box.value = digits.slice(0, 2) + '/' + digits.slice(2);
      } else {
        box.value = digits;
      }
      speak();
    });

    if (wrap) {
      wrap.querySelectorAll('[data-date-offset]').forEach(function (btn) {
        btn.addEventListener('click', function () {
          var t = new Date();
          t.setDate(t.getDate() + parseInt(btn.dataset.dateOffset, 10));
          var pad = function (n) { return n < 10 ? '0' + n : '' + n; };
          box.value = pad(t.getDate()) + '/' + pad(t.getMonth() + 1) + '/' + (t.getFullYear() + 543);
          speak();
        });
      });
    }
    speak();
  });

  // ---------- ปุ่มเลือกฝ่าย ----------
  form.querySelectorAll('.pick-item input').forEach(function (radio) {
    radio.addEventListener('change', function () {
      form.querySelectorAll('.pick-item').forEach(function (item) {
        item.classList.toggle('on', item.contains(radio) && radio.checked);
      });
    });
  });

  // ---------- สรุปค่าที่พับไว้ให้เห็นบนหัวข้อ ----------
  var summary = form.querySelector('[data-fold-summary]');
  if (summary) {
    var parts = form.querySelectorAll('[data-fold-part]');
    parts.forEach(function (sel) {
      sel.addEventListener('change', function () {
        var out = [];
        parts.forEach(function (s) { out.push(s.value); });
        summary.textContent = out.join(' · ');
      });
    });
  }

  // ---------- คีย์บอร์ด: Enter ไปช่องถัดไป, Ctrl+Enter บันทึก ----------
  var ORDER = ['subject', 'from_text', 'to_text', 'doc_number', 'doc_date'];
  form.addEventListener('keydown', function (ev) {
    if (ev.key === 'Enter' && (ev.ctrlKey || ev.metaKey)) {
      ev.preventDefault();
      form.submit();
      return;
    }
    if (ev.key === 'Escape') {
      form.querySelectorAll('details[open]').forEach(function (d) { d.open = false; });
      return;
    }
    if (ev.key !== 'Enter' || ev.shiftKey) return;
    var id = ev.target.id;
    var at = ORDER.indexOf(id);
    if (at === -1) return;
    var list = ev.target.closest('[data-combo]');
    if (list && !list.querySelector('[data-combo-list]').hidden) return;  // ให้ Enter เลือกจากรายการก่อน
    ev.preventDefault();
    for (var i = at + 1; i < ORDER.length; i++) {
      var next = form.querySelector('#' + ORDER[i]);
      if (next) { next.focus(); return; }
    }
  });

  var first = form.querySelector('#subject');
  if (first && first.value === '') first.focus();
})();
</script>
