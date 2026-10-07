<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/core/Auth.php';
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/core/Security.php';
require_once __DIR__ . '/../app/models/Course.php';
require_once __DIR__ . '/../app/models/Student.php';
require_once __DIR__ . '/../app/models/Classroom.php';
require_once __DIR__ . '/../app/models/Setting.php';

if (!Auth::check()) {
    header('Location: signup.php');
    exit;
}

$role = Auth::role();
$userId = (int) Auth::userId();
$courses = Course::all();
$students = Student::all();
$lecturers = [];
$programs = Database::programs();
$classrooms = Classroom::forUser($userId, $role);
$certBatch = (string) (Setting::get('cert_batch_label') ?? '4th Batch');

$adminStats = [
    'students' => count($students),
    'courses' => count($courses),
    'lecturers' => 0,
    'classrooms' => is_array($classrooms) ? count($classrooms) : 0,
    'alerts' => 0,
];
$reportStats = [
    'courses' => count($courses),
    'students' => count($students),
    'lecturers' => 0,
    'classrooms' => $adminStats['classrooms'],
    'files' => 0,
];

$pageTitle = ucfirst($role) . ' Dashboard';
$hideTopbar = true;
$allowedSections = ['overview', 'classrooms', 'courses', 'students', 'lecturers', 'assignments', 'materials', 'reports', 'applications', 'payments', 'grading', 'certification'];
if ($role === 'admin') {
    $allowedSections = array_merge($allowedSections, ['applications', 'payments', 'grading', 'momo', 'home']);
}
$section = $_GET['section'] ?? 'overview';
if (!in_array($section, $allowedSections, true)) {
    $section = 'overview';
}

if ($role === 'admin' && $section === 'applications' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../app/models/Setting.php';
    Security::requireCsrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'save_cert_period') {
        $batch = trim((string) ($_POST['cert_batch_label'] ?? ''));
        $opens = trim((string) ($_POST['cert_opens_at'] ?? ''));
        $closes = trim((string) ($_POST['cert_closes_at'] ?? ''));
        if ($batch === '') {
            $batch = '4th Batch';
        }
        foreach (['cert_opens_at' => $opens, 'cert_closes_at' => $closes] as $key => $val) {
            if ($val !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $val)) {
                $val = '';
            }
            Setting::set($key, $val !== '' ? $val : null);
        }
        Setting::set('cert_batch_label', $batch);
        Setting::set('cert_applications_open', isset($_POST['cert_applications_open']) ? '1' : '0');
        header('Location: ' . BASE_URL . '/dashboard.php?section=applications&period_saved=1');
        exit;
    }
    header('Location: ' . BASE_URL . '/dashboard.php?section=applications');
    exit;
}

if ($role === 'admin' && $section === 'home' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../app/models/HomePost.php';
    Security::requireCsrf();
    $action = $_POST['action'] ?? '';
    $postId = (int) ($_POST['id'] ?? 0);
    $type = in_array($_POST['type'] ?? '', HomePost::TYPES, true) ? $_POST['type'] : 'announcement';
    $title = trim((string) ($_POST['title'] ?? ''));
    $body = trim((string) ($_POST['body'] ?? ''));
    $eventDate = $type === 'event' ? trim((string) ($_POST['event_date'] ?? '')) : '';
    if ($eventDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $eventDate)) {
        $eventDate = '';
    }

    $imagePath = null;
    if (!empty($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['image'];
        $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
        [$ok, $msg] = Security::uploadIsAllowed(
            $file,
            ['jpg', 'jpeg', 'png', 'gif', 'webp'],
            ['image/'],
            5 * 1024 * 1024
        );
        if (!$ok) {
            header('Location: ' . BASE_URL . '/dashboard.php?section=home&error=' . urlencode($msg));
            exit;
        }
        $uploadRoot = __DIR__ . '/../storage/uploads/home_posts';
        if (!is_dir($uploadRoot)) {
            mkdir($uploadRoot, 0775, true);
        }
        $storedName = Security::safeStorageName($file['name'], $ext);
        if (move_uploaded_file($file['tmp_name'], $uploadRoot . '/' . $storedName)) {
            $imagePath = 'storage/uploads/home_posts/' . $storedName;
        }
    }
    if ($action === 'create' && $title !== '' && $body !== '') {
        HomePost::create($type, $title, $body, $eventDate !== '' ? $eventDate : null, $imagePath, $userId);
        header('Location: ' . BASE_URL . '/dashboard.php?section=home&saved=1');
        exit;
    }
    if ($action === 'update' && $postId > 0 && $title !== '' && $body !== '') {
        $published = isset($_POST['is_published']);
        $existing = HomePost::find($postId);
        if ($imagePath === null) {
            $imagePath = $existing['image_path'] ?? null;
            if (isset($_POST['remove_image']) && $existing['image_path']) {
                @unlink(__DIR__ . '/../' . $existing['image_path']);
                $imagePath = null;
            }
        } elseif ($existing && $existing['image_path']) {
            @unlink(__DIR__ . '/../' . $existing['image_path']);
        }
        HomePost::update($postId, $type, $title, $body, $eventDate !== '' ? $eventDate : null, $imagePath, $published);
        header('Location: ' . BASE_URL . '/dashboard.php?section=home&saved=1');
        exit;
    }
    if ($action === 'delete' && $postId > 0) {
        $existing = HomePost::find($postId);
        if ($existing && $existing['image_path']) {
            @unlink(__DIR__ . '/../' . $existing['image_path']);
        }
        HomePost::delete($postId);
        header('Location: ' . BASE_URL . '/dashboard.php?section=home&deleted=1');
        exit;
    }
    header('Location: ' . BASE_URL . '/dashboard.php?section=home');
    exit;
}

if ($role === 'admin') {
    Database::migrate();
    $lecturers = Database::connection()
        ->query('SELECT users.id, teachers.id AS teacher_id, users.full_name, users.email, users.status, teachers.title, teachers.phone, teachers.speciality, teachers.created_at,
                        COALESCE((SELECT GROUP_CONCAT(courses.code, ", ") FROM courses WHERE courses.teacher_id = teachers.id), "—") AS assigned_courses
                 FROM users
                 INNER JOIN teachers ON teachers.user_id = users.id
                 WHERE users.role = "teacher"
                 ORDER BY users.id DESC')
        ->fetchAll();
    $adminStats['lecturers'] = count($lecturers);
    $reportStats['lecturers'] = count($lecturers);
}

$reportStats['files'] = (int) Database::connection()->query('SELECT COUNT(*) FROM files')->fetchColumn();

require __DIR__ . '/../views/dashboard/' . $role . '.view.php';
