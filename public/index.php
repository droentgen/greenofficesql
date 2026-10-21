<?php
require __DIR__ . '/../app/bootstrap.php';

$pageTitle = 'หน้าแรก';
$active = 'home';

[$cur, $prev] = latest_two_months();
$meta = resource_meta();
$game = game_stats();
$missions = missions_with_progress();
$units = units_list();
$news = rows("SELECT * FROM news WHERE status = 'published' ORDER BY published_at DESC, id DESC LIMIT 4");
$evidenceCount = (int)val('SELECT COUNT(*) FROM criteria_evidence') + (int)val('SELECT COUNT(*) FROM documents WHERE is_published = 1');
$ghgCut = ($cur && $prev) ? max(0, ghg_of($prev) - ghg_of($cur)) : 0;
$elecBadge = ($cur && $prev) ? change_badge((float)$cur['elec_kwh'], (float)$prev['elec_kwh'])[0] : '—';

$questions = [
    ['topic' => 'ประหยัดไฟ', 'name' => GAME_TOPICS['a_elec'], 'q' => 'ออกจากห้องปุ๊บ ปิดไฟ-แอร์ทุกครั้งไหม?'],
    ['topic' => 'ลดพลาสติก', 'name' => GAME_TOPICS['a_plastic'], 'q' => 'พกแก้วหรือขวดน้ำส่วนตัวมาคณะบ้างไหม?'],
    ['topic' => 'ลดกระดาษ', 'name' => GAME_TOPICS['a_paper'], 'q' => 'ส่งงานออนไลน์ หรือปริ้นต์สองหน้าเป็นนิสัยหรือยัง?'],
    ['topic' => 'เดินทางรักษ์โลก', 'name' => GAME_TOPICS['a_commute'], 'q' => 'มาคณะด้วยการเดิน ปั่น หรือรถสาธารณะบ้างไหม?'],
];

require __DIR__ . '/../app/views/public_top.php';
?>

<!-- ===== Intro game overlay (shows on first visit; replay with the button or #play) ===== -->
<div id="checkin" hidden class="fixed inset-0 z-50 overflow-y-auto bg-ku text-white" role="dialog" aria-modal="true" aria-label="เกม Green Check-in"
     data-questions='<?= e(json_encode($questions, JSON_UNESCAPED_UNICODE)) ?>' data-api="<?= e(url('api/game.php')) ?>">
  <div aria-hidden="true" class="anim-blob absolute w-[560px] h-[560px] -left-44 -top-40 bg-ku-mid"></div>
  <div aria-hidden="true" class="anim-blob absolute w-[420px] h-[420px] -right-28 -bottom-36 bg-[#004F4D]" style="animation-delay:-4s"></div>
  <img src="<?= e(url('assets/img/logo.png')) ?>" alt="AGR Green Office" class="absolute left-5 top-5 w-24 h-24 object-contain bg-white rounded-3xl p-1.5">
  <div class="relative min-h-full flex items-center justify-center px-5 py-28">
    <div class="w-full max-w-5xl flex flex-wrap gap-10 items-center justify-center">
      <div class="flex-[1_1_280px] max-w-[340px] flex flex-col items-center gap-4 text-center">
        <div class="anim-bob"><?php mascot('wave', 'w-60 h-64'); ?></div>
        <div class="font-display text-[15px] bg-white/10 px-4 py-2 rounded-full">สวัสดี! เราชื่อ “น้องกล้า” ผู้ช่วยสายกรีนของคณะ</div>
      </div>
      <div class="flex-[2_1_420px] max-w-xl">
        <div data-q class="flex flex-col gap-5">
          <div class="flex items-center justify-between gap-3 flex-wrap">
            <span class="font-display text-sm tracking-widest text-lime">GREEN CHECK-IN · ด่าน <span data-q-num>1</span>/4</span>
            <button type="button" data-close class="underline text-[#CFE8E4] p-2">ข้ามไปหน้าเว็บ</button>
          </div>
          <div class="flex gap-2"><?php for ($i = 0; $i < 4; $i++): ?><div data-dot class="flex-1 h-2 rounded-full bg-white/20 transition"></div><?php endfor; ?></div>
          <div data-card class="bg-black/20 border border-white/15 rounded-[28px] p-7 flex flex-col gap-5">
            <div class="flex items-center gap-4">
              <div class="w-16 h-16 rounded-[20px] bg-lime text-ku-deep flex items-center justify-center flex-none">
                <svg data-q-icon="0" aria-hidden="true" class="w-9 h-9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18h6M10 21h4"/><path d="M12 3a6 6 0 0 0-3.5 10.9c.6.5 1 1.2 1 2V16h5v-.1c0-.8.4-1.5 1-2A6 6 0 0 0 12 3z"/></svg>
                <svg data-q-icon="1" hidden aria-hidden="true" class="w-9 h-9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M10 2h4v3l1.5 2.5V20a2 2 0 0 1-2 2h-3a2 2 0 0 1-2-2V7.5L10 5z"/><path d="M8.5 11h7"/></svg>
                <svg data-q-icon="2" hidden aria-hidden="true" class="w-9 h-9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5M9 13h6M9 17h4"/></svg>
                <svg data-q-icon="3" hidden aria-hidden="true" class="w-9 h-9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="6" cy="17" r="3.5"/><circle cx="18" cy="17" r="3.5"/><path d="M6 17l4-8h5l3 8M10 9 8.5 6H6.5M14 6h2.5"/></svg>
              </div>
              <div>
                <div class="text-sm text-[#CFE8E4]">ภารกิจ: <span data-q-topic></span></div>
                <h1 class="font-display font-medium text-[28px] leading-tight" data-q-text></h1>
              </div>
            </div>
            <div class="flex flex-col gap-2.5">
              <?php foreach ([[2, 'ทำเป็นประจำ สบายมาก', '+2 ใบไม้'], [1, 'บางครั้ง ถ้าไม่ลืม', '+1 ใบไม้'], [0, 'ยังไม่เคย แต่พร้อมเริ่ม', 'เริ่มวันนี้!']] as [$v, $label, $pts]): ?>
                <button type="button" data-answer="<?= $v ?>" class="ans flex items-center justify-between gap-3 min-h-[56px] px-5 py-3 rounded-2xl border-2 border-white/25 bg-white/5 text-left text-[17px]">
                  <span><?= e($label) ?></span><span class="text-sm text-lime whitespace-nowrap"><?= e($pts) ?></span>
                </button>
              <?php endforeach; ?>
            </div>
          </div>
          <p class="text-sm text-[#CFE8E4]">คำตอบของคุณช่วยให้คณะรู้ว่าเราควรชวนกันทำเรื่องไหนเพิ่ม — ไม่มีถูกผิด ตอบตามจริงได้เลย</p>
        </div>
        <div data-result hidden class="anim-pop flex flex-col gap-5">
          <span class="font-display text-sm tracking-widest text-lime">ผลเช็กอินของคุณ</span>
          <div class="font-display text-[44px] font-semibold leading-tight" data-level></div>
          <p class="text-lg text-mint" data-level-desc></p>
          <div class="flex items-baseline gap-2.5"><span class="font-display text-[56px] text-sun leading-none" data-score>0</span><span class="text-[#CFE8E4]">/ 8 ใบไม้</span></div>
          <div class="grid grid-cols-2 gap-2.5" data-topics></div>
          <div class="flex gap-3 flex-wrap">
            <button type="button" data-close class="btn-sun text-lg font-display font-normal">เข้าสู่เว็บไซต์ →</button>
            <button type="button" data-play class="btn border-2 border-white/40 text-white">เล่นใหม่</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ===== Hero ===== -->
<section class="relative overflow-hidden" data-parallax>
  <span aria-hidden="true" class="anim-drift absolute top-[12%] left-0 text-leaf"><?= leaf_icon('w-7 h-7') ?></span>
  <span aria-hidden="true" class="anim-drift absolute top-[70%] left-0 text-ku/50" style="animation-delay:-11s"><?= leaf_icon('w-6 h-6') ?></span>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 pt-14 pb-16 flex flex-wrap gap-10 items-center">
    <div class="flex-[1_1_460px] flex flex-col gap-5">
      <span class="self-start inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-mint text-ku text-sm font-semibold"><span class="w-2 h-2 rounded-full bg-leaf"></span><?= e(setting('hero_tag', '#เกษตรไม่ทิ้งโลก')) ?></span>
      <h1 class="font-display font-semibold text-[clamp(42px,6vw,76px)] leading-[1.08]">ปลูกพืชเก่งแล้ว<br><span class="text-ku">ปีนี้มาปลูก</span> <span class="relative inline-block text-ku">“นิสัยกรีน”<svg aria-hidden="true" viewBox="0 0 300 20" preserveAspectRatio="none" class="absolute left-0 -bottom-2.5 w-full h-4"><path d="M4 14 Q80 2 150 10 T296 8" stroke="#FFC845" stroke-width="7" fill="none" stroke-linecap="round"/></svg></span></h1>
      <p class="text-[19px] text-[#3E5553] max-w-xl">กรีนแบบเรา ๆ ไม่ต้องเพอร์เฟกต์ แค่เริ่มก็พอ — ทุกการปิดไฟ ทุกแก้วที่พกมา ถูกนับเป็นตัวเลขจริงบนหน้านี้</p>
      <div class="flex gap-3 flex-wrap">
        <a href="<?= e(url('operations.php')) ?>" class="btn-primary text-lg font-display font-normal px-7 min-h-[54px]">ร่วมภารกิจเลย →</a>
        <button type="button" data-play class="btn border-2 border-ink min-h-[54px]">เช็กเลเวลกรีนของฉัน</button>
      </div>
      <div class="flex gap-7 flex-wrap pt-2">
        <div><div class="font-display text-[34px] text-ku leading-none"><span data-count="<?= $game['players'] ?>" data-part="players">0</span></div><div class="text-sm text-muted">คนร่วมเช็กอินแล้ว</div></div>
        <div><div class="font-display text-[34px] text-ku leading-none"><span data-count="<?= round($ghgCut, 1) ?>" data-dec="1">0</span></div><div class="text-sm text-muted">tCO₂e ที่ลดได้จากเดือนก่อน</div></div>
      </div>
    </div>
    <div class="flex-[1_1_420px] relative h-[480px] min-w-[300px]">
      <div aria-hidden="true" data-depth="-18" class="absolute inset-0 flex items-center justify-center transition-transform duration-200"><div class="anim-blob w-[400px] h-[400px] max-w-full bg-ku"></div></div>
      <div aria-hidden="true" data-depth="26" class="absolute right-6 top-2 transition-transform duration-200"><div class="anim-spin w-24 h-24 rounded-full bg-sun shadow-[0_0_0_14px_rgba(255,200,69,.25)]"></div></div>
      <div class="absolute inset-0 flex items-center justify-center"><div class="anim-bob"><?php mascot('wave', 'w-[260px] h-[282px]', 'น้องกล้า โบกมือทักทาย'); ?></div></div>
      <div data-depth="14" class="absolute left-0 top-16 transition-transform duration-200">
        <div class="anim-float bg-white rounded-[18px] px-4 py-3 shadow-[0_16px_30px_-18px_rgba(0,61,60,.6)] flex items-center gap-2.5">
          <span class="w-9 h-9 rounded-xl bg-[#FFF2C2] text-[#7A5800] flex items-center justify-center"><?= resource_icon('elec', 'w-5 h-5') ?></span>
          <span><span class="block text-xs text-muted">ไฟฟ้าเดือนนี้</span><span class="font-display text-lg text-[#1E6B45]"><?= e($elecBadge) ?></span></span>
        </div>
      </div>
      <div data-depth="-12" class="absolute right-0 bottom-16 transition-transform duration-200">
        <div class="anim-float bg-ink text-white rounded-[18px] px-4 py-3 flex items-center gap-2.5" style="animation-delay:-2.5s">
          <span class="w-9 h-9 rounded-xl bg-lime text-ink flex items-center justify-center"><svg aria-hidden="true" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0"/><path d="M16 4.5a3.5 3.5 0 0 1 0 7M18 14a6 6 0 0 1 3.5 6"/></svg></span>
          <span><span class="block text-xs text-[#CFE8E4]">ภารกิจพกแก้ว</span><span class="font-display text-lg"><span data-count="<?= $game['topics'][1]['count'] ?>" data-part="a_plastic-count">0</span> คนทำแล้ว</span></span>
        </div>
      </div>
      <div class="absolute left-7 bottom-5 font-display text-[15px] text-ku -rotate-6 bg-white px-3.5 py-1.5 rounded-full border-2 border-dashed border-leaf">ลองเลื่อนเมาส์มาทักน้องกล้าสิ</div>
    </div>
  </div>
</section>

<div aria-hidden="true" class="bg-ink text-lime overflow-hidden -rotate-[1.2deg] -mx-5 py-3.5">
  <div class="anim-marquee flex gap-10 w-max font-display text-[22px] whitespace-nowrap">
    <?php for ($i = 0; $i < 2; $i++): ?><span>ปิดไฟก่อนออก ✦ พกแก้วมาเอง ✦ ปริ้นต์สองหน้า ✦ แยกขยะให้ถูกสี ✦ ปั่นมาเรียน ✦ ใช้ซ้ำก่อนทิ้ง ✦ ประชุมไร้กระดาษ ✦</span><?php endfor; ?>
  </div>
</div>

<!-- ===== Resource overview (fuel, GHG, electricity, water, paper) ===== -->
<section class="max-w-7xl mx-auto px-4 sm:px-6 pt-16 pb-6">
  <div class="flex justify-between items-end gap-4 flex-wrap mb-6">
    <div>
      <div class="eyebrow">ตัวเลขสด จากระบบกรอกข้อมูล · <?= e(month_label($cur)) ?></div>
      <h2 class="font-display font-medium text-4xl leading-tight mt-1">ภาพรวมผลการดำเนินงานด้านสิ่งแวดล้อม</h2>
    </div>
    <a href="<?= e(url('stats.php')) ?>" class="btn bg-mint text-ku">ดูสถิติทั้งหมด →</a>
  </div>
  <?php if (!$cur): ?>
    <div class="card p-6 text-muted">ยังไม่มีข้อมูลสถิติ — ผู้ดูแลกรอกได้ที่ ระบบหลังบ้าน › จัดการสถิติ</div>
  <?php else: ?>
  <div class="grid gap-4 [grid-template-columns:repeat(auto-fit,minmax(210px,1fr))]">
    <?php $no = 0; foreach ($meta as $key => $m): $no++;
      $v = resource_value($cur, $key);
      [$badge, $bbg, $bfg] = $prev ? change_badge($v, resource_value($prev, $key)) : ['—', '#EDF0EA', '#4A5F5D']; ?>
      <div class="hover-lift rounded-[26px] p-5 flex flex-col gap-3 min-h-[220px]" style="background:<?= e($m['bg']) ?>">
        <div class="flex justify-between items-start">
          <div class="ico-bg w-14 h-14 rounded-[18px] bg-white flex items-center justify-center" style="color:<?= e($m['ac']) ?>"><?= resource_icon($key) ?></div>
          <span class="chip text-[13px]" style="background:<?= e($bbg) ?>;color:<?= e($bfg) ?>"><?= e($badge) ?></span>
        </div>
        <div class="text-sm text-muted">0<?= $no ?></div>
        <div class="-mt-3 font-display text-xl" style="color:<?= e($m['ac']) ?>"><?= e($m['name']) ?></div>
        <div class="flex items-baseline gap-1.5 flex-wrap mt-auto">
          <span class="font-display font-semibold text-4xl leading-none tabular-nums" data-count="<?= round($v, $m['dec']) ?>" data-dec="<?= $m['dec'] ?>">0</span>
          <span class="text-[15px] text-[#3E5553]"><?= e($m['unit']) ?></span>
        </div>
        <div class="text-xs text-muted">เทียบเดือนก่อน</div>
      </div>
    <?php endforeach; ?>
  </div>
  <p class="mt-3.5 text-sm text-[#5D716F]">ก๊าซเรือนกระจกคำนวณจากน้ำมันและไฟฟ้า (Scope 1+2) ด้วยค่าการปล่อยที่กำหนดในระบบหลังบ้าน</p>
  <?php endif; ?>
</section>

<!-- ===== Participation from the intro game ===== -->
<section class="max-w-7xl mx-auto px-4 sm:px-6 py-12">
  <div class="relative overflow-hidden bg-ku text-white rounded-[36px] p-8 sm:p-10">
    <div aria-hidden="true" class="anim-blob absolute -right-28 -top-28 w-[360px] h-[360px] bg-ku-mid"></div>
    <div class="relative flex flex-wrap gap-8 items-end justify-between mb-7">
      <div class="max-w-2xl">
        <div class="text-sm text-lime font-semibold tracking-wide">ผลจากเกม Green Check-in</div>
        <h2 class="font-display font-medium text-4xl leading-tight mt-1 mb-2">คุณกับเพื่อนอีก <span data-count="<?= $game['players'] ?>" data-part="players">0</span> คน กำลังทำเรื่องเหล่านี้</h2>
        <p class="text-[#CFE8E4]">ตัวเลขอัปเดตทันทีที่มีคนเล่นเกมหน้าแรกจบ</p>
      </div>
      <button type="button" data-play class="btn-sun font-display font-normal">เล่นเกมอีกรอบ</button>
    </div>
    <div class="relative grid gap-3.5 [grid-template-columns:repeat(auto-fit,minmax(220px,1fr))]">
      <?php foreach ($game['topics'] as $t): ?>
        <div class="hover-lift bg-white/10 border border-white/15 rounded-[22px] p-5 flex flex-col gap-2.5">
          <span class="font-display text-lg"><?= e($t['name']) ?></span>
          <div class="font-display text-[40px] leading-none text-lime"><span data-count="<?= $t['pct'] ?>" data-part="<?= e($t['key']) ?>-pct">0</span>%</div>
          <div class="h-2 rounded-full bg-white/15 overflow-hidden"><div class="grow-bar h-full bg-lime rounded-full" data-w="<?= $t['pct'] ?>" data-part="<?= e($t['key']) ?>-bar"></div></div>
          <div class="text-sm text-[#CFE8E4]"><span data-count="<?= $t['count'] ?>" data-part="<?= e($t['key']) ?>-count">0</span> คนทำประจำหรือบางครั้ง</div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ===== Missions + evidence hub ===== -->
<section class="max-w-7xl mx-auto px-4 sm:px-6 pb-12 flex flex-wrap gap-5">
  <div class="flex-[2_1_560px] bg-[#EEF6E6] rounded-[30px] p-7">
    <div class="flex justify-between items-center gap-3 flex-wrap mb-4">
      <h2 class="font-display font-medium text-[28px]">ภารกิจเดือนนี้</h2>
      <a href="<?= e(url('operations.php')) ?>" class="text-ku font-semibold underline min-h-[44px] inline-flex items-center">ดูการดำเนินงานทั้งหมด →</a>
    </div>
    <div class="grid gap-3 [grid-template-columns:repeat(auto-fit,minmax(200px,1fr))]">
      <?php foreach ($missions as $m): $joined = mission_already_joined((int)$m['id']); ?>
        <div class="hover-lift bg-white rounded-[20px] p-4 flex flex-col gap-3" data-mission-card="<?= (int)$m['id'] ?>">
          <div class="font-semibold"><?= e($m['name']) ?></div>
          <div class="h-2.5 rounded-full bg-[#EDF0EA] overflow-hidden"><div class="grow-bar h-full rounded-full" data-w="<?= $m['pct'] ?>" data-mission-bar style="background:<?= e($m['color']) ?>"></div></div>
          <div class="flex justify-between text-sm text-muted"><span><?= e($m['target_text']) ?></span><span class="font-semibold text-ink"><span data-mission-pct><?= $m['pct'] ?></span>%</span></div>
          <div class="flex justify-between items-center text-sm text-muted"><span><span data-mission-joined><?= $m['joined'] ?></span>/<?= (int)$m['target_count'] ?> คนเข้าร่วม</span>
            <button type="button" class="btn-sm btn-outline" data-mission-join="<?= (int)$m['id'] ?>" data-mission-name="<?= e($m['name']) ?>" <?= $joined ? 'disabled' : '' ?>><?= $joined ? 'เข้าร่วมแล้ว' : 'เข้าร่วม' ?></button>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="hover-lift flex-[1_1_320px] bg-[#FFF2C2] rounded-[30px] p-7 flex flex-col gap-3.5 relative overflow-hidden">
    <svg aria-hidden="true" class="anim-float absolute right-5 top-5 w-20 h-20 text-[#E0A800]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"><path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
    <h2 class="font-display font-medium text-[26px] max-w-[220px] leading-tight">ศูนย์รวมหลักฐานสำหรับการประเมิน</h2>
    <p class="text-[#5A4A10]">เอกสาร รูปภาพ และข้อมูลกิจกรรม จัดครบ 7 หมวดตามเกณฑ์ Green Office</p>
    <div class="font-display text-[40px] leading-none"><span data-count="<?= $evidenceCount ?>">0</span><span class="text-base text-[#5A4A10]"> รายการ</span></div>
    <a href="<?= e(url('operations.php')) ?>" class="mt-auto self-start btn-dark">ดูตามหมวด →</a>
  </div>
</section>

<!-- ===== News ===== -->
<section class="max-w-7xl mx-auto px-4 sm:px-6 pb-16">
  <div class="flex justify-between items-center gap-3 flex-wrap mb-5">
    <h2 class="font-display font-medium text-[32px]">ข่าวและกิจกรรม</h2>
    <a href="<?= e(url('news.php')) ?>" class="text-ku font-semibold underline min-h-[44px] inline-flex items-center">ดูทั้งหมด →</a>
  </div>
  <?php include __DIR__ . '/../app/views/partials/news_cards.php'; ?>
</section>

<?php include __DIR__ . '/../app/views/partials/mission_join_dialog.php'; ?>
<?php require __DIR__ . '/../app/views/public_bottom.php'; ?>
