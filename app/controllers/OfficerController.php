<?php

require_once __DIR__ . '/../models/Complaint.php';
require_once __DIR__ . '/../models/Comment.php';

class OfficerController
{
    public function dashboard(): void
    {
        Auth::requireRole('officer');
        $departmentId = Auth::departmentId();

        $counts = Complaint::countsByStatus($departmentId);
        $queue = Complaint::forDepartment($departmentId);
        $breached = array_values(array_filter($queue, fn($c) => Complaint::isBreached($c)));
        $mine = array_values(array_filter($queue, fn($c) => (int)$c['assigned_officer_id'] === Auth::id() && !in_array($c['status'], ['resolved', 'closed'], true)));

        $stats = [
            'unclaimed' => $counts['new'],
            'in_progress' => $counts['assigned'] + $counts['in_progress'],
            'resolved'  => $counts['resolved'],
            'breached'  => count($breached),
        ];

        require __DIR__ . '/../views/officer/dashboard.php';
    }

    public function queue(): void
    {
        Auth::requireRole('officer');
        $departmentId = Auth::departmentId();

        $status = trim($_GET['status'] ?? '') ?: null;
        $complaints = Complaint::forDepartment($departmentId, $status);

        require __DIR__ . '/../views/officer/queue.php';
    }

    public function complaintView(): void
    {
        Auth::requireRole('officer');

        $id = (int)($_GET['id'] ?? 0);
        $complaint = Complaint::find($id);
        if (!$complaint || (int)$complaint['department_id'] !== Auth::departmentId()) {
            http_response_code(404);
            require __DIR__ . '/../views/errors/404.php';
            return;
        }
        $history = Complaint::statusHistory($id);
        $comments = Comment::forComplaint($id);
        $commentRoute = 'officer/complaints/comment';

        require __DIR__ . '/../views/officer/complaint_detail.php';
    }

    public function commentStore(): void
    {
        Auth::requireRole('officer');
        Csrf::requireValid();

        $id = (int)($_POST['id'] ?? 0);
        $body = trim($_POST['body'] ?? '');

        $complaint = Complaint::find($id);
        if (!$complaint || (int)$complaint['department_id'] !== Auth::departmentId()) {
            flash_set('error', 'Complaint not found.');
            redirect('officer/queue');
        }

        if ($body !== '') {
            Comment::create($id, Auth::id(), $body);
        }
        redirect("officer/complaints/view?id=$id");
    }

    public function claim(): void
    {
        Auth::requireRole('officer');
        Csrf::requireValid();

        $id = (int)($_POST['id'] ?? 0);
        $complaint = Complaint::find($id);
        if (!$complaint || (int)$complaint['department_id'] !== Auth::departmentId()) {
            flash_set('error', 'Complaint not found.');
            redirect('officer/queue');
        }

        if (Complaint::claim($id, Auth::id(), Auth::departmentId())) {
            flash_set('success', 'Complaint claimed. It is now in your queue.');
        } else {
            flash_set('error', 'Someone already claimed this complaint.');
        }
        redirect("officer/complaints/view?id=$id");
    }

    public function updateStatus(): void
    {
        Auth::requireRole('officer');
        Csrf::requireValid();

        $id = (int)($_POST['id'] ?? 0);
        $note = trim($_POST['note'] ?? '') ?: null;

        $complaint = Complaint::find($id);
        if (!$complaint || (int)$complaint['department_id'] !== Auth::departmentId()) {
            flash_set('error', 'Complaint not found.');
            redirect('officer/queue');
        }

        $resolutionPhotoPath = null;
        $movingToResolved = ($complaint['status'] === 'in_progress');

        if ($movingToResolved) {
            if (empty($_FILES['resolution_photo']) || ($_FILES['resolution_photo']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                flash_set('error', 'A resolution proof photo is required to mark this complaint Resolved.');
                redirect("officer/complaints/view?id=$id");
            }
            $uploadError = null;
            $resolutionPhotoPath = handle_photo_upload($_FILES['resolution_photo'], 'resolutions', $uploadError);
            if ($resolutionPhotoPath === null) {
                flash_set('error', $uploadError);
                redirect("officer/complaints/view?id=$id");
            }
        }

        $result = Complaint::advanceStatus($id, Auth::id(), $note, $resolutionPhotoPath);

        if ($result === true && $movingToResolved) {
            Mailer::send(
                $complaint['citizen_email'],
                $complaint['citizen_name'],
                'Your complaint #' . $id . ' has been resolved',
                "<p>Your complaint <strong>#$id -- " . e($complaint['category_name']) . "</strong> has been marked Resolved.</p>"
                . "<p>Log in to CivicFix to review the resolution and confirm, rate, or reopen it if it isn't actually fixed.</p>"
            );
        }

        if ($result === true) {
            flash_set('success', 'Complaint status updated.');
        } elseif ($result === 'photo_required') {
            flash_set('error', 'A resolution proof photo is required to mark this complaint Resolved.');
        } elseif ($result === 'no_next_status') {
            flash_set('error', 'This complaint has no further status to advance to.');
        } else {
            flash_set('error', 'Could not update status -- it may have already changed. Please review and retry.');
        }

        redirect("officer/complaints/view?id=$id");
    }
}
