<?php

require_once __DIR__ . '/../models/Complaint.php';
require_once __DIR__ . '/../models/Category.php';
require_once __DIR__ . '/../models/Ward.php';
require_once __DIR__ . '/../models/Comment.php';
require_once __DIR__ . '/../models/User.php';

class CitizenController
{
    public function dashboard(): void
    {
        Auth::requireRole('citizen');
        $complaints = Complaint::forCitizen(Auth::id());
        $open = array_filter($complaints, fn($c) => in_array($c['status'], Complaint::OPEN_STATUSES, true));
        $breached = array_filter($open, fn($c) => Complaint::isBreached($c));

        $stats = [
            'total'    => count($complaints),
            'open'     => count($open),
            'resolved' => count(array_filter($complaints, fn($c) => in_array($c['status'], ['resolved', 'closed'], true))),
            'breached' => count($breached),
        ];

        require __DIR__ . '/../views/citizen/dashboard.php';
    }

    public function newForm(): void
    {
        Auth::requireRole('citizen');
        $categories = Category::all();
        $wards = Ward::all();
        $errors = [];
        $old = [];
        $duplicates = [];
        require __DIR__ . '/../views/citizen/new.php';
    }

    public function store(): void
    {
        Auth::requireRole('citizen');
        Csrf::requireValid();

        $categoryId = (int)($_POST['category_id'] ?? 0);
        $wardId = (int)($_POST['ward_id'] ?? 0);
        $addressLine = trim($_POST['address_line'] ?? '');
        $description = trim($_POST['description'] ?? '');

        $v = new Validator();
        $v->required($_POST, 'address_line', 'Address / landmark')->maxLength($_POST, 'address_line', 255, 'Address / landmark')
          ->required($_POST, 'description', 'Description')->maxLength($_POST, 'description', 2000, 'Description');
        $errors = $v->errors();

        if (!Category::find($categoryId)) {
            $errors['category_id'] = 'Please choose a valid category.';
        }
        if (!Ward::find($wardId)) {
            $errors['ward_id'] = 'Please choose a valid ward.';
        }

        $photoPath = null;
        if (empty($_FILES['photo']) || ($_FILES['photo']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            $errors['photo'] = 'A photo of the issue is required.';
        } else {
            $uploadError = null;
            $photoPath = handle_photo_upload($_FILES['photo'], 'complaints', $uploadError);
            if ($photoPath === null) {
                $errors['photo'] = $uploadError;
            }
        }

        if (!empty($errors)) {
            $old = $_POST;
            $categories = Category::all();
            $wards = Ward::all();
            $duplicates = [];
            require __DIR__ . '/../views/citizen/new.php';
            return;
        }

        if (empty($_POST['confirm_new'])) {
            $duplicates = Complaint::findDuplicates($categoryId, $wardId);
            if (!empty($duplicates)) {
                $old = $_POST;
                $categories = Category::all();
                $wards = Ward::all();
                require __DIR__ . '/../views/citizen/new.php';
                return;
            }
        }

        $id = Complaint::create(Auth::id(), $categoryId, $wardId, $addressLine, $description, $photoPath);

        $category = Category::find($categoryId);
        $ward = Ward::find($wardId);
        foreach (User::activeOfficersByDepartment((int)$category['department_id']) as $officer) {
            Mailer::send(
                $officer['email'],
                $officer['name'],
                'New complaint filed: ' . $category['name'],
                "<p>A new complaint has been filed in your department's queue.</p>"
                . "<p><strong>Category:</strong> " . e($category['name']) . "<br>"
                . "<strong>Ward:</strong> " . e($ward['name']) . "</p>"
                . "<p>Log in to CivicFix to claim it from the department queue.</p>"
            );
        }

        flash_set('success', 'Complaint filed and routed to the responsible department.');
        redirect("citizen/complaints/view?id=$id");
    }

    public function complaintView(): void
    {
        Auth::requireRole('citizen');

        $id = (int)($_GET['id'] ?? 0);
        $complaint = Complaint::find($id);
        if (!$complaint || (int)$complaint['citizen_id'] !== Auth::id()) {
            http_response_code(404);
            require __DIR__ . '/../views/errors/404.php';
            return;
        }
        $history = Complaint::statusHistory($id);
        $comments = Comment::forComplaint($id);
        $commentRoute = 'citizen/complaints/comment';

        require __DIR__ . '/../views/citizen/complaint_detail.php';
    }

    public function rateComplaint(): void
    {
        Auth::requireRole('citizen');
        Csrf::requireValid();

        $id = (int)($_POST['id'] ?? 0);
        $rating = (int)($_POST['rating'] ?? 0);
        $comment = trim($_POST['comment'] ?? '') ?: null;

        $complaint = Complaint::find($id);
        if (!$complaint || (int)$complaint['citizen_id'] !== Auth::id()) {
            flash_set('error', 'Complaint not found.');
            redirect('citizen/dashboard');
        }

        if ($rating < 1 || $rating > 5) {
            flash_set('error', 'Please choose a rating between 1 and 5.');
            redirect("citizen/complaints/view?id=$id");
        }

        if (Complaint::rate($id, Auth::id(), $rating, $comment)) {
            flash_set('success', 'Thanks for your feedback. Complaint closed.');
        } else {
            flash_set('error', 'Could not submit rating -- this complaint may no longer be open for feedback.');
        }
        redirect("citizen/complaints/view?id=$id");
    }

    public function reopenComplaint(): void
    {
        Auth::requireRole('citizen');
        Csrf::requireValid();

        $id = (int)($_POST['id'] ?? 0);
        $reason = trim($_POST['reason'] ?? '') ?: null;

        $complaint = Complaint::find($id);
        if (!$complaint || (int)$complaint['citizen_id'] !== Auth::id()) {
            flash_set('error', 'Complaint not found.');
            redirect('citizen/dashboard');
        }

        if (Complaint::reopen($id, Auth::id(), $reason)) {
            flash_set('success', 'Complaint reopened and sent back to the department.');
        } else {
            flash_set('error', 'This complaint can no longer be reopened.');
        }
        redirect("citizen/complaints/view?id=$id");
    }

    public function upvote(): void
    {
        Auth::requireRole('citizen');
        Csrf::requireValid();

        $complaintId = (int)($_POST['complaint_id'] ?? 0);
        $result = Complaint::upvote($complaintId, Auth::id());

        if ($result === true) {
            flash_set('success', 'Thanks -- upvoted. The department will see this is a widely-reported issue.');
        } elseif ($result === 'own_complaint') {
            flash_set('error', 'You already filed this complaint.');
        } elseif ($result === 'already_upvoted') {
            flash_set('error', 'You already upvoted this complaint.');
        } else {
            flash_set('error', 'That complaint is no longer open.');
        }
        redirect('citizen/dashboard');
    }

    public function commentStore(): void
    {
        Auth::requireRole('citizen');
        Csrf::requireValid();

        $id = (int)($_POST['id'] ?? 0);
        $body = trim($_POST['body'] ?? '');

        $complaint = Complaint::find($id);
        if (!$complaint || (int)$complaint['citizen_id'] !== Auth::id()) {
            flash_set('error', 'Complaint not found.');
            redirect('citizen/dashboard');
        }

        if ($body !== '') {
            Comment::create($id, Auth::id(), $body);
        }
        redirect("citizen/complaints/view?id=$id");
    }
}
