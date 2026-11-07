<?php

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function redirect(string $route): void
{
    // A route can itself carry a query string (e.g. "admin/complaints?status=breached").
    // Folding it straight into "?route=" without care turns everything after the "?"
    // into extra top-level query params instead of part of the route value, so split
    // it out and re-append it as its own param -- a bug that bit an earlier project
    // in this suite (see that project's README for the write-up).
    if (str_contains($route, '?')) {
        [$path, $query] = explode('?', $route, 2);
        header('Location: /index.php?route=' . $path . '&' . $query);
    } else {
        header('Location: /index.php?route=' . $route);
    }
    exit;
}

function flash_set(string $type, string $message): void
{
    $_SESSION['flash'][$type] = $message;
}

function flash_get(string $type): ?string
{
    if (empty($_SESSION['flash'][$type])) {
        return null;
    }
    $message = $_SESSION['flash'][$type];
    unset($_SESSION['flash'][$type]);
    return $message;
}

function old(array $data, string $key, string $default = ''): string
{
    return e($data[$key] ?? $default);
}

function format_date(?string $date): string
{
    if (empty($date)) {
        return '-';
    }
    $ts = strtotime($date);
    return $ts === false ? '-' : date('d M Y', $ts);
}

function format_datetime(?string $datetime): string
{
    if (empty($datetime)) {
        return '-';
    }
    $ts = strtotime($datetime);
    return $ts === false ? '-' : date('d M Y, h:i A', $ts);
}

function status_label(string $status): string
{
    return match ($status) {
        'new'         => 'New',
        'assigned'    => 'Assigned',
        'in_progress' => 'In Progress',
        'resolved'    => 'Resolved',
        'closed'      => 'Closed',
        default       => ucfirst($status),
    };
}

/**
 * Validate and store an uploaded photo under public/uploads/<subdir>/ with a
 * randomized filename (never the client's original name, to avoid overwrite/path
 * traversal). Returns the stored filename (relative, to save in the DB) on success,
 * or null with $error set on failure. MIME is checked server-side via finfo/
 * mime_content_type against the file's actual bytes, not the client-supplied
 * Content-Type header.
 */
function handle_photo_upload(array $file, string $subdir, ?string &$error): ?string
{
    $error = null;

    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        $error = 'Please choose a photo to upload.';
        return null;
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error = 'The photo upload failed. Please try again.';
        return null;
    }

    $maxBytes = 5 * 1024 * 1024; // 5 MB
    if ($file['size'] > $maxBytes) {
        $error = 'Photo must be 5 MB or smaller.';
        return null;
    }

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    $mime = false;
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
    } elseif (function_exists('mime_content_type')) {
        $mime = mime_content_type($file['tmp_name']);
    }

    if ($mime === false || !isset($allowed[$mime])) {
        $error = 'Only JPG, PNG, or WEBP photos are allowed.';
        return null;
    }

    $extension = $allowed[$mime];
    $storedName = bin2hex(random_bytes(16)) . '.' . $extension;
    $destDir = __DIR__ . '/../public/uploads/' . $subdir . '/';
    if (!is_dir($destDir)) {
        mkdir($destDir, 0755, true);
    }

    if (!move_uploaded_file($file['tmp_name'], $destDir . $storedName)) {
        $error = 'Could not save the uploaded photo.';
        return null;
    }

    return $storedName;
}

/** Small inline SLA badge for a complaint row/detail: green-ish "on track" or orange "breached". */
function sla_badge(array $complaint): string
{
    if (in_array($complaint['status'], ['resolved', 'closed'], true)) {
        return '';
    }
    if (Complaint::isBreached($complaint)) {
        return '<span class="badge badge-breach">Deadline Breached</span>';
    }
    $deadline = Complaint::slaDeadline($complaint);
    $remaining = duration_between(date('Y-m-d H:i:s'), date('Y-m-d H:i:s', $deadline));
    return '<span class="badge badge-new" style="background:var(--steel-light);color:var(--steel)">Due in ' . e($remaining) . '</span>';
}

/** Human-readable "2d 4h" style duration between two timestamps (or now). */
function duration_between(string $start, ?string $end = null): string
{
    $startTs = strtotime($start);
    $endTs = $end !== null ? strtotime($end) : time();
    if ($startTs === false || $endTs === false || $endTs < $startTs) {
        return '-';
    }
    $seconds = $endTs - $startTs;
    $days = intdiv($seconds, 86400);
    $hours = intdiv($seconds % 86400, 3600);
    if ($days > 0) {
        return "{$days}d {$hours}h";
    }
    $minutes = intdiv($seconds % 3600, 60);
    if ($hours > 0) {
        return "{$hours}h {$minutes}m";
    }
    return "{$minutes}m";
}
