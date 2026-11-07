<?php

require_once __DIR__ . '/../../core/Database.php';

class Complaint
{
    public const STATUSES = ['new', 'assigned', 'in_progress', 'resolved', 'closed'];
    public const OPEN_STATUSES = ['new', 'assigned', 'in_progress'];

    /**
     * Forward-only pipeline: current status => the one officer status-update advances to.
     * Deliberately stops at "resolved" -- closing is no longer something the officer does
     * to themselves; it's the citizen confirming (rate()) or reopening (reopen()), with
     * Super Admin's adminClose() as the escape hatch if the citizen never responds.
     */
    private const NEXT_STATUS = [
        'assigned'    => 'in_progress',
        'in_progress' => 'resolved',
    ];

    private const SELECT_BASE = "
        SELECT cp.*,
               cat.name AS category_name, cat.sla_hours AS sla_hours,
               d.id AS department_id_real, d.name AS department_name,
               w.name AS ward_name,
               cz.name AS citizen_name, cz.email AS citizen_email,
               off.name AS officer_name,
               (SELECT COUNT(*) FROM complaint_upvotes cu WHERE cu.complaint_id = cp.id) AS upvote_count
        FROM complaints cp
        JOIN categories cat   ON cat.id = cp.category_id
        JOIN departments d    ON d.id = cp.department_id
        JOIN wards w          ON w.id = cp.ward_id
        JOIN users cz         ON cz.id = cp.citizen_id
        LEFT JOIN users off   ON off.id = cp.assigned_officer_id
    ";

    /** Whether an open complaint has already blown past its category's SLA target. Computed, never stored. */
    public static function isBreached(array $complaint): bool
    {
        if (in_array($complaint['status'], ['resolved', 'closed'], true)) {
            return false;
        }
        $deadline = strtotime($complaint['created_at']) + ((int)$complaint['sla_hours'] * 3600);
        return time() > $deadline;
    }

    public static function slaDeadline(array $complaint): int
    {
        return strtotime($complaint['created_at']) + ((int)$complaint['sla_hours'] * 3600);
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connect()->prepare(self::SELECT_BASE . ' WHERE cp.id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function forCitizen(int $citizenId): array
    {
        $stmt = Database::connect()->prepare(
            self::SELECT_BASE . ' WHERE cp.citizen_id = ? ORDER BY cp.created_at DESC'
        );
        $stmt->execute([$citizenId]);
        return $stmt->fetchAll();
    }

    /** A department's shared queue: unclaimed "new" complaints plus everything already claimed in-department. */
    public static function forDepartment(int $departmentId, ?string $status = null): array
    {
        $sql = self::SELECT_BASE . ' WHERE cp.department_id = ?';
        $params = [$departmentId];
        if ($status !== null && $status !== '') {
            $sql .= ' AND cp.status = ?';
            $params[] = $status;
        }
        $sql .= ' ORDER BY cp.created_at ASC';
        $stmt = Database::connect()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Admin oversight list with optional filters. */
    public static function search(?int $departmentId, ?int $wardId, ?string $status): array
    {
        $sql = self::SELECT_BASE . ' WHERE 1=1';
        $params = [];
        if ($departmentId) {
            $sql .= ' AND cp.department_id = ?';
            $params[] = $departmentId;
        }
        if ($wardId) {
            $sql .= ' AND cp.ward_id = ?';
            $params[] = $wardId;
        }
        if ($status) {
            $sql .= ' AND cp.status = ?';
            $params[] = $status;
        }
        $sql .= ' ORDER BY cp.created_at DESC';
        $stmt = Database::connect()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Every open complaint that has breached its SLA -- the Super Admin's escalation queue. */
    public static function escalations(): array
    {
        $sql = self::SELECT_BASE . " WHERE cp.status IN ('new','assigned','in_progress') ORDER BY cp.created_at ASC";
        $stmt = Database::connect()->query($sql);
        $rows = $stmt->fetchAll();
        return array_values(array_filter($rows, fn($row) => self::isBreached($row)));
    }

    public static function statusHistory(int $complaintId): array
    {
        $stmt = Database::connect()->prepare(
            'SELECT h.*, u.name AS changed_by_name
             FROM complaint_status_history h
             LEFT JOIN users u ON u.id = h.changed_by
             WHERE h.complaint_id = ?
             ORDER BY h.changed_at ASC, h.id ASC'
        );
        $stmt->execute([$complaintId]);
        return $stmt->fetchAll();
    }

    /**
     * File a new complaint. The owning department is copied from the category at this
     * instant (auto-routing) -- a deliberate snapshot, not a live lookup, so a later
     * re-mapping of the category doesn't retroactively move history to a different desk.
     */
    public static function create(
        int $citizenId,
        int $categoryId,
        int $wardId,
        string $addressLine,
        string $description,
        ?string $photoPath
    ): int {
        $pdo = Database::connect();
        $catStmt = $pdo->prepare('SELECT department_id FROM categories WHERE id = ?');
        $catStmt->execute([$categoryId]);
        $category = $catStmt->fetch();
        if (!$category) {
            throw new RuntimeException('Invalid category.');
        }

        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO complaints
                    (citizen_id, category_id, department_id, ward_id, address_line, description, photo_path, status)
                 VALUES (?, ?, ?, ?, ?, ?, ?, "new")'
            );
            $stmt->execute([
                $citizenId, $categoryId, $category['department_id'], $wardId, $addressLine, $description, $photoPath,
            ]);
            $id = (int)$pdo->lastInsertId();

            self::logHistory($id, 'new', $citizenId, 'Complaint filed and auto-routed to department.');

            $pdo->commit();
            return $id;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * An officer claims an unclaimed "new" complaint in their own department.
     * The WHERE clause doubles as the concurrency guard: if two officers race to claim
     * the same complaint, only the first UPDATE (still status = 'new') affects a row.
     */
    public static function claim(int $complaintId, int $officerId, int $departmentId): bool
    {
        $pdo = Database::connect();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare(
                "UPDATE complaints
                 SET status = 'assigned', assigned_officer_id = ?
                 WHERE id = ? AND department_id = ? AND status = 'new'"
            );
            $stmt->execute([$officerId, $complaintId, $departmentId]);
            if ($stmt->rowCount() === 0) {
                $pdo->rollBack();
                return false;
            }
            self::logHistory($complaintId, 'assigned', $officerId, 'Claimed by officer.');
            $pdo->commit();
            return true;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Advance a complaint one step along the fixed pipeline. Only the officer it's
     * assigned to may advance it, and a move into "resolved" is refused server-side
     * unless a resolution proof photo is supplied in this same call -- the UI also
     * requires the file, but this is the check that actually can't be bypassed.
     */
    public static function advanceStatus(
        int $complaintId,
        int $officerId,
        ?string $note,
        ?string $resolutionPhotoPath
    ): bool|string {
        $complaint = self::find($complaintId);
        if (!$complaint || (int)$complaint['assigned_officer_id'] !== $officerId) {
            return 'not_found';
        }

        $current = $complaint['status'];
        $next = self::NEXT_STATUS[$current] ?? null;
        if ($next === null) {
            return 'no_next_status';
        }
        if ($next === 'resolved' && !$resolutionPhotoPath) {
            return 'photo_required';
        }

        $pdo = Database::connect();
        $pdo->beginTransaction();
        try {
            $sets = ['status = ?'];
            $params = [$next];
            if ($next === 'resolved') {
                $sets[] = 'resolution_photo_path = ?';
                $params[] = $resolutionPhotoPath;
                $sets[] = 'resolved_at = NOW()';
            }
            if ($next === 'closed') {
                $sets[] = 'closed_at = NOW()';
            }
            $params[] = $complaintId;
            $params[] = $officerId;
            $params[] = $current;

            $sql = 'UPDATE complaints SET ' . implode(', ', $sets)
                 . ' WHERE id = ? AND assigned_officer_id = ? AND status = ?';
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            if ($stmt->rowCount() === 0) {
                $pdo->rollBack();
                return 'conflict';
            }

            self::logHistory($complaintId, $next, $officerId, $note ?: null);
            $pdo->commit();
            return true;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    private static function logHistory(int $complaintId, string $status, ?int $changedBy, ?string $note): void
    {
        $stmt = Database::connect()->prepare(
            'INSERT INTO complaint_status_history (complaint_id, status, note, changed_by, changed_at)
             VALUES (?, ?, ?, ?, NOW())'
        );
        $stmt->execute([$complaintId, $status, $note, $changedBy]);
    }

    /** Citizen confirms a Resolved complaint is actually fixed, rates it, and closes it. */
    public static function rate(int $complaintId, int $citizenId, int $rating, ?string $comment): bool
    {
        $stmt = Database::connect()->prepare(
            "UPDATE complaints
             SET citizen_rating = ?, citizen_rating_comment = ?, status = 'closed', closed_at = NOW()
             WHERE id = ? AND citizen_id = ? AND status = 'resolved'"
        );
        $stmt->execute([$rating, $comment, $complaintId, $citizenId]);
        if ($stmt->rowCount() === 0) {
            return false;
        }
        $note = "Citizen confirmed resolution, rated $rating/5" . ($comment ? ": $comment" : '.');
        self::logHistory($complaintId, 'closed', $citizenId, $note);
        return true;
    }

    /** Citizen disputes a Resolved complaint and sends it back to the department, once. */
    public static function reopen(int $complaintId, int $citizenId, ?string $reason): bool
    {
        $pdo = Database::connect();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare(
                "UPDATE complaints
                 SET status = 'in_progress', resolved_at = NULL, reopen_count = reopen_count + 1
                 WHERE id = ? AND citizen_id = ? AND status = 'resolved' AND reopen_count < 1"
            );
            $stmt->execute([$complaintId, $citizenId]);
            if ($stmt->rowCount() === 0) {
                $pdo->rollBack();
                return false;
            }
            self::logHistory($complaintId, 'in_progress', $citizenId, 'Reopened by citizen' . ($reason ? ": $reason" : '.'));
            $pdo->commit();
            return true;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /** Super Admin escape hatch: close a Resolved complaint the citizen never responded to. */
    public static function adminClose(int $complaintId, int $adminId): bool
    {
        $stmt = Database::connect()->prepare(
            "UPDATE complaints SET status = 'closed', closed_at = NOW() WHERE id = ? AND status = 'resolved'"
        );
        $stmt->execute([$complaintId]);
        if ($stmt->rowCount() === 0) {
            return false;
        }
        self::logHistory($complaintId, 'closed', $adminId, 'Closed by Super Admin (no citizen response).');
        return true;
    }

    /**
     * Complaints that look like the same issue already being reported: same category and
     * ward, still open, filed recently. $limit is interpolated (not bound) -- Database.php
     * runs with PDO::ATTR_EMULATE_PREPARES = false, and a real MySQL prepared statement
     * rejects a bound LIMIT parameter's type, so it has to be a literal here.
     */
    public static function findDuplicates(int $categoryId, int $wardId, int $days = 14, int $limit = 5): array
    {
        $sql = self::SELECT_BASE . "
            WHERE cp.category_id = ? AND cp.ward_id = ?
              AND cp.status IN ('new','assigned','in_progress')
              AND cp.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            ORDER BY upvote_count DESC, cp.created_at DESC
            LIMIT " . (int)$limit;
        $stmt = Database::connect()->prepare($sql);
        $stmt->execute([$categoryId, $wardId, $days]);
        return $stmt->fetchAll();
    }

    /**
     * A citizen backs an existing open complaint as "this is happening to me too" instead
     * of filing a near-duplicate. Returns true, or a string reason it was rejected.
     */
    public static function upvote(int $complaintId, int $citizenId): bool|string
    {
        $complaint = self::find($complaintId);
        if (!$complaint || !in_array($complaint['status'], self::OPEN_STATUSES, true)) {
            return 'not_found';
        }
        if ((int)$complaint['citizen_id'] === $citizenId) {
            return 'own_complaint';
        }
        try {
            $stmt = Database::connect()->prepare(
                'INSERT INTO complaint_upvotes (complaint_id, citizen_id) VALUES (?, ?)'
            );
            $stmt->execute([$complaintId, $citizenId]);
            return true;
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                return 'already_upvoted';
            }
            throw $e;
        }
    }

    /**
     * Per-officer stats for the Super Admin: how many complaints they've claimed, how many
     * they've resolved, average resolution time, and their late-resolution rate (resolved
     * after the category's SLA deadline). Same split-query-then-merge-in-PHP technique as
     * publicStatsGroupedBy() below, so it runs the same on MySQL and a SQLite stand-in.
     */
    public static function officerPerformance(): array
    {
        $officers = Database::connect()->query(
            "SELECT off.id, off.name, d.name AS department_name,
                    COUNT(cp.id) AS total_claimed,
                    SUM(CASE WHEN cp.status IN ('resolved','closed') THEN 1 ELSE 0 END) AS resolved_count
             FROM users off
             LEFT JOIN complaints cp ON cp.assigned_officer_id = off.id
             LEFT JOIN departments d ON d.id = off.department_id
             WHERE off.role = 'officer'
             GROUP BY off.id, off.name, d.name
             ORDER BY off.name"
        )->fetchAll();

        $rows = Database::connect()->query(
            "SELECT cp.assigned_officer_id AS officer_id, cp.created_at, cp.resolved_at, cat.sla_hours
             FROM complaints cp
             JOIN categories cat ON cat.id = cp.category_id
             WHERE cp.status IN ('resolved','closed') AND cp.resolved_at IS NOT NULL"
        )->fetchAll();

        $byOfficer = [];
        foreach ($rows as $row) {
            $byOfficer[$row['officer_id']][] = $row;
        }

        foreach ($officers as &$o) {
            $resolved = (int)$o['resolved_count'];
            $officerRows = $byOfficer[$o['id']] ?? [];
            if ($resolved > 0 && $officerRows) {
                $hoursSum = 0;
                $lateCount = 0;
                foreach ($officerRows as $r) {
                    $hours = (strtotime($r['resolved_at']) - strtotime($r['created_at'])) / 3600;
                    $hoursSum += $hours;
                    if ($hours > (int)$r['sla_hours']) {
                        $lateCount++;
                    }
                }
                $o['avg_resolution_hours'] = round($hoursSum / count($officerRows), 1);
                $o['late_rate'] = round(($lateCount / count($officerRows)) * 100, 1);
            } else {
                $o['avg_resolution_hours'] = null;
                $o['late_rate'] = null;
            }
        }
        unset($o);

        return $officers;
    }

    public static function countsByStatus(?int $departmentId = null): array
    {
        $sql = 'SELECT status, COUNT(*) AS c FROM complaints';
        $params = [];
        if ($departmentId !== null) {
            $sql .= ' WHERE department_id = ?';
            $params[] = $departmentId;
        }
        $sql .= ' GROUP BY status';
        $stmt = Database::connect()->prepare($sql);
        $stmt->execute($params);
        $counts = array_fill_keys(self::STATUSES, 0);
        foreach ($stmt->fetchAll() as $row) {
            $counts[$row['status']] = (int)$row['c'];
        }
        return $counts;
    }

    /**
     * Aggregate-only stats for the public dashboard: resolution rate and average
     * resolution time, grouped by ward and by department. Deliberately selects no
     * citizen name/contact/address/description/photo column anywhere in this method.
     */
    public static function publicStatsByWard(): array
    {
        return self::publicStatsGroupedBy('w.id', 'w.name', 'wards w', 'cp.ward_id = w.id');
    }

    public static function publicStatsByDepartment(): array
    {
        return self::publicStatsGroupedBy('d.id', 'd.name', 'departments d', 'cp.department_id = d.id');
    }

    private static function publicStatsGroupedBy(string $idCol, string $nameCol, string $table, string $joinOn): array
    {
        // Average resolution time is computed in PHP below (from raw created_at/resolved_at
        // pairs) rather than a DB-specific date-diff function, so this runs identically on
        // MySQL (the real deliverable) and the SQLite stand-in used for local smoke testing.
        $sql = "SELECT $idCol AS group_id, $nameCol AS group_name,
                       COUNT(cp.id) AS total,
                       SUM(CASE WHEN cp.status IN ('resolved','closed') THEN 1 ELSE 0 END) AS resolved_count
                FROM $table
                LEFT JOIN complaints cp ON $joinOn
                GROUP BY $idCol, $nameCol
                ORDER BY $nameCol";
        $stmt = Database::connect()->query($sql);
        $groups = $stmt->fetchAll();

        // Pull resolved complaints' created_at/resolved_at per group to average in PHP.
        $timesStmt = Database::connect()->query(
            "SELECT $idCol AS group_id, cp.created_at, cp.resolved_at
             FROM $table
             LEFT JOIN complaints cp ON $joinOn
             WHERE cp.status IN ('resolved','closed') AND cp.resolved_at IS NOT NULL"
        );
        $durations = [];
        foreach ($timesStmt->fetchAll() as $row) {
            $hours = (strtotime($row['resolved_at']) - strtotime($row['created_at'])) / 3600;
            $durations[$row['group_id']][] = $hours;
        }

        foreach ($groups as &$g) {
            $total = (int)$g['total'];
            $resolved = (int)$g['resolved_count'];
            $g['resolution_rate'] = $total > 0 ? round(($resolved / $total) * 100, 1) : null;
            $groupDurations = $durations[$g['group_id']] ?? [];
            $g['avg_resolution_hours'] = $groupDurations
                ? round(array_sum($groupDurations) / count($groupDurations), 1)
                : null;
        }
        unset($g);

        return $groups;
    }
}
