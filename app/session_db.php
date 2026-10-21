<?php
/**
 * Database-backed PHP session storage.
 *
 * Needed because some hosts may run each request on a different container with
 * no shared local disk — PHP's default file-based sessions would silently reset
 * on every request, breaking CSRF tokens, admin login and flash messages across
 * the whole site. Storing sessions in the database fixes that, and works the
 * same way on every host.
 */
final class DbSessionHandler implements SessionHandlerInterface
{
    public function open(string $path, string $name): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function read(string $id): string|false
    {
        $row = row('SELECT data FROM app_sessions WHERE id = ?', [$id]);
        return $row ? $row['data'] : '';
    }

    public function write(string $id, string $data): bool
    {
        q('INSERT INTO app_sessions (id, data, last_access) VALUES (?, ?, CURRENT_TIMESTAMP)
           ON DUPLICATE KEY UPDATE data = VALUES(data), last_access = VALUES(last_access)', [$id, $data]);
        return true;
    }

    public function destroy(string $id): bool
    {
        q('DELETE FROM app_sessions WHERE id = ?', [$id]);
        return true;
    }

    public function gc(int $max_lifetime): int|false
    {
        return q('DELETE FROM app_sessions WHERE last_access < DATE_SUB(CURRENT_TIMESTAMP, INTERVAL ? SECOND)', [$max_lifetime])->rowCount();
    }
}
