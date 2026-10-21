<?php
/**
 * Admin layout header. Before including set: $u (from require_login), $pageTitle, $active.
 */
require_once __DIR__ . '/partials/mascot.php';
$menu = [
    'dash'       => ['Dashboard ผู้ดูแล', 'index.php', []],
    'news'       => ['จัดการเนื้อหา (ข่าว)', 'news.php', ['editor']],
    'posters'    => ['โปสเตอร์ประชาสัมพันธ์', 'posters.php', ['editor']],
    'documents'  =>['เอกสารเกี่ยวกับเรา', 'documents.php', ['editor']],
    'activities' => ['จัดการแผนและกิจกรรม', 'activities.php', ['editor', 'staff']],
    'units'      => ['หน่วยงาน/สาขาวิชา', 'units.php', ['editor', 'staff']],
    'mission_dashboard' => ['แดชบอร์ดภารกิจ', 'mission-dashboard.php', ['editor', 'staff']],
    'stats'      => ['จัดการสถิติ', 'stats.php', ['staff']],
    'criteria'   => ['จัดการข้อกำหนด 7 หมวด', 'criteria.php', ['editor']],
    'reports'    => ['รายงานผล & หลักฐาน 7 หมวด', 'reports.php', ['staff', 'auditor', 'editor']],
    'audits'     => ['ตรวจประเมินและติดตามแก้ไข', 'audits.php', ['auditor']],
    'complaints' => ['ข้อร้องเรียน', 'complaints.php', ['editor']],
    'users'      => ['ผู้ใช้งานและรายงาน', 'users.php', []],
    'settings'   => ['ตั้งค่าเว็บไซต์', 'settings.php', []],
];
$i = 0;
?>
<!doctype html>
<html lang="th">
<head>
<?php require __DIR__ . '/partials/head.php'; ?>
<meta name="robots" content="noindex">
<title><?= e($pageTitle) ?> · ระบบหลังบ้าน Green Office</title>
</head>
<body class="font-sans text-ink antialiased bg-[#F2F4EF]">
<div class="min-h-screen flex flex-wrap">
  <aside class="flex-[1_1_250px] max-w-full lg:max-w-[280px] bg-ku-deep text-mint px-4 py-6 flex flex-col gap-1.5">
    <a href="<?= e(url('admin/')) ?>" class="flex items-center gap-2.5 px-2 pb-4 no-underline">
      <img src="<?= e(url('assets/img/logo.png')) ?>" alt="AGR Green Office" class="w-14 h-14 object-contain bg-white rounded-2xl p-1 flex-none">
      <span class="leading-tight"><span class="block font-display text-[17px] text-white">Admin &amp; Assessment</span><span class="text-xs text-[#A9C9C5]">Green Office Portal</span></span>
    </a>
    <nav aria-label="เมนูระบบหลังบ้าน" class="flex flex-col gap-1">
      <?php foreach ($menu as $key => [$label, $href, $roles]):
        if ($roles !== [] && $u['role'] !== 'admin' && !in_array($u['role'], $roles, true)) continue;
        if ($roles === [] && $key !== 'dash' && $u['role'] !== 'admin') continue;
        $i++; $on = $active === $key; ?>
        <a href="<?= e(url('admin/' . $href)) ?>" class="min-h-[46px] flex items-center gap-3 px-3 py-2 rounded-2xl text-[15px] transition <?= $on ? 'bg-white text-ku-deep' : 'text-mint hover:bg-white/10' ?>"<?= $on ? ' aria-current="page"' : '' ?>>
          <span class="w-7 h-7 rounded-[9px] text-xs font-bold flex items-center justify-center flex-none <?= $on ? 'bg-ku text-white' : 'bg-white/10 text-lime' ?>"><?= sprintf('%02d', $i) ?></span><?= e($label) ?>
        </a>
      <?php endforeach; ?>
    </nav>
    <a href="<?= e(url('index.php')) ?>" class="mt-4 min-h-[46px] flex items-center px-3 rounded-2xl border border-white/20 hover:bg-white/10">← กลับหน้าเว็บไซต์</a>
    <form method="post" action="<?= e(url('admin/logout.php')) ?>"><?= csrf_field() ?><button class="w-full min-h-[46px] text-left px-3 rounded-2xl text-[#FFB4A3] hover:bg-white/10">ออกจากระบบ</button></form>
  </aside>
  <main class="flex-[999_1_560px] min-w-0 px-4 sm:px-8 lg:px-10 py-7 flex flex-col gap-5">
    <div class="flex justify-between items-center gap-3 flex-wrap">
      <div>
        <div class="text-sm text-muted">ระบบหลังบ้าน › <?= e($pageTitle) ?></div>
        <h1 class="font-display font-medium text-3xl"><?= e($pageTitle) ?></h1>
      </div>
      <div class="flex items-center gap-2.5 bg-white rounded-full pl-1.5 pr-4 py-1.5">
        <span class="w-9 h-9 rounded-full bg-sun flex items-center justify-center font-display"><?= e(mb_substr($u['display_name'] ?: $u['username'], 0, 1)) ?></span>
        <span class="text-sm"><?= e($u['display_name'] ?: $u['username']) ?> · <?= e(ROLES[$u['role']] ?? $u['role']) ?></span>
      </div>
    </div>
    <?php foreach (take_flashes() as $f): ?>
      <div role="status" class="anim-pop rounded-2xl px-4 py-3 <?= $f['t'] === 'error' ? 'bg-[#FDE2DD] text-[#7A2A1C]' : 'bg-[#DDF2E6] text-[#1E6B45]' ?>"><?= e($f['m']) ?></div>
    <?php endforeach; ?>
