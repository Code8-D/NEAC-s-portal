<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/core/Auth.php';
require_once __DIR__ . '/../app/core/Security.php';
require_once __DIR__ . '/../app/models/Course.php';
require_once __DIR__ . '/../app/models/Classroom.php';

if (Auth::role() !== 'admin') {
    http_response_code(403);
    exit('Only admins can add courses.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php?section=classrooms');
    exit;
}

Security::requireCsrf();

if (trim($_POST['title'] ?? '') === '' || trim($_POST['code'] ?? '') === '') {
    header('Location: dashboard.php?section=courses&course=invalid');
    exit;
}

try {
    $courseId = Course::create($_POST);
    Classroom::createForCourse($courseId, (int) ($_POST['teacher_id'] ?? 0));
    header('Location: dashboard.php?section=courses&course=created');
} catch (PDOException $exception) {
    $status = str_contains($exception->getMessage(), 'UNIQUE') ? 'exists' : 'error';
    header('Location: dashboard.php?section=courses&course=' . $status);
}
exit;
