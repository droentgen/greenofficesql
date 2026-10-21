<?php
require __DIR__ . '/../app/bootstrap.php';

$pageTitle = 'ข่าวสารและกิจกรรม';
$active = 'news';

$cats = NEWS_CATS;
$cat = in_array($_GET['cat'] ?? '', $cats, true) ? $_GET['cat'] : '';
// Date range filter (published_at); swapped if picked backwards.
$day = fn(string $k) => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)($_GET[$k] ?? '')) && strtotime($_GET[$k]) ? $_GET[$k] : '';
$from = $day('from');
$to = $day('to');
if ($from && $to && $from > $to) {
    [$from, $to] = [$to, $from];
}

$where = ["status = 'published'"];
$params = [];
if ($cat) { $where[] = 'category = ?'; $params[] = $cat; }
if ($from) { $where[] = 'published_at >= ?'; $params[] = $from; }
if ($to) { $where[] = 'published_at <= ?'; $params[] = $to; }
$perPage = 8;
$total = (int)val('SELECT COUNT(*) FROM news WHERE ' . implode(' AND ', $where), $params);
$pages = max(1, (int)ceil($total / $perPage));
$page = min($pages, max(1, (int)($_GET['page'] ?? 1)));
$news = rows('SELECT * FROM news WHERE ' . implode(' AND ', $where) . ' ORDER BY published_at DESC, id DESC LIMIT ' . $perPage . ' OFFSET ' . (($page - 1) * $perPage), $params);

// One row of the newest posters; the rest are on posters.php.
$posters = rows('SELECT id, title, image_path, posted_at FROM posters WHERE is_published = 1 ORDER BY (posted_at IS NULL), posted_at DESC, id DESC LIMIT 6');
$posterTotal = (int)val('SELECT COUNT(*) FROM posters WHERE is_published = 1');

/** Link to this page keeping the current filters, with some of them changed. */
$link = function (array $change) use ($cat, $from, $to): string {
    $q = http_build_query(array_filter(array_merge(['cat' => $cat, 'from' => $from, 'to' => $to], $change)));
    return url('news.php' . ($q ? '?' . $q : ''));
};
$today = date('Y-m-d');
$quick = [
    '7 วันล่าสุด'  => [date('Y-m-d', strtotime('-6 days')), $today],
    '30 วันล่าสุด' => [date('Y-m-d', strtotime('-29 days')), $today],
    'เดือนนี้'     => [date('Y-m-01'), date('Y-m-t')],
    'ปีนี้'        => [date('Y-01-01'), date('Y-12-31')],
];
$calIcon = fn(string $cls) => '<svg class="' . $cls . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4.5" width="18" height="16.5" rx="3"/><path d="M3 9.5h18M8 2.5v4M16 2.5v4"/><circle cx="8" cy="14" r=".6" fill="currentColor"/><circle cx="12" cy="14" r=".6" fill="currentColor"/><circle cx="16" cy="14" r=".6" fill="currentColor"/><circle cx="8" cy="17.5" r=".6" fill="currentColor"/><circle cx="12" cy="17.5" r=".6" fill="currentColor"/></svg>';

require __DIR__ . '/../app/views/public_top.php';
?>
<div class="max-w-7xl mx-auto px-4 sm:px-6 pt-14 pb-16 flex flex-col gap-6">
  <div>
    <span class="eyebrow">ข่าวสารและกิจกรรม</span>
    <h1 class="h-page mt-1.5">มีอะไรกรีน ๆ เกิดขึ้นในคณะบ้าง</h1>
  </div>
  <nav aria-label="ประเภทข่าว" class="flex gap-2 flex-wrap">
    <?php foreach (array_merge([''], $cats) as $c): ?>
      <a href="<?= e($link(['cat' => $c])) ?>" class="btn border-2 border-ku <?= $c === $cat ? 'bg-ku text-white' : 'bg-white text-ku hover:bg-mint' ?>"><?= e($c ?: 'ทั้งหมด') ?></a>
    <?php endforeach; ?>
  </nav>

  <form method="get" action="<?= e(url('news.php')) ?>" class="card p-4 sm:p-5 flex flex-wrap gap-x-4 gap-y-3 items-end" aria-label="กรองข่าวตามช่วงวันที่">
    <?php if ($cat): ?><input type="hidden" name="cat" value="<?= e($cat) ?>"><?php endif; ?>
    <div class="flex items-center gap-3 flex-[1_1_220px]">
      <span class="w-12 h-12 rounded-2xl bg-mint text-ku flex items-center justify-center flex-none anim-bob"><?= $calIcon('w-7 h-7') ?></span>
      <span class="leading-tight"><span class="block font-display font-medium text-lg">เลือกช่วงวันที่</span><span class="text-sm text-muted">กรองข่าวตามวันที่เผยแพร่</span></span>
    </div>
    <label class="label flex-[1_1_170px]">ตั้งแต่วันที่
      <span class="relative block"><span class="absolute left-3 top-1/2 -translate-y-1/2 text-ku pointer-events-none"><?= $calIcon('w-5 h-5') ?></span><input type="date" name="from" value="<?= e($from) ?>" max="<?= e($to ?: '') ?>" class="input pl-11 cursor-pointer" data-date-picker></span>
    </label>
    <label class="label flex-[1_1_170px]">ถึงวันที่
      <span class="relative block"><span class="absolute left-3 top-1/2 -translate-y-1/2 text-ku pointer-events-none"><?= $calIcon('w-5 h-5') ?></span><input type="date" name="to" value="<?= e($to) ?>" min="<?= e($from ?: '') ?>" class="input pl-11 cursor-pointer" data-date-picker></span>
    </label>
    <div class="flex gap-2">
      <button class="btn-primary">กรองข่าว</button>
      <?php if ($from || $to): ?><a href="<?= e($link(['from' => '', 'to' => ''])) ?>" class="btn-outline">ล้าง</a><?php endif; ?>
    </div>
    <div class="basis-full flex gap-2 flex-wrap items-center">
      <span class="text-sm text-muted">เลือกเร็ว:</span>
      <?php foreach ($quick as $label => [$qf, $qt]): $on = $from === $qf && $to === $qt; ?>
        <a href="<?= e($link(['from' => $qf, 'to' => $qt])) ?>" class="chip min-h-[36px] inline-flex items-center px-3.5 text-sm transition <?= $on ? 'bg-ku text-white' : 'bg-mint text-ku hover:bg-lime' ?>"<?= $on ? ' aria-current="true"' : '' ?>><?= e($label) ?></a>
      <?php endforeach; ?>
    </div>
  </form>

  <div id="news-list" class="scroll-mt-28 flex flex-col gap-6">
    <?php if ($from || $to): ?>
      <p class="text-muted -mt-2" role="status">
        พบ <strong class="text-ink"><?= $total ?></strong> ข่าว
        · <?= $from ? e(thai_date($from)) : 'ตั้งแต่แรก' ?> – <?= $to ? e(thai_date($to)) : 'ปัจจุบัน' ?>
      </p>
    <?php endif; ?>
    <?php include __DIR__ . '/../app/views/partials/news_cards.php'; ?>
    <?php $pageUrl = fn(int $n) => $link(['page' => $n > 1 ? $n : '']) . '#news-list'; include __DIR__ . '/../app/views/partials/pagination.php'; ?>
  </div>

  <section id="posters" aria-labelledby="posters-h" class="mt-10 flex flex-col gap-5">
    <div class="flex justify-between items-end gap-4 flex-wrap">
      <div>
        <span class="eyebrow">ประชาสัมพันธ์</span>
        <h2 id="posters-h" class="font-display font-semibold text-[clamp(26px,3.5vw,38px)] leading-tight mt-1">โปสเตอร์ประชาสัมพันธ์</h2>
        <p class="text-muted mt-1">กดที่โปสเตอร์เพื่อดูขนาดใหญ่</p>
      </div>
      <?php if ($posters): ?>
        <form method="get" action="<?= e(url('posters.php')) ?>" role="search" class="relative flex-[0_1_360px] w-full">
          <label for="poster-q" class="sr-only">ค้นหาโปสเตอร์ตามชื่อ</label>
          <svg class="w-5 h-5 absolute left-4 top-1/2 -translate-y-1/2 text-ku pointer-events-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
          <input id="poster-q" type="search" name="q" placeholder="ค้นหาชื่อโปสเตอร์ทั้งหมด…" class="input rounded-full pl-12 pr-24 bg-white">
          <button class="absolute right-1.5 top-1/2 -translate-y-1/2 min-h-[38px] px-4 rounded-full bg-ku text-white text-sm font-semibold">ค้นหา</button>
        </form>
      <?php endif; ?>
    </div>

    <div class="poster-board rounded-[32px] px-4 py-8 sm:p-9 flex flex-col gap-7">
      <?php if (!$posters): ?>
        <p class="text-center text-muted py-6">ยังไม่มีโปสเตอร์ประชาสัมพันธ์</p>
      <?php else: ?>
        <ul class="grid gap-x-5 gap-y-9 grid-cols-2 sm:grid-cols-3 lg:grid-cols-6">
          <?php foreach ($posters as $p) { include __DIR__ . '/../app/views/partials/poster_card.php'; } ?>
        </ul>
        <div class="flex justify-center">
          <a href="<?= e(url('posters.php')) ?>" class="btn-primary group">
            ดูโปสเตอร์ทั้งหมด<?= $posterTotal > count($posters) ? ' (' . $posterTotal . ')' : '' ?>
            <span class="transition group-hover:translate-x-1" aria-hidden="true">→</span>
          </a>
        </div>
      <?php endif; ?>
    </div>
  </section>
</div>
<?php include __DIR__ . '/../app/views/partials/poster_lightbox.php'; ?>
<?php require __DIR__ . '/../app/views/public_bottom.php'; ?>
