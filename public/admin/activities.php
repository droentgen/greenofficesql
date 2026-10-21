<?php
/** จัดการแผนและกิจกรรม — plan board + the monthly missions shown on the home page. */
require __DIR__ . '/../../app/bootstrap.php';
$u = require_login(['editor', 'staff']);
$pageTitle = 'จัดการแผนและกิจกรรม';
$active = 'activities';
$cols = ['plan' => ['วางแผน', 'bg-[#EDF0EA]'], 'doing' => ['กำลังดำเนินการ', 'bg-[#FFF2C2]'], 'done' => ['เสร็จสิ้น', 'bg-[#DDF2E6]']];

if (is_post()) {
    csrf_check();
    $a = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    if ($a === 'add_activity' && trim((string)$_POST['title']) !== '') {
        q('INSERT INTO activities (title, category_no, owner, due_date, status) VALUES (?,?,?,?,?)', [
            mb_substr(trim((string)$_POST['title']), 0, 255), max(1, min(7, (int)$_POST['category_no'])),
            mb_substr(trim((string)$_POST['owner']), 0, 150), $_POST['due_date'] ?: null, 'plan',
        ]);
        flash('เพิ่มกิจกรรมแล้ว');
    } elseif ($a === 'move' && array_key_exists($_POST['status'] ?? '', $cols)) {
        q('UPDATE activities SET status = ? WHERE id = ?', [$_POST['status'], $id]);
        if ($_POST['status'] === 'done') {
            q('UPDATE activities SET progress = 100 WHERE id = ?', [$id]);
        }
    } elseif ($a === 'progress') {
        q('UPDATE activities SET progress = ? WHERE id = ?', [max(0, min(100, (int)$_POST['progress'])), $id]);
    } elseif ($a === 'delete_activity') {
        q('DELETE FROM activities WHERE id = ?', [$id]);
        flash('ลบกิจกรรมแล้ว');
    } elseif ($a === 'save_mission') {
        $vals = [mb_substr(trim((string)$_POST['name']), 0, 200), mb_substr(trim((string)$_POST['target_text']), 0, 255), max(0, (int)$_POST['target_count']), preg_match('/^#[0-9a-fA-F]{6}$/', (string)$_POST['color']) ? $_POST['color'] : '#9CCB3B', (int)$_POST['sort'], isset($_POST['is_active']) ? 1 : 0];
        if ($vals[0] !== '') {
            if ($id) {
                q('UPDATE missions SET name = ?, target_text = ?, target_count = ?, color = ?, sort = ?, is_active = ? WHERE id = ?', [...$vals, $id]);
            } else {
                q('INSERT INTO missions (name, target_text, target_count, color, sort, is_active) VALUES (?,?,?,?,?,?)', $vals);
            }
            flash('บันทึกภารกิจแล้ว');
        }
    } elseif ($a === 'delete_mission') {
        q('DELETE FROM missions WHERE id = ?', [$id]);
        flash('ลบภารกิจแล้ว');
    }
    redirect('admin/activities.php');
}

$acts = rows('SELECT * FROM activities ORDER BY due_date IS NULL, due_date, id');
$missions = missions_with_progress(false);
require __DIR__ . '/../../app/views/admin_top.php';
?>
<form method="post" class="bg-white rounded-3xl p-5 flex flex-wrap gap-2.5 items-end">
  <?= csrf_field() ?><input type="hidden" name="action" value="add_activity">
  <label class="label flex-[2_1_240px]">กิจกรรมใหม่<input type="text" name="title" required class="input"></label>
  <label class="label">หมวด<select name="category_no" class="input"><?php for ($i = 1; $i <= 7; $i++): ?><option value="<?= $i ?>">หมวด <?= $i ?></option><?php endfor; ?></select></label>
  <label class="label flex-[1_1_160px]">ผู้รับผิดชอบ<input type="text" name="owner" class="input"></label>
  <label class="label">กำหนดเสร็จ<input type="date" name="due_date" class="input"></label>
  <button class="btn-primary">+ เพิ่ม</button>
</form>

<div class="grid gap-3.5 [grid-template-columns:repeat(auto-fit,minmax(260px,1fr))]">
  <?php foreach ($cols as $st => [$label, $bg]): $items = array_filter($acts, fn($x) => $x['status'] === $st); ?>
    <div class="<?= $bg ?> rounded-3xl p-4 flex flex-col gap-2.5">
      <div class="flex justify-between items-center px-1.5"><span class="font-display text-lg"><?= e($label) ?></span><span class="text-sm bg-white rounded-full px-2.5"><?= count($items) ?></span></div>
      <?php foreach ($items as $it): ?>
        <div class="bg-white rounded-2xl p-3.5 flex flex-col gap-2">
          <span class="chip self-start bg-mint text-ku">หมวด <?= (int)$it['category_no'] ?></span>
          <div class="font-semibold"><?= e($it['title']) ?></div>
          <div class="text-xs text-muted"><?= e($it['owner'] ?: '-') ?> · <?= e($it['due_date'] ? thai_date($it['due_date']) : 'ไม่กำหนดวัน') ?></div>
          <form method="post" class="flex items-center gap-2"><?= csrf_field() ?><input type="hidden" name="action" value="progress"><input type="hidden" name="id" value="<?= (int)$it['id'] ?>">
            <input type="range" name="progress" min="0" max="100" step="5" value="<?= (int)$it['progress'] ?>" aria-label="ความคืบหน้า" class="flex-1 accent-ku" onchange="this.form.submit()"><span class="text-xs w-10 text-right"><?= (int)$it['progress'] ?>%</span>
          </form>
          <div class="flex gap-1.5 flex-wrap">
            <?php foreach ($cols as $to => [$toLabel]): if ($to === $st) continue; ?>
              <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="move"><input type="hidden" name="id" value="<?= (int)$it['id'] ?>"><input type="hidden" name="status" value="<?= $to ?>"><button class="btn-sm btn border border-[#DDE3D9] bg-white text-xs">→ <?= e($toLabel) ?></button></form>
            <?php endforeach; ?>
            <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="delete_activity"><input type="hidden" name="id" value="<?= (int)$it['id'] ?>"><button data-confirm="ลบกิจกรรมนี้?" class="btn-sm btn-danger text-xs">ลบ</button></form>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endforeach; ?>
</div>

<div class="bg-white rounded-3xl p-6 flex flex-col gap-3">
  <h2 class="font-display font-medium text-xl">ภารกิจประจำเดือน (แสดงบนหน้าแรก)</h2>
  <p class="text-muted">% คืบหน้าคำนวณอัตโนมัติจากจำนวนคนที่กดเข้าร่วมบนหน้าเว็บ เทียบกับเป้าหมายที่ตั้งไว้ — ดูสรุปแยกตามหน่วยงานได้ที่ <a href="<?= e(url('admin/mission-dashboard.php')) ?>" class="text-ku underline">แดชบอร์ดภารกิจ</a></p>
  <?php foreach (array_merge($missions, [['id' => 0, 'name' => '', 'target_text' => '', 'target_count' => 0, 'color' => '#9CCB3B', 'sort' => count($missions) + 1, 'is_active' => 1, 'joined' => 0, 'pct' => 0]]) as $m): ?>
    <form method="post" class="flex flex-wrap gap-2 items-end border-t border-[#EDF0EA] pt-3">
      <?= csrf_field() ?><input type="hidden" name="action" value="save_mission"><input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
      <label class="label flex-[2_1_200px]"><?= $m['id'] ? 'ภารกิจ' : 'เพิ่มภารกิจใหม่' ?><input type="text" name="name" value="<?= e($m['name']) ?>" class="input"></label>
      <label class="label flex-[2_1_200px]">เป้าหมาย (ข้อความ)<input type="text" name="target_text" value="<?= e($m['target_text']) ?>" class="input"></label>
      <label class="label w-28">เป้าหมาย (คน)<input type="number" name="target_count" min="0" value="<?= (int)$m['target_count'] ?>" class="input"></label>
      <?php if ($m['id']): ?><span class="text-sm text-muted min-h-[46px] inline-flex items-center">เข้าร่วมแล้ว <?= (int)$m['joined'] ?> คน (<?= $m['pct'] ?>%)</span><?php endif; ?>
      <label class="label w-20">สี<input type="color" name="color" value="<?= e($m['color']) ?>" class="input p-1"></label>
      <label class="label w-20">ลำดับ<input type="number" name="sort" value="<?= (int)$m['sort'] ?>" class="input"></label>
      <label class="flex items-center gap-1.5 text-sm min-h-[46px]"><input type="checkbox" name="is_active" <?= $m['is_active'] ? 'checked' : '' ?> class="w-5 h-5 accent-ku"> แสดง</label>
      <button class="btn-sm btn-outline min-h-[46px]">บันทึก</button>
      <?php if ($m['id']): ?></form><form method="post" class="flex items-end"><?= csrf_field() ?><input type="hidden" name="action" value="delete_mission"><input type="hidden" name="id" value="<?= (int)$m['id'] ?>"><button data-confirm="ลบภารกิจนี้? (รายชื่อคนเข้าร่วมเดิมจะถูกลบไปด้วย)" class="btn-sm btn-danger min-h-[46px]">ลบ</button><?php endif; ?>
    </form>
  <?php endforeach; ?>
</div>
<?php require __DIR__ . '/../../app/views/admin_bottom.php'; ?>
