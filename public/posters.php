<?php
/** โปสเตอร์ประชาสัมพันธ์ทั้งหมด — every published poster, grouped by year (พ.ศ.), live search by name (?q= prefills it). */
require __DIR__ . '/../app/bootstrap.php';

$pageTitle = 'โปสเตอร์ประชาสัมพันธ์ทั้งหมด';
$active = 'news';
$q = trim((string)($_GET['q'] ?? ''));

$posters = rows('SELECT id, title, image_path, posted_at FROM posters WHERE is_published = 1 ORDER BY (posted_at IS NULL), posted_at DESC, id DESC');
$groups = [];
foreach ($posters as $p) {
    $year = $p['posted_at'] ? (string)((int)date('Y', strtotime($p['posted_at'])) + 543) : 'ไม่ระบุปี';
    $groups[$year][] = $p;
}

require __DIR__ . '/../app/views/public_top.php';
?>
<div class="max-w-7xl mx-auto px-4 sm:px-6 pt-10 pb-16 flex flex-col gap-6">
  <a href="<?= e(url('news.php#posters')) ?>" class="self-start min-h-[44px] inline-flex items-center font-semibold text-ku">← ข่าวสารและกิจกรรม</a>

  <div class="relative overflow-hidden rounded-[32px] bg-ku text-white px-6 py-9 sm:px-10 flex flex-wrap gap-6 items-end justify-between">
    <div class="absolute -right-10 -top-14 w-56 h-56 bg-lime/20 anim-blob" aria-hidden="true"></div>
    <div class="absolute right-24 -bottom-16 w-40 h-40 bg-sun/20 anim-blob" aria-hidden="true"></div>
    <div class="relative">
      <span class="text-sm font-semibold text-lime tracking-wide">ประชาสัมพันธ์</span>
      <h1 class="font-display font-semibold text-[clamp(30px,4.5vw,48px)] leading-tight mt-1">โปสเตอร์ประชาสัมพันธ์ทั้งหมด</h1>
      <p class="text-mint/90 mt-1">รวมโปสเตอร์กิจกรรม ข่าวสาร และการอบรมของ Green Office · กดที่โปสเตอร์เพื่อดูขนาดใหญ่</p>
    </div>
    <div class="relative flex gap-3">
      <span class="rounded-2xl bg-white/10 px-5 py-3 text-center"><span class="block font-display text-3xl" data-count="<?= count($posters) ?>"><?= count($posters) ?></span><span class="text-xs text-mint">โปสเตอร์</span></span>
      <span class="rounded-2xl bg-white/10 px-5 py-3 text-center"><span class="block font-display text-3xl"><?= count($groups) ?></span><span class="text-xs text-mint">ปี</span></span>
    </div>
  </div>

  <?php if (!$posters): ?>
    <div class="poster-board rounded-[32px] p-10 text-center text-muted">ยังไม่มีโปสเตอร์ประชาสัมพันธ์</div>
  <?php else: ?>
    <div class="sticky top-[76px] z-10 -mx-1 px-1 py-2 bg-cream/90 backdrop-blur flex flex-wrap gap-3 items-center justify-between">
      <label class="relative flex-[0_1_420px] w-full">
        <span class="sr-only">ค้นหาโปสเตอร์ตามชื่อ</span>
        <svg class="w-5 h-5 absolute left-4 top-1/2 -translate-y-1/2 text-ku pointer-events-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
        <input type="search" value="<?= e($q) ?>" placeholder="พิมพ์ชื่อโปสเตอร์เพื่อค้นหา…" class="input rounded-full pl-12 bg-white" data-poster-search>
      </label>
      <?php if (count($groups) > 1): ?>
        <nav aria-label="ไปที่ปี" class="flex gap-1.5 flex-wrap items-center">
          <?php foreach (array_keys($groups) as $year): ?>
            <a href="#y<?= e($year) ?>" class="chip min-h-[36px] inline-flex items-center px-3.5 text-sm bg-mint text-ku hover:bg-lime transition" data-year-link="<?= e($year) ?>"><?= is_numeric($year) ? 'ปี ' : '' ?><?= e($year) ?></a>
          <?php endforeach; ?>
        </nav>
      <?php endif; ?>
      <p class="text-sm text-muted" role="status">แสดง <strong class="text-ink" data-poster-shown><?= count($posters) ?></strong> จาก <?= count($posters) ?> โปสเตอร์</p>
    </div>

    <div class="poster-board rounded-[32px] px-4 py-8 sm:p-9 flex flex-col gap-8">
      <?php foreach ($groups as $year => $items): ?>
        <section id="y<?= e($year) ?>" data-poster-group="<?= e($year) ?>" class="scroll-mt-44 flex flex-col gap-6">
          <h2 class="self-start inline-flex items-center gap-2 bg-white/80 rounded-full pl-2 pr-4 py-1.5 font-display font-medium text-ku shadow-sm">
            <span class="w-8 h-8 rounded-full bg-ku text-white flex items-center justify-center"><svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><rect x="3" y="4.5" width="18" height="16.5" rx="3"/><path d="M3 9.5h18M8 2.5v4M16 2.5v4"/></svg></span>
            <?= is_numeric($year) ? 'ปี พ.ศ. ' : '' ?><?= e($year) ?> <span class="text-sm text-muted font-sans">· <span data-group-count><?= count($items) ?></span> โปสเตอร์</span>
          </h2>
          <ul class="grid gap-x-5 gap-y-9 grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6">
            <?php foreach ($items as $p) { include __DIR__ . '/../app/views/partials/poster_card.php'; } ?>
          </ul>
        </section>
      <?php endforeach; ?>
      <div class="text-center py-6" hidden data-poster-empty>
        <div class="text-4xl mb-2" aria-hidden="true">🔍</div>
        <p class="text-muted">ไม่พบโปสเตอร์ที่ชื่อตรงกับคำค้น ลองใช้คำอื่นดูนะ</p>
      </div>
    </div>
  <?php endif; ?>
</div>
<?php include __DIR__ . '/../app/views/partials/poster_lightbox.php'; ?>
<?php require __DIR__ . '/../app/views/public_bottom.php'; ?>
