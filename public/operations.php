<?php
require __DIR__ . '/../app/bootstrap.php';

$pageTitle = 'การดำเนินงาน';
$active = 'ops';

$years = report_years();
$year = in_array((int)($_GET['year'] ?? 0), $years, true) ? (int)$_GET['year'] : $years[0];
$cats = categories();
$progress = category_progress($year);
$missions = missions_with_progress();
$units = units_list();
$steps = [['1', 'วางแผน', 'กำหนดนโยบาย/เป้าหมาย', 'bg-ku text-white'], ['2', 'ลงมือทำ', 'ภารกิจและกิจกรรม', 'bg-ku-mid text-white'], ['3', 'ตรวจติดตาม', 'เก็บข้อมูล-หลักฐาน', 'bg-[#CFE8E4]'], ['4', 'ปรับปรุง', 'แก้ไขตามข้อสังเกต', 'bg-[#EEF6E6]']];

require __DIR__ . '/../app/views/public_top.php';
?>
<div class="max-w-7xl mx-auto px-4 sm:px-6 pt-14 pb-16 flex flex-col gap-9">
  <div>
    <span class="eyebrow">การดำเนินงาน</span>
    <h1 class="h-page mt-1.5 mb-2.5">7 หมวด 1 เป้าหมาย: สำนักงานสีเขียว</h1>
    <p class="text-lg text-[#3E5553] max-w-3xl">คลิกแต่ละหมวดเพื่อดูตารางผลการดำเนินงานรายตัวชี้วัด พร้อมลิงก์เอกสารหลักฐาน — เลือกดูย้อนหลังได้ตามปี</p>
  </div>
  <?php $yearLinkBase = 'operations.php'; include __DIR__ . '/../app/views/partials/year_filter.php'; ?>
  <div class="flex gap-2 flex-wrap">
    <?php foreach ($steps as [$n, $t, $d, $cls]): ?>
      <div class="flex-[1_1_200px] flex items-center gap-3 rounded-[18px] px-4 py-3.5 <?= $cls ?>">
        <span class="w-9 h-9 rounded-full bg-white/85 text-ku flex items-center justify-center font-display flex-none"><?= $n ?></span>
        <span><span class="block font-semibold"><?= e($t) ?></span><span class="text-sm opacity-85"><?= e($d) ?></span></span>
      </div>
    <?php endforeach; ?>
  </div>
  <div class="grid gap-4 [grid-template-columns:repeat(auto-fit,minmax(280px,1fr))]">
    <?php foreach ($cats as $c): $p = $progress[$c['id']] ?? ['total' => 0, 'done' => 0]; $pct = $p['total'] ? round($p['done'] / $p['total'] * 100) : 0; ?>
      <a href="<?= e(url('category.php?n=' . $c['id'] . '&year=' . $year)) ?>" class="hover-lift card p-6 flex flex-col gap-3.5 text-ink no-underline">
        <span class="flex items-center gap-3.5">
          <span class="ico-bg w-[60px] h-[60px] rounded-[18px] flex flex-col items-center justify-center font-display leading-none flex-none" style="background:<?= e($c['color_bg']) ?>;color:<?= e($c['color_fg']) ?>"><span class="text-[11px]">หมวด</span><span class="text-[26px]"><?= (int)$c['id'] ?></span></span>
          <span class="text-[17px] font-semibold leading-snug"><?= e($c['name']) ?></span>
        </span>
        <span class="text-sm text-muted"><?= e($c['description']) ?></span>
        <span class="block h-2.5 rounded-full bg-[#EDF0EA] overflow-hidden"><span class="grow-bar block h-full bg-ku rounded-full" data-w="<?= $pct ?>"></span></span>
        <span class="flex justify-between gap-2 text-sm text-muted"><span>รายงานแล้ว <?= $p['done'] ?>/<?= $p['total'] ?> ตัวชี้วัด</span><span class="font-semibold text-ku">ดูตาราง →</span></span>
      </a>
    <?php endforeach; ?>
  </div>
  <div class="bg-[#EEF6E6] rounded-[30px] p-7">
    <h2 class="font-display font-medium text-[26px] mb-4">ภารกิจที่เปิดให้ร่วม</h2>
    <div class="grid gap-3 [grid-template-columns:repeat(auto-fit,minmax(220px,1fr))]">
      <?php foreach ($missions as $m): $joined = mission_already_joined((int)$m['id']); ?>
        <div class="hover-lift bg-white rounded-[20px] p-4 flex flex-col gap-2.5" data-mission-card="<?= (int)$m['id'] ?>">
          <div class="font-semibold"><?= e($m['name']) ?></div>
          <div class="h-2.5 rounded-full bg-[#EDF0EA] overflow-hidden"><div class="grow-bar h-full rounded-full" data-w="<?= $m['pct'] ?>" data-mission-bar style="background:<?= e($m['color']) ?>"></div></div>
          <div class="text-sm text-muted"><?= e($m['target_text']) ?> · <span data-mission-pct><?= $m['pct'] ?></span>% · <span data-mission-joined><?= $m['joined'] ?></span>/<?= (int)$m['target_count'] ?> คนเข้าร่วม</div>
          <button type="button" class="btn-sm btn-outline self-start" data-mission-join="<?= (int)$m['id'] ?>" data-mission-name="<?= e($m['name']) ?>" <?= $joined ? 'disabled' : '' ?>><?= $joined ? 'เข้าร่วมแล้ว' : 'เข้าร่วม' ?></button>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../app/views/partials/mission_join_dialog.php'; ?>
<?php require __DIR__ . '/../app/views/public_bottom.php'; ?>
