<?php
/** หน่วยงาน/สาขาวิชา — the list people pick from when joining a mission. */
require __DIR__ . '/../../app/bootstrap.php';
require_login(['editor', 'staff']);
$pageTitle = 'หน่วยงาน/สาขาวิชา';
$active = 'units';

if (is_post()) {
    csrf_check();
    $a = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    if ($a === 'save') {
        $name = mb_substr(trim((string)($_POST['name'] ?? '')), 0, 150);
        $vals = [$name, (int)($_POST['sort'] ?? 0), isset($_POST['is_active']) ? 1 : 0];
        if ($name !== '') {
            if ($id) {
                q('UPDATE units SET name = ?, sort = ?, is_active = ? WHERE id = ?', [...$vals, $id]);
            } else {
                q('INSERT INTO units (name, sort, is_active) VALUES (?,?,?)', $vals);
            }
            flash('บันทึกแล้ว');
        }
    } elseif ($a === 'delete') {
        try {
            q('DELETE FROM units WHERE id = ?', [$id]);
            flash('ลบแล้ว');
        } catch (PDOException $ex) {
            flash('ลบไม่ได้ เพราะมีคนเข้าร่วมภารกิจในนามหน่วยงานนี้แล้ว — เอาเครื่องหมาย "แสดงในฟอร์ม" ออกแทนได้', 'error');
        }
    }
    redirect('admin/units.php');
}

$units = rows('SELECT u.*, (SELECT COUNT(*) FROM mission_participants mp WHERE mp.unit_id = u.id) AS joins FROM units u ORDER BY u.sort, u.id');
require __DIR__ . '/../../app/views/admin_top.php';
?>
<div class="bg-white rounded-3xl p-6 flex flex-col gap-3">
  <p class="text-muted">รายการนี้คือตัวเลือก "หน่วยงาน/สาขาวิชา" ที่คนเลือกตอนกดเข้าร่วมภารกิจในหน้าเว็บ</p>
  <?php foreach (array_merge($units, [['id' => 0, 'name' => '', 'sort' => count($units) + 1, 'is_active' => 1, 'joins' => 0]]) as $un): ?>
    <form method="post" class="flex flex-wrap gap-2 items-end border-t border-[#EDF0EA] pt-3">
      <?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= (int)$un['id'] ?>">
      <label class="label flex-[2_1_220px]"><?= $un['id'] ? 'ชื่อหน่วยงาน/สาขาวิชา' : 'เพิ่มใหม่' ?><input type="text" name="name" value="<?= e($un['name']) ?>" class="input"></label>
      <label class="label w-20">ลำดับ<input type="number" name="sort" value="<?= (int)$un['sort'] ?>" class="input"></label>
      <label class="flex items-center gap-1.5 text-sm min-h-[46px]"><input type="checkbox" name="is_active" <?= $un['is_active'] ? 'checked' : '' ?> class="w-5 h-5 accent-ku"> แสดงในฟอร์ม</label>
      <?php if ($un['id']): ?><span class="text-sm text-muted min-h-[46px] inline-flex items-center">เข้าร่วมแล้ว <?= (int)$un['joins'] ?> ครั้ง</span><?php endif; ?>
      <button class="btn-sm btn-outline min-h-[46px]">บันทึก</button>
      <?php if ($un['id']): ?></form><form method="post" class="flex items-end"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$un['id'] ?>"><button data-confirm="ลบหน่วยงานนี้? (ประวัติการเข้าร่วมเดิมจะยังอยู่)" class="btn-sm btn-danger min-h-[46px]">ลบ</button><?php endif; ?>
    </form>
  <?php endforeach; ?>
</div>
<?php require __DIR__ . '/../../app/views/admin_bottom.php'; ?>
