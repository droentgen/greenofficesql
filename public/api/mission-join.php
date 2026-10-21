<?php
/**
 * POST /api/mission-join.php  { "mission_id": 1, "name": "...", "unit_id": 2 }
 * Records one person joining a mission (one join per browser session per mission)
 * and returns the mission's updated joined count / percent.
 */
require __DIR__ . '/../../app/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

if (!is_post()) {
    http_response_code(405);
    echo json_encode(['ok' => false]);
    exit;
}
$token = $_SERVER['HTTP_X_CSRF'] ?? '';
if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
    http_response_code(419);
    echo json_encode(['ok' => false, 'error' => 'csrf']);
    exit;
}

$body = json_decode(file_get_contents('php://input') ?: '', true);
$missionId = (int)($body['mission_id'] ?? 0);
$name = mb_substr(trim((string)($body['name'] ?? '')), 0, 150);
$unitId = (int)($body['unit_id'] ?? 0);

$mission = $missionId ? row('SELECT id, target_count FROM missions WHERE id = ? AND is_active = 1', [$missionId]) : null;
$unit = $unitId ? row('SELECT id FROM units WHERE id = ? AND is_active = 1', [$unitId]) : null;

if (!$mission || !$unit || $name === '') {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'invalid']);
    exit;
}
if (mission_already_joined($missionId)) {
    http_response_code(409);
    echo json_encode(['ok' => false, 'error' => 'already_joined']);
    exit;
}

q('INSERT INTO mission_participants (mission_id, name, unit_id) VALUES (?,?,?)', [$missionId, $name, $unitId]);
$_SESSION['missions_joined'][$missionId] = 1;

$joined = (int)val('SELECT COUNT(*) FROM mission_participants WHERE mission_id = ?', [$missionId]);
$pct = $mission['target_count'] > 0 ? min(100, (int)round($joined / $mission['target_count'] * 100)) : 0;
echo json_encode(['ok' => true, 'joined' => $joined, 'pct' => $pct], JSON_UNESCAPED_UNICODE);
