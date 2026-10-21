<?php
/**
 * Join-mission form for every [data-mission-join] button on the page (wired up in app.js).
 * Expects $units (from units_list()) in scope.
 */
?>
<dialog id="mission-join-dlg" class="mission-dlg" aria-label="เข้าร่วมภารกิจ" data-api="<?= e(url('api/mission-join.php')) ?>">
  <form method="dialog" class="flex flex-col gap-4 p-6 w-full max-w-md" data-mission-form>
    <div class="flex justify-between items-start gap-3">
      <div>
        <span class="eyebrow">เข้าร่วมภารกิจ</span>
        <h2 class="font-display font-medium text-xl mt-1" data-mission-dlg-name></h2>
      </div>
      <button type="button" class="text-muted p-2" data-mission-dlg-close aria-label="ปิด">✕</button>
    </div>
    <label class="label">ชื่อ-สกุล<input type="text" name="name" required maxlength="150" class="input" data-mission-dlg-field="name"></label>
    <label class="label">หน่วยงาน/สาขาวิชา
      <select name="unit_id" required class="input" data-mission-dlg-field="unit_id">
        <option value="">— เลือก —</option>
        <?php foreach ($units as $u): ?>
          <option value="<?= (int)$u['id'] ?>"><?= e($u['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <?php if (!$units): ?>
      <p class="text-sm text-[#A33A2A]">ยังไม่มีรายการหน่วยงาน/สาขาวิชาให้เลือก กรุณาแจ้งแอดมินให้เพิ่มก่อน</p>
    <?php endif; ?>
    <p role="alert" class="text-sm text-[#A33A2A]" data-mission-dlg-error hidden></p>
    <button type="submit" class="btn-primary" data-mission-dlg-submit <?= $units ? '' : 'disabled' ?>>ยืนยันเข้าร่วม</button>
  </form>
</dialog>
