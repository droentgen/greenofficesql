<?php
/**
 * Queries and definitions shared by several pages.
 */

/** The 5 resource figures, in the order shown on the site. */
function resource_meta(): array
{
    return [
        'fuel'  => ['name' => 'น้ำมันเชื้อเพลิง', 'unit' => 'ลิตร',  'col' => 'fuel_l',     'dec' => 0, 'bg' => '#FFE9D6', 'ac' => '#A3471A', 'tip' => 'รวมทริปรถไปแปลงทดลองให้เต็มคันก่อนออก ลดได้ทั้งน้ำมันและ CO₂'],
        'ghg'   => ['name' => 'ก๊าซเรือนกระจก', 'unit' => 'tCO₂e', 'col' => null,         'dec' => 1, 'bg' => '#E8E4F7', 'ac' => '#4A3D96', 'tip' => 'ไฟฟ้าคือก้อนใหญ่สุดของคาร์บอนคณะ ปิดแอร์เร็วขึ้น 30 นาทีช่วยได้จริง'],
        'elec'  => ['name' => 'ไฟฟ้า',          'unit' => 'kWh',   'col' => 'elec_kwh',   'dec' => 0, 'bg' => '#FFF2C2', 'ac' => '#7A5800', 'tip' => 'ตั้งแอร์ 26°C แล้วเปิดพัดลมช่วย ประหยัดไฟได้มาก'],
        'water' => ['name' => 'น้ำ',            'unit' => 'ลบ.ม.', 'col' => 'water_m3',   'dec' => 0, 'bg' => '#DCF0FA', 'ac' => '#155E80', 'tip' => 'เจอก๊อกรั่วในคณะ แจ้งผ่านแบบฟอร์มข้อคิดเห็นได้เลย'],
        'paper' => ['name' => 'กระดาษ',         'unit' => 'รีม',   'col' => 'paper_ream', 'dec' => 0, 'bg' => '#FBE3E3', 'ac' => '#962B38', 'tip' => 'ส่งเอกสารผ่านระบบออนไลน์ และปริ้นต์สองหน้าเป็นค่าเริ่มต้น'],
    ];
}

/** Greenhouse gas (tCO2e) from fuel and electricity, using the emission factors in settings. */
function ghg_of(array $m): float
{
    $efFuel = (float)setting('ef_fuel', '2.7');
    $efElec = (float)setting('ef_elec', '0.4999');
    return ((float)$m['fuel_l'] * $efFuel + (float)$m['elec_kwh'] * $efElec) / 1000;
}

/** Value of one resource for one month row. */
function resource_value(array $m, string $key): float
{
    if ($key === 'ghg') {
        return ghg_of($m);
    }
    return (float)$m[resource_meta()[$key]['col']];
}

/** Latest month and the month before it: [latest|null, previous|null]. */
function latest_two_months(): array
{
    $r = rows('SELECT * FROM resource_monthly ORDER BY year_be DESC, month DESC LIMIT 2');
    return [$r[0] ?? null, $r[1] ?? null];
}

/** All 12 calendar months (ม.ค.-ธ.ค.) of one Buddhist-era year; null where no row exists yet. */
function months_of_year(int $year): array
{
    $byMonth = [];
    foreach (rows('SELECT * FROM resource_monthly WHERE year_be = ?', [$year]) as $r) {
        $byMonth[(int)$r['month']] = $r;
    }
    $out = [];
    for ($m = 1; $m <= 12; $m++) {
        $out[] = $byMonth[$m] ?? null;
    }
    return $out;
}

function month_label(?array $m, bool $short = false): string
{
    if (!$m) {
        return '-';
    }
    return ($short ? THAI_MONTHS_SHORT : THAI_MONTHS)[(int)$m['month']] . ' ' . $m['year_be'];
}

/** Percent change text and colours, e.g. ['↓ 7.2%', bg, fg]. Lower use = green. */
function change_badge(float $cur, float $prev): array
{
    if ($prev <= 0) {
        return ['—', '#EDF0EA', '#4A5F5D'];
    }
    $ch = ($cur - $prev) / $prev * 100;
    return $ch <= 0
        ? ['↓ ' . number_format(abs($ch), 1) . '%', '#DDF2E6', '#1E6B45']
        : ['↑ ' . number_format($ch, 1) . '%', '#FDE2DD', '#A33A2A'];
}

/* ---------- Criteria (7 categories) ---------- */

function categories(): array
{
    return rows('SELECT * FROM categories ORDER BY id');
}

/**
 * Per-category counts for one year:
 * [category_id => ['total' => indicators, 'done' => reported (text or link), 'full' => text and link]]
 */
function category_progress(int $year): array
{
    $sql = 'SELECT i.category_id AS cid, COUNT(*) AS total,
                   SUM(CASE WHEN (r.report_text <> \'\' AND r.report_text IS NOT NULL) OR ev.n > 0 THEN 1 ELSE 0 END) AS done,
                   SUM(CASE WHEN (r.report_text <> \'\' AND r.report_text IS NOT NULL) AND ev.n > 0 THEN 1 ELSE 0 END) AS full
            FROM criteria_indicators d
            JOIN criteria_issues i ON i.id = d.issue_id
            LEFT JOIN criteria_reports r ON r.indicator_id = d.id AND r.year_be = ?
            LEFT JOIN (SELECT indicator_id, COUNT(*) n FROM criteria_evidence WHERE year_be = ? GROUP BY indicator_id) ev ON ev.indicator_id = d.id
            GROUP BY i.category_id';
    $out = [];
    foreach (rows($sql, [$year, $year]) as $r) {
        $out[(int)$r['cid']] = ['total' => (int)$r['total'], 'done' => (int)$r['done'], 'full' => (int)$r['full']];
    }
    return $out;
}

/**
 * Full table for one category and year: issues → indicators (with sub-items, report, evidence).
 */
function category_table(int $catId, int $year): array
{
    $issues = rows('SELECT * FROM criteria_issues WHERE category_id = ? ORDER BY sort, id', [$catId]);
    if (!$issues) {
        return [];
    }
    $inds = rows('SELECT d.* FROM criteria_indicators d JOIN criteria_issues i ON i.id = d.issue_id WHERE i.category_id = ? ORDER BY d.sort, d.id', [$catId]);
    $ids = array_column($inds, 'id') ?: [0];
    $in = implode(',', array_map('intval', $ids));

    $subs = [];
    foreach (rows("SELECT * FROM criteria_subitems WHERE indicator_id IN ($in) ORDER BY idx") as $s) {
        $subs[$s['indicator_id']][] = $s;
    }
    $reports = [];
    foreach (rows("SELECT * FROM criteria_reports WHERE year_be = ? AND indicator_id IN ($in)", [$year]) as $r) {
        $reports[$r['indicator_id']] = $r;
    }
    $evidence = [];
    foreach (rows("SELECT * FROM criteria_evidence WHERE year_be = ? AND indicator_id IN ($in) ORDER BY id", [$year]) as $ev) {
        $evidence[$ev['indicator_id']][] = $ev;
    }

    $byIssue = [];
    foreach ($inds as $d) {
        $rep = $reports[$d['id']] ?? null;
        $d['subs'] = $subs[$d['id']] ?? [];
        $d['report'] = $rep['report_text'] ?? '';
        $d['checks'] = $rep ? (json_decode($rep['checks_json'], true) ?: []) : [];
        $d['evidence'] = $evidence[$d['id']] ?? [];
        $d['status'] = report_status($d['report'], count($d['evidence']));
        $byIssue[$d['issue_id']][] = $d;
    }
    foreach ($issues as &$iss) {
        $iss['indicators'] = $byIssue[$iss['id']] ?? [];
    }
    return $issues;
}

/** Status label + colours for one indicator's report. */
function report_status(string $text, int $links): array
{
    $t = trim($text) !== '';
    if ($t && $links > 0) {
        return ['label' => 'ครบถ้วน', 'bg' => '#DDF2E6', 'fg' => '#1E6B45', 'dot' => '#2E9E5B'];
    }
    if ($t || $links > 0) {
        return ['label' => 'บางส่วน', 'bg' => '#FFF2C2', 'fg' => '#6B4E00', 'dot' => '#E0A800'];
    }
    return ['label' => 'ยังไม่รายงาน', 'bg' => '#EDF0EA', 'fg' => '#4A5F5D', 'dot' => '#B5C0BD'];
}

/* ---------- Complaints ---------- */

const COMPLAINT_STATUS = [
    'received' => ['label' => 'รับเรื่องแล้ว',     'bg' => '#DCF0FA', 'fg' => '#155E80', 'step' => 1],
    'checking' => ['label' => 'กำลังตรวจสอบ',    'bg' => '#E8E4F7', 'fg' => '#4A3D96', 'step' => 2],
    'fixing'   => ['label' => 'กำลังดำเนินการ',   'bg' => '#FFF2C2', 'fg' => '#6B4E00', 'step' => 3],
    'done'     => ['label' => 'ดำเนินการแล้ว',    'bg' => '#DDF2E6', 'fg' => '#1E6B45', 'step' => 4],
];

const COMPLAINT_TOPICS = ['ไฟฟ้า / พลังงาน', 'น้ำ', 'ขยะ / ของเสีย', 'อากาศ / กลิ่น', 'เสียง', 'ความปลอดภัย', 'พื้นที่สีเขียว', 'อื่น ๆ'];

function next_ticket_no(): string
{
    $year = current_year_be();
    $last = val('SELECT ticket_no FROM complaints WHERE ticket_no LIKE ? ORDER BY id DESC LIMIT 1', ["GO-$year-%"]);
    $n = $last ? ((int)substr($last, -3)) + 1 : 1;
    return sprintf('GO-%d-%03d', $year, $n);
}

/* ---------- About page document sections ---------- */

const DOC_SECTIONS = [
    'policy'           => 'นโยบาย',
    'goals'            => 'เป้าหมาย',
    'team'             => 'คณะทำงาน',
    'measures'         => 'มาตรการและแนวปฏิบัติ',
    'complaint_plan'   => 'แผนการจัดการข้อร้องเรียน',
    'complaint_notice' => 'ประกาศข้อร้องเรียน',
];

/** Where a document opens: uploaded file or external link. */
function doc_href(array $d): string
{
    return $d['file_path'] ? upload_url($d['file_path']) : (string)$d['external_url'];
}

/* ---------- Intro game ---------- */

const GAME_TOPICS = [
    'a_elec'    => 'ปิดไฟ-แอร์',
    'a_plastic' => 'พกแก้ว/ขวดน้ำ',
    'a_paper'   => 'ลดการปริ้นต์',
    'a_commute' => 'เดิน ปั่น ขนส่งสาธารณะ',
];

/** Participation: total players and how many do each habit (answer 1 or 2). */
function game_stats(): array
{
    $r = row('SELECT COUNT(*) AS players,
                     SUM(a_elec > 0) AS a_elec, SUM(a_plastic > 0) AS a_plastic,
                     SUM(a_paper > 0) AS a_paper, SUM(a_commute > 0) AS a_commute
              FROM game_responses') ?? [];
    $players = (int)($r['players'] ?? 0);
    $topics = [];
    foreach (GAME_TOPICS as $col => $label) {
        $count = (int)($r[$col] ?? 0);
        $topics[] = ['key' => $col, 'name' => $label, 'count' => $count, 'pct' => $players ? round($count / $players * 100) : 0];
    }
    return ['players' => $players, 'topics' => $topics];
}

/* ---------- Missions (ภารกิจประจำเดือน) ---------- */

/** หน่วยงาน/สาขาวิชา options for the mission join form, in admin-chosen order. */
function units_list(bool $activeOnly = true): array
{
    return rows('SELECT * FROM units' . ($activeOnly ? ' WHERE is_active = 1' : '') . ' ORDER BY sort, id');
}

/**
 * Active missions with progress computed live from mission_participants
 * (not stored) — ['joined' => people who clicked เข้าร่วม, 'pct' => 0-100].
 */
function missions_with_progress(bool $activeOnly = true): array
{
    $sql = 'SELECT m.*, COUNT(mp.id) AS joined
            FROM missions m
            LEFT JOIN mission_participants mp ON mp.mission_id = m.id'
        . ($activeOnly ? ' WHERE m.is_active = 1' : '')
        . ' GROUP BY m.id ORDER BY m.sort, m.id';
    $out = [];
    foreach (rows($sql) as $m) {
        $m['joined'] = (int)$m['joined'];
        $m['pct'] = $m['target_count'] > 0 ? min(100, (int)round($m['joined'] / $m['target_count'] * 100)) : 0;
        $out[] = $m;
    }
    return $out;
}

/** Has this browser session already joined this mission? (one join per session, like the intro game.) */
function mission_already_joined(int $missionId): bool
{
    return !empty($_SESSION['missions_joined'][$missionId]);
}

/** Unit leaderboard by number of mission joins, optionally within a date range (Y-m-d). */
function unit_leaderboard(?string $from = null, ?string $to = null): array
{
    $where = [];
    $params = [];
    if ($from) { $where[] = 'mp.created_at >= ?'; $params[] = $from . ' 00:00:00'; }
    if ($to) { $where[] = 'mp.created_at <= ?'; $params[] = $to . ' 23:59:59'; }
    $sql = 'SELECT u.id, u.name, COUNT(mp.id) AS joins, COUNT(DISTINCT mp.mission_id) AS missions
            FROM mission_participants mp
            JOIN units u ON u.id = mp.unit_id'
        . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
        . ' GROUP BY u.id ORDER BY joins DESC, u.name';
    return rows($sql, $params);
}
