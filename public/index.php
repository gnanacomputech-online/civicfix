<?php

declare(strict_types=1);

// Let PHP's built-in dev server (`php -S`) serve real static files directly
// instead of routing every asset request through the front controller.
if (PHP_SAPI === 'cli-server') {
    $requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $assetPath = __DIR__ . $requestPath;
    if (!str_ends_with($requestPath, '.php') && is_file($assetPath)) {
        return false;
    }
}

date_default_timezone_set('Asia/Kolkata');
ini_set('display_errors', '1');
error_reporting(E_ALL);

session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
]);

require_once __DIR__ . '/../vendor/autoload.php';

require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Csrf.php';
require_once __DIR__ . '/../core/Validator.php';
require_once __DIR__ . '/../core/Router.php';
require_once __DIR__ . '/../core/Mailer.php';

require_once __DIR__ . '/../app/models/Complaint.php';

require_once __DIR__ . '/../app/controllers/AuthController.php';
require_once __DIR__ . '/../app/controllers/AdminController.php';
require_once __DIR__ . '/../app/controllers/OfficerController.php';
require_once __DIR__ . '/../app/controllers/CitizenController.php';
require_once __DIR__ . '/../app/controllers/PublicController.php';

$router = new Router();

// ---- Public (no login) ----
$router->get('stats', fn() => (new PublicController())->dashboard());

// ---- Auth ----
$router->get('login', fn() => (new AuthController())->showLogin());
$router->post('login', fn() => (new AuthController())->login());
$router->post('logout', fn() => (new AuthController())->logout());
$router->get('register', fn() => (new AuthController())->showRegister());
$router->post('register', fn() => (new AuthController())->register());

// ---- Super Admin ----
$router->get('admin/dashboard', fn() => (new AdminController())->dashboard());
$router->get('admin/departments', fn() => (new AdminController())->departments());
$router->post('admin/departments/store', fn() => (new AdminController())->departmentStore());
$router->post('admin/departments/update', fn() => (new AdminController())->departmentUpdate());
$router->post('admin/departments/delete', fn() => (new AdminController())->departmentDelete());
$router->get('admin/categories', fn() => (new AdminController())->categories());
$router->post('admin/categories/store', fn() => (new AdminController())->categoryStore());
$router->post('admin/categories/update', fn() => (new AdminController())->categoryUpdate());
$router->post('admin/categories/delete', fn() => (new AdminController())->categoryDelete());
$router->get('admin/wards', fn() => (new AdminController())->wards());
$router->post('admin/wards/store', fn() => (new AdminController())->wardStore());
$router->post('admin/wards/update', fn() => (new AdminController())->wardUpdate());
$router->post('admin/wards/delete', fn() => (new AdminController())->wardDelete());
$router->get('admin/officers', fn() => (new AdminController())->officers());
$router->post('admin/officers/store', fn() => (new AdminController())->officerStore());
$router->post('admin/officers/status', fn() => (new AdminController())->officerStatus());
$router->get('admin/complaints', fn() => (new AdminController())->complaints());
$router->get('admin/complaints/view', fn() => (new AdminController())->complaintView());
$router->post('admin/complaints/comment', fn() => (new AdminController())->commentStore());
$router->post('admin/complaints/force-close', fn() => (new AdminController())->forceCloseComplaint());
$router->get('admin/escalations', fn() => (new AdminController())->escalations());
$router->get('admin/reports/officers', fn() => (new AdminController())->officerPerformance());

// ---- Department Officer ----
$router->get('officer/dashboard', fn() => (new OfficerController())->dashboard());
$router->get('officer/queue', fn() => (new OfficerController())->queue());
$router->get('officer/complaints/view', fn() => (new OfficerController())->complaintView());
$router->post('officer/complaints/claim', fn() => (new OfficerController())->claim());
$router->post('officer/complaints/status', fn() => (new OfficerController())->updateStatus());
$router->post('officer/complaints/comment', fn() => (new OfficerController())->commentStore());

// ---- Citizen ----
$router->get('citizen/dashboard', fn() => (new CitizenController())->dashboard());
$router->get('citizen/complaints/new', fn() => (new CitizenController())->newForm());
$router->post('citizen/complaints/store', fn() => (new CitizenController())->store());
$router->get('citizen/complaints/view', fn() => (new CitizenController())->complaintView());
$router->post('citizen/complaints/rate', fn() => (new CitizenController())->rateComplaint());
$router->post('citizen/complaints/reopen', fn() => (new CitizenController())->reopenComplaint());
$router->post('citizen/complaints/upvote', fn() => (new CitizenController())->upvote());
$router->post('citizen/complaints/comment', fn() => (new CitizenController())->commentStore());

$route = $_GET['route'] ?? '';

if ($route === '') {
    if (Auth::check()) {
        redirect(Auth::dashboardRoute(Auth::role()));
    }
    redirect('login');
}

$router->dispatch($_SERVER['REQUEST_METHOD'], $route);
