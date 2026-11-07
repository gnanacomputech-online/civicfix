<?php

require_once __DIR__ . '/../../core/Database.php';

class Ward
{
    public static function all(): array
    {
        $stmt = Database::connect()->query(
            'SELECT w.*,
                    (SELECT COUNT(*) FROM complaints cp WHERE cp.ward_id = w.id) AS complaint_count
             FROM wards w
             ORDER BY w.name'
        );
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connect()->prepare('SELECT * FROM wards WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function nameExists(string $name, ?int $excludeId = null): bool
    {
        $sql = 'SELECT id FROM wards WHERE name = ?';
        $params = [$name];
        if ($excludeId !== null) {
            $sql .= ' AND id != ?';
            $params[] = $excludeId;
        }
        $stmt = Database::connect()->prepare($sql);
        $stmt->execute($params);
        return (bool)$stmt->fetch();
    }

    public static function create(string $name): int
    {
        $stmt = Database::connect()->prepare('INSERT INTO wards (name) VALUES (?)');
        $stmt->execute([$name]);
        return (int)Database::connect()->lastInsertId();
    }

    public static function update(int $id, string $name): void
    {
        $stmt = Database::connect()->prepare('UPDATE wards SET name = ? WHERE id = ?');
        $stmt->execute([$name, $id]);
    }

    public static function delete(int $id): bool
    {
        $stmt = Database::connect()->prepare('SELECT COUNT(*) AS c FROM complaints WHERE ward_id = ?');
        $stmt->execute([$id]);
        if ((int)$stmt->fetch()['c'] > 0) {
            return false;
        }
        $stmt = Database::connect()->prepare('DELETE FROM wards WHERE id = ?');
        $stmt->execute([$id]);
        return true;
    }
}
