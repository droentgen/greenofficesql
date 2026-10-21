<?php
/**
 * Database access (PDO + MySQL / MariaDB). Always use the helpers below with
 * placeholders (?) so user input is never put into SQL directly.
 *
 *   rows('SELECT * FROM news WHERE status = ?', ['published']);
 *   row('SELECT * FROM users WHERE id = ?', [$id]);
 *   val('SELECT COUNT(*) FROM complaints');
 *   q('UPDATE news SET title = ? WHERE id = ?', [$title, $id]);
 */

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        global $CONFIG;
        $c = $CONFIG['db'];
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $c['host'], $c['port'], $c['name']);
        $pdo = new PDO($dsn, $c['user'], $c['pass'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
    return $pdo;
}

function q(string $sql, array $params = []): PDOStatement
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

function rows(string $sql, array $params = []): array
{
    return q($sql, $params)->fetchAll();
}

function row(string $sql, array $params = []): ?array
{
    $r = q($sql, $params)->fetch();
    return $r === false ? null : $r;
}

function val(string $sql, array $params = [])
{
    return q($sql, $params)->fetchColumn();
}
