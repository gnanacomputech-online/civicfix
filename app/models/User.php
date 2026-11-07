<?php

require_once __DIR__ . '/../../core/Database.php';

class User
{
    public static function find(int $id): ?array
    {
        $stmt = Database::connect()->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function emailExists(string $email): bool
    {
        $stmt = Database::connect()->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        return (bool)$stmt->fetch();
    }

    public static function create(
        string $name,
        string $email,
        string $password,
        string $role,
        ?string $phone = null,
        ?int $departmentId = null
    ): int {
        $stmt = Database::connect()->prepare(
            'INSERT INTO users (name, email, password, role, phone, department_id)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $name,
            $email,
            password_hash($password, PASSWORD_BCRYPT),
            $role,
            $phone,
            $departmentId,
        ]);
        return (int)Database::connect()->lastInsertId();
    }

    public static function allByRole(string $role): array
    {
        $stmt = Database::connect()->prepare(
            'SELECT u.*, d.name AS department_name
             FROM users u
             LEFT JOIN departments d ON d.id = u.department_id
             WHERE u.role = ?
             ORDER BY u.name'
        );
        $stmt->execute([$role]);
        return $stmt->fetchAll();
    }

    public static function setStatus(int $id, string $status, string $role): bool
    {
        $stmt = Database::connect()->prepare(
            'UPDATE users SET status = ? WHERE id = ? AND role = ?'
        );
        $stmt->execute([$status, $id, $role]);
        return $stmt->rowCount() > 0;
    }

    public static function activeOfficersByDepartment(int $departmentId): array
    {
        $stmt = Database::connect()->prepare(
            "SELECT * FROM users WHERE role = 'officer' AND department_id = ? AND status = 'active'"
        );
        $stmt->execute([$departmentId]);
        return $stmt->fetchAll();
    }

    public static function countByRole(string $role): int
    {
        $stmt = Database::connect()->prepare('SELECT COUNT(*) AS c FROM users WHERE role = ?');
        $stmt->execute([$role]);
        return (int)$stmt->fetch()['c'];
    }
}
