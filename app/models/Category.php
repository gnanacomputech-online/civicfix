<?php

require_once __DIR__ . '/../../core/Database.php';

class Category
{
    public static function all(): array
    {
        $stmt = Database::connect()->query(
            'SELECT c.*, d.name AS department_name,
                    (SELECT COUNT(*) FROM complaints cp WHERE cp.category_id = c.id) AS complaint_count
             FROM categories c
             JOIN departments d ON d.id = c.department_id
             ORDER BY d.name, c.name'
        );
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connect()->prepare(
            'SELECT c.*, d.name AS department_name
             FROM categories c JOIN departments d ON d.id = c.department_id
             WHERE c.id = ?'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function create(string $name, int $departmentId, int $slaHours): int
    {
        $stmt = Database::connect()->prepare(
            'INSERT INTO categories (name, department_id, sla_hours) VALUES (?, ?, ?)'
        );
        $stmt->execute([$name, $departmentId, $slaHours]);
        return (int)Database::connect()->lastInsertId();
    }

    public static function update(int $id, string $name, int $departmentId, int $slaHours): void
    {
        $stmt = Database::connect()->prepare(
            'UPDATE categories SET name = ?, department_id = ?, sla_hours = ? WHERE id = ?'
        );
        $stmt->execute([$name, $departmentId, $slaHours, $id]);
    }

    public static function delete(int $id): bool
    {
        $stmt = Database::connect()->prepare('SELECT COUNT(*) AS c FROM complaints WHERE category_id = ?');
        $stmt->execute([$id]);
        if ((int)$stmt->fetch()['c'] > 0) {
            return false;
        }
        $stmt = Database::connect()->prepare('DELETE FROM categories WHERE id = ?');
        $stmt->execute([$id]);
        return true;
    }
}
