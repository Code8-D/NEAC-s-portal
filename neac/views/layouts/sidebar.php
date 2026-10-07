<?php
$role = $role ?? '';
$currentSection = $section ?? 'overview';
$sidebarItems = [
    ['key' => 'overview', 'label' => 'Overview', 'icon' => 'fa-table-columns'],
    ['key' => 'classrooms', 'label' => 'Classrooms', 'icon' => 'fa-school'],
];

if ($role === 'student') {
    $sidebarItems[] = ['key' => 'certification', 'label' => 'Certification', 'icon' => 'fa-certificate'];
    $sidebarItems[] = ['key' => 'assignments', 'label' => 'My assignments', 'icon' => 'fa-file-pen'];
} elseif ($role === 'admin') {
    $sidebarItems[] = ['key' => 'courses', 'label' => 'Courses', 'icon' => 'fa-book'];
    $sidebarItems[] = ['key' => 'materials', 'label' => 'Materials', 'icon' => 'fa-cloud-arrow-up'];
    $sidebarItems[] = ['key' => 'applications', 'label' => 'Applications', 'icon' => 'fa-file-lines'];
    $sidebarItems[] = ['key' => 'payments', 'label' => 'Payments', 'icon' => 'fa-credit-card'];
    $sidebarItems[] = ['key' => 'students', 'label' => 'Students', 'icon' => 'fa-users'];
    $sidebarItems[] = ['key' => 'lecturers', 'label' => 'Lecturers', 'icon' => 'fa-person-chalkboard'];
    $sidebarItems[] = ['key' => 'grading', 'label' => 'Grading', 'icon' => 'fa-chart-simple'];
    $sidebarItems[] = ['key' => 'home', 'label' => 'Home page posts', 'icon' => 'fa-bullhorn'];
} else {
    $sidebarItems[] = ['key' => 'materials', 'label' => 'Materials', 'icon' => 'fa-cloud-arrow-up'];
    $sidebarItems[] = ['key' => 'students', 'label' => 'Students', 'icon' => 'fa-users'];
    $sidebarItems[] = ['key' => 'grading', 'label' => 'Grading', 'icon' => 'fa-chart-simple'];
}

$sidebarItems[] = ['key' => 'reports', 'label' => 'Reports', 'icon' => 'fa-chart-line'];

$unread = 0;
if (!empty($_SESSION['user_id'])) {
    try {
        require_once __DIR__ . '/../../app/models/Notification.php';
        $unread = Notification::unreadCount((int) $_SESSION['user_id']);
    } catch (Throwable $e) {}
}
?>

<button type="button" class="sidebar-toggle" id="sidebar-toggle" aria-controls="app-sidebar" aria-expanded="false" aria-label="Open menu">
    <i class="fa-solid fa-bars"></i>
</button>
<div class="sidebar-backdrop" id="sidebar-backdrop" hidden></div>
<aside class="sidebar" id="app-sidebar">
    <div class="sidebar__head">
        <a class="sidebar__brand" href="<?= BASE_URL ?>/index.php">
            <img src="<?= LOGO_IMAGE ?>" alt="NEAC logo">
            <span><b>NEAC</b><small>Portal</small></span>
        </a>
        <button type="button" class="sidebar__close" id="sidebar-close" aria-label="Close menu"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <nav class="sidebar__nav">
        <?php foreach ($sidebarItems as $item): ?>
            <a href="<?= BASE_URL ?>/dashboard.php?section=<?= e($item['key']) ?>" class="<?= $currentSection === $item['key'] ? 'active' : '' ?>">
                <i class="fa-solid <?= e($item['icon']) ?>"></i><span><?= e($item['label']) ?></span>
            </a>
        <?php endforeach; ?>
        <a href="<?= BASE_URL ?>/quizzes.php"><i class="fa-solid fa-pen-ruler"></i><span>Quizzes</span></a>
        <a href="<?= BASE_URL ?>/lessons.php"><i class="fa-solid fa-book-open"></i><span>Lesson planner</span></a>
        <a href="<?= BASE_URL ?>/discussions.php"><i class="fa-solid fa-comments"></i><span>Discussions</span></a>
        <a href="<?= BASE_URL ?>/results.php"><i class="fa-solid fa-chart-line"></i><span>Results & gradebook</span></a>
        <a href="<?= BASE_URL ?>/classroom.php?action=join"><i class="fa-solid fa-keyboard"></i><span>Join class</span></a>
        <a href="<?= BASE_URL ?>/notifications.php" style="position:relative">
            <i class="fa-solid fa-bell"></i><span>Notifications</span>
            <?php if ($unread > 0): ?>
                <span style="position:absolute;right:10px;background:#d9a321;color:#fff;font-size:11px;font-weight:700;min-width:20px;height:20px;display:flex;align-items:center;justify-content:center;border-radius:999px"><?= (int) $unread ?></span>
            <?php endif; ?>
        </a>
    </nav>
    <form method="post" action="<?= BASE_URL ?>/logout.php" class="sidebar-logout">
        <?= Security::csrfField() ?>
        <button type="submit"><i class="fa-solid fa-arrow-right-from-bracket"></i><span>Logout</span></button>
    </form>
</aside>
