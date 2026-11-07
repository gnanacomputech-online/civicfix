<?php

require_once __DIR__ . '/../../core/Database.php';

class Department
{
    public static function all(): array
    {
        $stmt = Database::connect()->query(
            "SELECT d.*,
                    (SELECT COUNT(*) FROM categories c WHERE c.department_id = d.id) AS category_count,
                    (SELECT COUNT(*) FROM users u WHERE u.department_id = d.id AND u.role = 'officer') AS officer_count,
                    (SELECT COUNT(*) FROM complaints cp WHERE cp.department_id = d.id) AS complaint_count
             FROM departments d
             ORDER BY d.name"
        );
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connect()->prepare('SELECT * FROM departments WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function nameExists(string $name, ?int $excludeId = null): bool
    {
        $sql = 'SELECT id FROM departments WHERE name = ?';
        $params = [$name];
        if ($excludeId !== null) {
            $sql .= ' AND id != ?';
            $params[] = $excludeId;
        }
        $stmt = Database::connect()->prepare($sql);
        $stmt->execute($params);
        return (bool)$stmt->fetch();
    }

    public static function create(string $name, ?string $description): int
    {
        $stmt = Database::connect()->prepare(
            'INSERT INTO departments (name, description) VALUES (?, ?)'
        );
        $stmt->execute([$name, $description]);
        return (int)Database::connect()->lastInsertId();
    }

    public static function update(int $id, string $name, ?string $description): void
    {
        $stmt = Database::connect()->prepare(
            'UPDATE departments SET name = ?, description = ? WHERE id = ?'
        );
        $stmt->execute([$name, $description, $id]);
    }

    public static function delete(int $id): bool
    {
        $stmt = Database::connect()->prepare('SELECT COUNT(*) AS c FROM categories WHERE department_id = ?');
        $stmt->execute([$id]);
        if ((int)$stmt->fetch()['c'] > 0) {
            return false;
        }
        $stmt = Database::connect()->prepare('SELECT COUNT(*) AS c FROM users WHERE department_id = ?');
        $stmt->execute([$id]);
        if ((int)$stmt->fetch()['c'] > 0) {
            return false;
        }
        $stmt = Database::connect()->prepare('DELETE FROM departments WHERE id = ?');
        $stmt->execute([$id]);
        return true;
    }
}
