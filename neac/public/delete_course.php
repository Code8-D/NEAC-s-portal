<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/core/Auth.php';
require_once __DIR__ . '/../app/core/Security.php';
require_once __DIR__ . '/../app/models/Course.php';

if (Auth::role() !== 'admin') {
    http_response_code(403);
    exit('Only admins can delete courses.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php?section=courses');
    exit;
}

Security::requireCsrf();

$courseId = (int) ($_POST['id'] ?? 0);
if ($courseId <= 0) {
    header('Location: dashboard.php?section=courses&course=invalid');
    exit;
}

if (!Course::find($courseId)) {
    header('Location: dashboard.php?section=courses&course=not_found');
    exit;
}

try {
    Course::delete($courseId);
    header('Location: dashboard.php?section=courses&course=deleted');
} catch (PDOException $exception) {
    header('Location: dashboard.php?section=courses&course=error');
}
exit;
