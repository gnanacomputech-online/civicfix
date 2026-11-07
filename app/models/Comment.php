<?php

require_once __DIR__ . '/../../core/Database.php';

class Comment
{
    public static function forComplaint(int $complaintId): array
    {
        $stmt = Database::connect()->prepare(
            'SELECT cm.*, u.name AS author_name, u.role AS author_role
             FROM complaint_comments cm
             JOIN users u ON u.id = cm.user_id
             WHERE cm.complaint_id = ?
             ORDER BY cm.created_at ASC, cm.id ASC'
        );
        $stmt->execute([$complaintId]);
        return $stmt->fetchAll();
    }

    public static function create(int $complaintId, int $userId, string $body): int
    {
        $stmt = Database::connect()->prepare(
            'INSERT INTO complaint_comments (complaint_id, user_id, body) VALUES (?, ?, ?)'
        );
        $stmt->execute([$complaintId, $userId, $body]);
        return (int)Database::connect()->lastInsertId();
    }
}
