<?php
/** แดชบอร์ดภารกิจ — unit/department leaderboard by mission-join count, for giving out the monthly award. */
require __DIR__ . '/../../app/bootstrap.php';
require_login(['editor', 'staff']);
$pageTitle = 'แดชบอร์ดภารกิจ';
$active = 'mission_dashboard';

$day = fn(string $k, string $default) => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)($_GET[$k] ?? '')) ? $_GET[$k] : $default;
$from = $day('from', date('Y-m-01'));
$to = $day('to', date('Y-m-t'));
if ($from > $to) {
    [$from, $to] = [$to, $from];
}

if (isset($_GET['export'])) {
    $out = fopen('php://output', 'w');
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="green-office-mission-dashboard-' . date('Ymd') . '.csv"');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, ['อันดับ', 'หน่วยงาน/สาขาวิชา', 'จำนวนครั้งที่เข้าร่วม', 'จำนวนภารกิจที่ร่วม', 'ช่วงวันที่', $from . ' ถึง ' . $to]);
    foreach (unit_leaderboard($from, $to) as $i => $r) {
        fputcsv($out, [$i + 1, $r['name'], $r['joins'], $r['missions']]);
    }
    exit;
}

$leaderboard = unit_leaderboard($from, $to);
$today = date('Y-m-d');
$quick = [
    'เดือนนี้' => [date('Y-m-01'), date('Y-m-t')],
    'ปีนี้'    => [date('Y-01-01'), date('Y-12-31')],
    'ทั้งหมด'  => ['2000-01-01', $today],
];
$link = fn(string $f, string $t): string => url('admin/mission-dashboard.php?from=' . $f . '&to=' . $t);

require __DIR__ . '/../../app/views/admin_top.php';
?>
<form method="get" class="card p-4 sm:p-5 flex flex-wrap gap-x-4 gap-y-3 items-end">
  <label class="label">ตั้งแต่วันที่<input type="date" name="from" value="<?= e($from) ?>" class="input"></label>
  <label class="label">ถึงวันที่<input type="date" name="to" value="<?= e($to) ?>" class="input"></label>
  <button class="btn-sm btn-outline min-h-[46px]">กรอง</button>
  <nav aria-label="ช่วงเวลาด่วน" class="flex gap-2 flex-wrap">
    <?php foreach ($quick as $label => [$qf, $qt]): ?>
      <a href="<?= e($link($qf, $qt)) ?>" class="btn-sm btn border border-[#DDE3D9] bg-white <?= $from === $qf && $to === $qt ? 'bg-mint' : '' ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
  </nav>
  <a href="<?= e(url('admin/mission-dashboard.php?from=' . $from . '&to=' . $to . '&export=1')) ?>" class="btn-sm btn-primary ml-auto">ดาวน์โหลด CSV</a>
</form>

<div class="bg-white rounded-3xl p-6 flex flex-col gap-3">
  <h2 class="font-display font-medium text-xl">อันดับหน่วยงาน/สาขาวิชา ตามจำนวนเข้าร่วมภารกิจ</h2>
  <p class="text-muted">ช่วงวันที่ <?= e(thai_date($from)) ?> – <?= e(thai_date($to)) ?></p>
  <?php if (!$leaderboard): ?>
    <p class="text-muted">ยังไม่มีคนเข้าร่วมภารกิจในช่วงเวลานี้</p>
  <?php else: ?>
    <div class="overflow-x-auto">
      <table class="w-full text-left">
        <thead><tr class="text-sm text-muted border-b border-[#EDF0EA]"><th class="py-2 pr-3">อันดับ</th><th class="py-2 pr-3">หน่วยงาน/สาขาวิชา</th><th class="py-2 pr-3">จำนวนครั้งเข้าร่วม</th><th class="py-2 pr-3">จำนวนภารกิจ</th></tr></thead>
        <tbody>
          <?php foreach ($leaderboard as $i => $r): ?>
            <tr class="border-b border-[#F3F5F0] <?= $i === 0 ? 'bg-[#FFF8E1]' : '' ?>">
              <td class="py-2.5 pr-3 font-display"><?= $i === 0 ? '🏆 1' : $i + 1 ?></td>
              <td class="py-2.5 pr-3 font-semibold"><?= e($r['name']) ?></td>
              <td class="py-2.5 pr-3"><?= (int)$r['joins'] ?></td>
              <td class="py-2.5 pr-3"><?= (int)$r['missions'] ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/../../app/views/admin_bottom.php'; ?>
