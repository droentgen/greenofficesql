<?php
/** จัดการสถิติ — monthly fuel, electricity, water and paper. GHG is calculated automatically. */
require __DIR__ . '/../../app/bootstrap.php';
$u = require_login(['staff']);
$pageTitle = 'จัดการสถิติ';
$active = 'stats';

if (is_post()) {
    csrf_check();
    if (!empty($_POST['delete'])) {
        q('DELETE FROM resource_monthly WHERE id = ?', [(int)$_POST['delete']]);
        flash('ลบข้อมูลเดือนนั้นแล้ว');
        redirect('admin/stats.php');
    }
    $y = (int)($_POST['year_be'] ?? 0);
    $m = (int)($_POST['month'] ?? 0);
    $vals = [];
    foreach (['fuel_l', 'elec_kwh', 'water_m3', 'paper_ream'] as $f) {
        $vals[$f] = max(0, (float)str_replace(',', '', (string)($_POST[$f] ?? '0')));
    }
    if ($y < 2500 || $y > 2700 || $m < 1 || $m > 12) {
        flash('กรุณาเลือกเดือนและปี พ.ศ. ให้ถูกต้อง', 'error');
    } else {
        q('INSERT INTO resource_monthly (year_be, month, fuel_l, elec_kwh, water_m3, paper_ream, note, updated_by) VALUES (?,?,?,?,?,?,?,?)
           ON DUPLICATE KEY UPDATE fuel_l = VALUES(fuel_l), elec_kwh = VALUES(elec_kwh), water_m3 = VALUES(water_m3), paper_ream = VALUES(paper_ream), note = VALUES(note), updated_by = VALUES(updated_by)',
            [$y, $m, $vals['fuel_l'], $vals['elec_kwh'], $vals['water_m3'], $vals['paper_ream'], mb_substr(trim((string)($_POST['note'] ?? '')), 0, 255), $u['id']]);
        flash('บันทึกข้อมูล ' . THAI_MONTHS[$m] . ' ' . $y . ' แล้ว — ตัวเลขหน้าแรกอัปเดตทันที');
    }
    redirect('admin/stats.php');
}

$edit = isset($_GET['edit']) ? row('SELECT * FROM resource_monthly WHERE id = ?', [(int)$_GET['edit']]) : null;
$list = rows('SELECT * FROM resource_monthly ORDER BY year_be DESC, month DESC LIMIT 36');
$defY = $edit['year_be'] ?? current_year_be();
$defM = $edit['month'] ?? (int)date('n');

require __DIR__ . '/../../app/views/admin_top.php';
?>
<div class="flex flex-wrap gap-4 items-start">
  <form method="post" class="flex-[2_1_420px] bg-white rounded-3xl p-6 flex flex-col gap-4">
    <?= csrf_field() ?>
    <div class="flex justify-between items-center gap-3 flex-wrap">
      <h2 class="font-display font-medium text-[22px]"><?= $edit ? 'แก้ไขข้อมูลเดือน' : 'กรอกข้อมูลประจำเดือน' ?></h2>
      <div class="flex gap-2">
        <label class="label">เดือน<select name="month" class="input"><?php for ($i = 1; $i <= 12; $i++): ?><option value="<?= $i ?>" <?= $i == $defM ? 'selected' : '' ?>><?= THAI_MONTHS[$i] ?></option><?php endfor; ?></select></label>
        <label class="label">ปี พ.ศ.<input type="number" name="year_be" value="<?= (int)$defY ?>" min="2500" max="2700" class="input w-28"></label>
      </div>
    </div>
    <div class="grid gap-3.5 [grid-template-columns:repeat(auto-fit,minmax(200px,1fr))]">
      <?php foreach ([['fuel_l', '01 น้ำมันเชื้อเพลิง', 'ลิตร'], ['elec_kwh', '03 ไฟฟ้า', 'kWh'], ['water_m3', '04 น้ำ', 'ลบ.ม.'], ['paper_ream', '05 กระดาษ', 'รีม']] as [$f, $label, $unit]): ?>
        <label class="label"><?= e($label) ?>
          <span class="flex items-center border-2 border-[#DDE3D9] rounded-xl bg-[#FAFBF8] focus-within:border-ku">
            <input type="number" step="0.01" min="0" name="<?= $f ?>" value="<?= e($edit[$f] ?? '') ?>" required class="flex-1 min-w-0 min-h-[48px] px-3 bg-transparent text-lg font-medium outline-none">
            <span class="px-3 text-muted font-normal"><?= e($unit) ?></span>
          </span>
        </label>
      <?php endforeach; ?>
    </div>
    <div class="bg-[#E8E4F7] rounded-2xl px-4 py-3.5 flex justify-between items-center gap-3 flex-wrap">
      <span><span class="block font-semibold text-[#4A3D96]">02 ก๊าซเรือนกระจก (คำนวณอัตโนมัติ)</span><span class="text-xs text-muted">น้ำมัน × <?= e(setting('ef_fuel')) ?> + ไฟฟ้า × <?= e(setting('ef_elec')) ?> kgCO₂e · แก้ค่าได้ที่ ตั้งค่า</span></span>
      <span class="font-display text-2xl text-[#4A3D96]"><span data-ghg-out data-ef-fuel="<?= e(setting('ef_fuel', '2.7')) ?>" data-ef-elec="<?= e(setting('ef_elec', '0.4999')) ?>">0.0</span> tCO₂e</span>
    </div>
    <label class="label">หมายเหตุ<input type="text" name="note" value="<?= e($edit['note'] ?? '') ?>" maxlength="255" class="input"></label>
    <div class="flex gap-2.5 flex-wrap">
      <button class="btn-primary font-display font-normal text-lg px-7">บันทึกและอัปเดตหน้าเว็บ</button>
      <?php if ($edit): ?><a href="<?= e(url('admin/stats.php')) ?>" class="btn-outline">ยกเลิกการแก้ไข</a><?php endif; ?>
    </div>
    <p class="text-sm text-muted">กรอกเดือนเดิมซ้ำ = แก้ไขข้อมูลเดือนนั้น</p>
  </form>

  <div class="flex-[2_1_420px] bg-white rounded-3xl p-6 overflow-x-auto">
    <h2 class="font-display font-medium text-xl mb-3">ข้อมูลที่บันทึกไว้</h2>
    <table class="w-full min-w-[560px] text-sm">
      <thead><tr class="text-left text-muted"><th class="py-2">เดือน</th><th class="text-right">น้ำมัน</th><th class="text-right">GHG</th><th class="text-right">ไฟฟ้า</th><th class="text-right">น้ำ</th><th class="text-right">กระดาษ</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($list as $r): ?>
          <tr class="border-t border-[#EDF0EA] hover:bg-[#F4F9F8]">
            <td class="py-2.5"><?= e(month_label($r, true)) ?></td>
            <td class="text-right tabular-nums"><?= num($r['fuel_l']) ?></td>
            <td class="text-right tabular-nums"><?= num(ghg_of($r), 1) ?></td>
            <td class="text-right tabular-nums"><?= num($r['elec_kwh']) ?></td>
            <td class="text-right tabular-nums"><?= num($r['water_m3']) ?></td>
            <td class="text-right tabular-nums"><?= num($r['paper_ream']) ?></td>
            <td class="text-right whitespace-nowrap">
              <a href="<?= e(url('admin/stats.php?edit=' . $r['id'])) ?>" class="btn-sm btn-outline">แก้ไข</a>
              <form method="post" class="inline"><?= csrf_field() ?><button name="delete" value="<?= (int)$r['id'] ?>" data-confirm="ลบข้อมูลเดือนนี้?" class="btn-sm btn-danger">ลบ</button></form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require __DIR__ . '/../../app/views/admin_bottom.php'; ?>
