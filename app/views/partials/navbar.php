<?php
$role = Auth::role();
$navItems = [];
$navGroups = [];
$escalationCount = null;

if ($role === 'super_admin') {
    $navItems = [
        'admin/dashboard' => 'Dashboard',
    ];
    $navGroups = [
        'Complaints' => [
            'admin/complaints'       => 'All Complaints',
            'admin/escalations'      => 'Escalations',
            'admin/reports/officers' => 'Officer Report',
        ],
        'Setup' => [
            'admin/departments' => 'Departments',
            'admin/categories'  => 'Categories',
            'admin/wards'       => 'Wards',
            'admin/officers'    => 'Officers',
        ],
    ];
    $escalationCount = count(Complaint::escalations());
} elseif ($role === 'officer') {
    $navItems = [
        'officer/dashboard' => 'Dashboard',
        'officer/queue'     => 'Complaint Queue',
    ];
    $deptQueue = Complaint::forDepartment(Auth::departmentId());
    $escalationCount = count(array_filter($deptQueue, fn($c) => Complaint::isBreached($c)));
} elseif ($role === 'citizen') {
    $navItems = [
        'citizen/dashboard'       => 'My Complaints',
        'citizen/complaints/new'  => 'File a Complaint',
    ];
}
$currentRoute = $_GET['route'] ?? '';
?>
<nav class="navbar">
    <div class="navbar-brand">
        <span class="brand-mark">&#128737;</span> CivicFix
    </div>
    <ul class="navbar-links">
        <?php foreach ($navItems as $route => $label): ?>
            <li>
                <a href="/index.php?route=<?= e($route) ?>"
                   class="<?= $currentRoute === $route ? 'active' : '' ?>">
                    <?= e($label) ?>
                    <?php if ($route === 'admin/escalations' || $route === 'officer/queue'): ?>
                        <?php if ($escalationCount): ?><span class="nav-badge"><?= (int)$escalationCount ?></span><?php endif; ?>
                    <?php endif; ?>
                </a>
            </li>
        <?php endforeach; ?>
        <?php foreach ($navGroups as $groupLabel => $groupItems): ?>
            <?php $groupIsActive = array_key_exists($currentRoute, $groupItems); ?>
            <li>
                <details class="nav-dropdown" <?= $groupIsActive ? 'open' : '' ?>>
                    <summary class="<?= $groupIsActive ? 'active' : '' ?>">
                        <?= e($groupLabel) ?>
                        <?php if (array_key_exists('admin/escalations', $groupItems) && $escalationCount): ?>
                            <span class="nav-badge"><?= (int)$escalationCount ?></span>
                        <?php endif; ?>
                    </summary>
                    <div class="nav-dropdown-menu">
                        <?php foreach ($groupItems as $route => $label): ?>
                            <a href="/index.php?route=<?= e($route) ?>" class="<?= $currentRoute === $route ? 'active' : '' ?>">
                                <?= e($label) ?>
                                <?php if ($route === 'admin/escalations' && $escalationCount): ?>
                                    <span class="nav-badge"><?= (int)$escalationCount ?></span>
                                <?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </details>
            </li>
        <?php endforeach; ?>
        <li><a href="/index.php?route=stats" target="_blank" rel="noopener">Public Dashboard &#8599;</a></li>
    </ul>
    <div class="navbar-user">
        <span><?= e(Auth::name()) ?></span>
        <span class="user-role"><?= e(str_replace('_', ' ', $role)) ?></span>
        <form method="POST" action="/index.php?route=logout" class="inline-form">
            <?= Csrf::field() ?>
            <button type="submit" class="btn btn-ghost btn-sm">Logout</button>
        </form>
    </div>
</nav>
