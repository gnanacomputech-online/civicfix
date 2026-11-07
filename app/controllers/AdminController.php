<?php

require_once __DIR__ . '/../models/Department.php';
require_once __DIR__ . '/../models/Category.php';
require_once __DIR__ . '/../models/Ward.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Complaint.php';
require_once __DIR__ . '/../models/Comment.php';

class AdminController
{
    public function dashboard(): void
    {
        Auth::requireRole('super_admin');

        $counts = Complaint::countsByStatus();
        $escalations = Complaint::escalations();
        $stats = [
            'total'        => array_sum($counts),
            'open'         => $counts['new'] + $counts['assigned'] + $counts['in_progress'],
            'resolved'     => $counts['resolved'],
            'closed'       => $counts['closed'],
            'breached'     => count($escalations),
            'departments'  => count(Department::all()),
            'officers'     => User::countByRole('officer'),
            'citizens'     => User::countByRole('citizen'),
        ];
        $recentEscalations = array_slice($escalations, 0, 5);

        require __DIR__ . '/../views/admin/dashboard.php';
    }

    // ---------------- Departments ----------------

    public function departments(): void
    {
        Auth::requireRole('super_admin');
        $departments = Department::all();
        $errors = [];
        $old = [];
        require __DIR__ . '/../views/admin/departments.php';
    }

    public function departmentStore(): void
    {
        Auth::requireRole('super_admin');
        Csrf::requireValid();

        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '') ?: null;

        $v = new Validator();
        $v->required($_POST, 'name', 'Name')->maxLength($_POST, 'name', 120, 'Name');
        $errors = $v->errors();
        if (empty($errors) && Department::nameExists($name)) {
            $errors['name'] = 'A department with this name already exists.';
        }

        if (!empty($errors)) {
            $old = $_POST;
            $departments = Department::all();
            require __DIR__ . '/../views/admin/departments.php';
            return;
        }

        Department::create($name, $description);
        flash_set('success', "Department \"$name\" added.");
        redirect('admin/departments');
    }

    public function departmentUpdate(): void
    {
        Auth::requireRole('super_admin');
        Csrf::requireValid();

        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '') ?: null;

        if (!Department::find($id) || $name === '') {
            flash_set('error', 'Invalid department details.');
            redirect('admin/departments');
        }
        if (Department::nameExists($name, $id)) {
            flash_set('error', 'Another department already uses that name.');
            redirect('admin/departments');
        }

        Department::update($id, $name, $description);
        flash_set('success', 'Department updated.');
        redirect('admin/departments');
    }

    public function departmentDelete(): void
    {
        Auth::requireRole('super_admin');
        Csrf::requireValid();

        $id = (int)($_POST['id'] ?? 0);
        if (Department::delete($id)) {
            flash_set('success', 'Department removed.');
        } else {
            flash_set('error', 'This department still has categories or officer accounts and cannot be removed.');
        }
        redirect('admin/departments');
    }

    // ---------------- Categories ----------------

    public function categories(): void
    {
        Auth::requireRole('super_admin');
        $categories = Category::all();
        $departments = Department::all();
        $errors = [];
        $old = [];
        require __DIR__ . '/../views/admin/categories.php';
    }

    public function categoryStore(): void
    {
        Auth::requireRole('super_admin');
        Csrf::requireValid();

        $name = trim($_POST['name'] ?? '');
        $departmentId = (int)($_POST['department_id'] ?? 0);
        $slaHours = (int)($_POST['sla_hours'] ?? 0);

        $v = new Validator();
        $v->required($_POST, 'name', 'Name')->maxLength($_POST, 'name', 150, 'Name');
        $errors = $v->errors();
        if (!Department::find($departmentId)) {
            $errors['department_id'] = 'Please choose a valid department.';
        }
        if ($slaHours < 1 || $slaHours > 2160) {
            $errors['sla_hours'] = 'SLA hours must be between 1 and 2160 (90 days).';
        }

        if (!empty($errors)) {
            $old = $_POST;
            $categories = Category::all();
            $departments = Department::all();
            require __DIR__ . '/../views/admin/categories.php';
            return;
        }

        Category::create($name, $departmentId, $slaHours);
        flash_set('success', "Category \"$name\" added.");
        redirect('admin/categories');
    }

    public function categoryUpdate(): void
    {
        Auth::requireRole('super_admin');
        Csrf::requireValid();

        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $departmentId = (int)($_POST['department_id'] ?? 0);
        $slaHours = (int)($_POST['sla_hours'] ?? 0);

        if (!Category::find($id) || $name === '' || !Department::find($departmentId) || $slaHours < 1) {
            flash_set('error', 'Invalid category details.');
            redirect('admin/categories');
        }

        Category::update($id, $name, $departmentId, $slaHours);
        flash_set('success', 'Category updated.');
        redirect('admin/categories');
    }

    public function categoryDelete(): void
    {
        Auth::requireRole('super_admin');
        Csrf::requireValid();

        $id = (int)($_POST['id'] ?? 0);
        if (Category::delete($id)) {
            flash_set('success', 'Category removed.');
        } else {
            flash_set('error', 'This category has complaints filed against it and cannot be removed.');
        }
        redirect('admin/categories');
    }

    // ---------------- Wards ----------------

    public function wards(): void
    {
        Auth::requireRole('super_admin');
        $wards = Ward::all();
        $errors = [];
        $old = [];
        require __DIR__ . '/../views/admin/wards.php';
    }

    public function wardStore(): void
    {
        Auth::requireRole('super_admin');
        Csrf::requireValid();

        $name = trim($_POST['name'] ?? '');
        $v = new Validator();
        $v->required($_POST, 'name', 'Name')->maxLength($_POST, 'name', 100, 'Name');
        $errors = $v->errors();
        if (empty($errors) && Ward::nameExists($name)) {
            $errors['name'] = 'A ward with this name already exists.';
        }

        if (!empty($errors)) {
            $old = $_POST;
            $wards = Ward::all();
            require __DIR__ . '/../views/admin/wards.php';
            return;
        }

        Ward::create($name);
        flash_set('success', "Ward \"$name\" added.");
        redirect('admin/wards');
    }

    public function wardUpdate(): void
    {
        Auth::requireRole('super_admin');
        Csrf::requireValid();

        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');

        if (!Ward::find($id) || $name === '') {
            flash_set('error', 'Invalid ward details.');
            redirect('admin/wards');
        }
        if (Ward::nameExists($name, $id)) {
            flash_set('error', 'Another ward already uses that name.');
            redirect('admin/wards');
        }

        Ward::update($id, $name);
        flash_set('success', 'Ward updated.');
        redirect('admin/wards');
    }

    public function wardDelete(): void
    {
        Auth::requireRole('super_admin');
        Csrf::requireValid();

        $id = (int)($_POST['id'] ?? 0);
        if (Ward::delete($id)) {
            flash_set('success', 'Ward removed.');
        } else {
            flash_set('error', 'This ward has complaints filed against it and cannot be removed.');
        }
        redirect('admin/wards');
    }

    // ---------------- Officer accounts ----------------

    public function officers(): void
    {
        Auth::requireRole('super_admin');
        $officers = User::allByRole('officer');
        $departments = Department::all();
        $errors = [];
        $old = [];
        require __DIR__ . '/../views/admin/officers.php';
    }

    public function officerStore(): void
    {
        Auth::requireRole('super_admin');
        Csrf::requireValid();

        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $departmentId = (int)($_POST['department_id'] ?? 0);
        $password = (string)($_POST['password'] ?? '');

        $v = new Validator();
        $v->required($_POST, 'name', 'Name')->maxLength($_POST, 'name', 100, 'Name')
          ->required($_POST, 'email', 'Email')->email($_POST, 'email', 'Email')
          ->required($_POST, 'password', 'Password')->minLength($_POST, 'password', 6, 'Password');

        $errors = $v->errors();
        if (empty($errors['email']) && User::emailExists($email)) {
            $errors['email'] = 'This email is already registered.';
        }
        if (!Department::find($departmentId)) {
            $errors['department_id'] = 'Please choose a valid department.';
        }

        if (!empty($errors)) {
            $old = $_POST;
            $officers = User::allByRole('officer');
            $departments = Department::all();
            require __DIR__ . '/../views/admin/officers.php';
            return;
        }

        User::create($name, $email, $password, 'officer', $phone ?: null, $departmentId);
        flash_set('success', "Officer account created for $name.");
        redirect('admin/officers');
    }

    public function officerStatus(): void
    {
        Auth::requireRole('super_admin');
        Csrf::requireValid();

        $userId = (int)($_POST['user_id'] ?? 0);
        $status = $_POST['status'] === 'active' ? 'active' : 'inactive';

        if (User::setStatus($userId, $status, 'officer')) {
            flash_set('success', 'Officer status updated.');
        } else {
            flash_set('error', 'Officer not found.');
        }
        redirect('admin/officers');
    }

    // ---------------- Complaints oversight ----------------

    public function complaints(): void
    {
        Auth::requireRole('super_admin');

        $departmentId = (int)($_GET['department_id'] ?? 0) ?: null;
        $wardId = (int)($_GET['ward_id'] ?? 0) ?: null;
        $status = trim($_GET['status'] ?? '') ?: null;

        $complaints = Complaint::search($departmentId, $wardId, $status);
        $departments = Department::all();
        $wardsList = Ward::all();

        require __DIR__ . '/../views/admin/complaints.php';
    }

    public function complaintView(): void
    {
        Auth::requireRole('super_admin');

        $id = (int)($_GET['id'] ?? 0);
        $complaint = Complaint::find($id);
        if (!$complaint) {
            http_response_code(404);
            require __DIR__ . '/../views/errors/404.php';
            return;
        }
        $history = Complaint::statusHistory($id);
        $comments = Comment::forComplaint($id);
        $commentRoute = 'admin/complaints/comment';

        require __DIR__ . '/../views/admin/complaint_detail.php';
    }

    public function commentStore(): void
    {
        Auth::requireRole('super_admin');
        Csrf::requireValid();

        $id = (int)($_POST['id'] ?? 0);
        $body = trim($_POST['body'] ?? '');

        if (!Complaint::find($id)) {
            flash_set('error', 'Complaint not found.');
            redirect('admin/complaints');
        }

        if ($body !== '') {
            Comment::create($id, Auth::id(), $body);
        }
        redirect("admin/complaints/view?id=$id");
    }

    public function forceCloseComplaint(): void
    {
        Auth::requireRole('super_admin');
        Csrf::requireValid();

        $id = (int)($_POST['id'] ?? 0);
        if (Complaint::adminClose($id, Auth::id())) {
            flash_set('success', 'Complaint closed.');
        } else {
            flash_set('error', 'Could not close this complaint -- it may no longer be Resolved.');
        }
        redirect("admin/complaints/view?id=$id");
    }

    public function officerPerformance(): void
    {
        Auth::requireRole('super_admin');
        $officers = Complaint::officerPerformance();
        require __DIR__ . '/../views/admin/officer_report.php';
    }

    public function escalations(): void
    {
        Auth::requireRole('super_admin');
        $escalations = Complaint::escalations();
        require __DIR__ . '/../views/admin/escalations.php';
    }
}
