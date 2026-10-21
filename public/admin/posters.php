<?php
/** โปสเตอร์ประชาสัมพันธ์ — posters shown on the news page (click to enlarge, searchable by name). */
require __DIR__ . '/../../app/bootstrap.php';
$u = require_login(['editor']);
$pageTitle = 'โปสเตอร์ประชาสัมพันธ์';
$active = 'posters';
$imgExt = ['jpg', 'jpeg', 'png', 'webp'];

if (is_post()) {
    csrf_check();
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    try {
        if ($action === 'delete') {
            $p = row('SELECT image_path FROM posters WHERE id = ?', [$id]);
            q('DELETE FROM posters WHERE id = ?', [$id]);
            delete_upload($p['image_path'] ?? null);
            flash('ลบโปสเตอร์แล้ว');
            redirect('admin/posters.php');
        }
        $title = mb_substr(trim((string)($_POST['title'] ?? '')), 0, 255);
        if ($title === '') {
            throw new RuntimeException('กรุณาใส่ชื่อโปสเตอร์');
        }
        $date = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)($_POST['posted_at'] ?? '')) ? $_POST['posted_at'] : null;
        $pub = isset($_POST['is_published']) ? 1 : 0;
        $img = save_upload('image', 'posters', $imgExt);
        if ($action === 'add') {
            if (!$img) {
                throw new RuntimeException('กรุณาเลือกไฟล์รูปโปสเตอร์');
            }
            q('INSERT INTO posters (title, image_path, posted_at, is_published) VALUES (?,?,?,?)', [$title, $img, $date, $pub]);
            flash('เพิ่มโปสเตอร์แล้ว');
        } elseif ($action === 'update') {
            if ($img) {
                delete_upload(val('SELECT image_path FROM posters WHERE id = ?', [$id]) ?: null);
                q('UPDATE posters SET image_path = ? WHERE id = ?', [$img, $id]);
            }
            q('UPDATE posters SET title = ?, posted_at = ?, is_published = ? WHERE id = ?', [$title, $date, $pub, $id]);
            flash('บันทึกโปสเตอร์แล้ว');
        }
    } catch (RuntimeException $ex) {
        flash($ex->getMessage(), 'error');
    }
    redirect('admin/posters.php');
}

$q = trim((string)($_GET['q'] ?? ''));
$posters = $q === ''
    ? rows('SELECT * FROM posters ORDER BY (posted_at IS NULL), posted_at DESC, id DESC')
    : rows("SELECT * FROM posters WHERE title LIKE ? ORDER BY (posted_at IS NULL), posted_at DESC, id DESC", ['%' . addcslashes($q, '%_\\') . '%']);

require __DIR__ . '/../../app/views/admin_top.php';
?>
<div class="flex flex-wrap gap-4 items-start">
  <form method="post" enctype="multipart/form-data" class="flex-[1_1_300px] lg:max-w-[360px] bg-white rounded-3xl p-6 flex flex-col gap-3.5">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="add">
    <h2 class="font-display font-medium text-xl">อัปโหลดโปสเตอร์ใหม่</h2>
    <label class="label">ไฟล์โปสเตอร์ (JPG / PNG / WEBP)
      <input type="file" name="image" accept=".jpg,.jpeg,.png,.webp" required class="input py-2.5" data-preview="#new-poster-preview">
    </label>
    <img id="new-poster-preview" src="" alt="ตัวอย่างโปสเตอร์" hidden class="w-40 aspect-[3/4] object-cover rounded-xl border border-line self-center">
    <label class="label">ชื่อโปสเตอร์<input type="text" name="title" required maxlength="255" class="input" placeholder="ใช้สำหรับค้นหาในหน้าเว็บ"></label>
    <label class="label">วันที่ประชาสัมพันธ์<input type="date" name="posted_at" value="<?= e(date('Y-m-d')) ?>" class="input"></label>
    <label class="flex items-center gap-2 min-h-[44px] text-sm font-semibold"><input type="checkbox" name="is_published" checked class="w-5 h-5 accent-ku"> แสดงบนหน้าเว็บทันที</label>
    <button class="btn-primary">อัปโหลดโปสเตอร์</button>
    <p class="text-xs text-muted">แนะนำรูปแนวตั้ง (สัดส่วนประมาณ 3:4) หน้าเว็บแสดงเป็นรูปย่อ กดแล้วเปิดดูขนาดใหญ่ได้</p>
  </form>

  <div class="flex-[2_1_480px] min-w-0 flex flex-col gap-4">
    <form method="get" class="flex gap-2 flex-wrap">
      <input type="search" name="q" value="<?= e($q) ?>" placeholder="ค้นหาชื่อโปสเตอร์…" aria-label="ค้นหาชื่อโปสเตอร์" class="input flex-1 min-w-[200px] w-auto bg-white">
      <button class="btn-outline">ค้นหา</button>
      <?php if ($q !== ''): ?><a href="<?= e(url('admin/posters.php')) ?>" class="btn bg-white text-muted">ล้าง</a><?php endif; ?>
    </form>
    <?php if (!$posters): ?>
      <div class="bg-white rounded-3xl p-6 text-muted"><?= $q !== '' ? 'ไม่พบโปสเตอร์ที่ชื่อตรงกับคำค้น' : 'ยังไม่มีโปสเตอร์ — เริ่มอัปโหลดได้จากกล่องด้านซ้าย' ?></div>
    <?php endif; ?>
    <div class="grid gap-4 [grid-template-columns:repeat(auto-fill,minmax(230px,1fr))]">
      <?php foreach ($posters as $p): ?>
        <div class="bg-white rounded-3xl p-4 flex flex-col gap-3">
          <div class="relative">
            <a href="<?= e(upload_url($p['image_path'])) ?>" target="_blank" rel="noopener" title="เปิดรูปขนาดเต็ม">
              <img id="poster-img-<?= (int)$p['id'] ?>" src="<?= e(upload_url($p['image_path'])) ?>" alt="" loading="lazy" class="w-full aspect-[3/4] object-cover rounded-2xl bg-cream">
            </a>
            <span class="chip absolute top-2 left-2 <?= $p['is_published'] ? 'bg-[#DDF2E6] text-[#1E6B45]' : 'bg-[#EDF0EA] text-[#3E5553]' ?>"><?= $p['is_published'] ? 'แสดงอยู่' : 'ซ่อน' ?></span>
          </div>
          <form method="post" enctype="multipart/form-data" class="flex flex-col gap-2">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="update"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
            <input type="text" name="title" value="<?= e($p['title']) ?>" required maxlength="255" aria-label="ชื่อโปสเตอร์" class="input">
            <input type="date" name="posted_at" value="<?= e($p['posted_at']) ?>" aria-label="วันที่ประชาสัมพันธ์" class="input">
            <label class="text-xs text-muted flex flex-col gap-1">เปลี่ยนรูป (ไม่บังคับ)<input type="file" name="image" accept=".jpg,.jpeg,.png,.webp" class="text-sm" data-preview="#poster-img-<?= (int)$p['id'] ?>"></label>
            <label class="flex items-center gap-2 min-h-[44px] text-sm"><input type="checkbox" name="is_published" <?= $p['is_published'] ? 'checked' : '' ?> class="w-5 h-5 accent-ku"> แสดงบนหน้าเว็บ</label>
            <button class="btn-sm btn-outline">บันทึก</button>
          </form>
          <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>"><button data-confirm="ลบโปสเตอร์นี้?" class="btn-sm btn-danger w-full">ลบ</button></form>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<?php require __DIR__ . '/../../app/views/admin_bottom.php'; ?>
