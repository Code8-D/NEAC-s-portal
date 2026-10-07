<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/core/Auth.php';
require_once __DIR__ . '/../app/core/Security.php';
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/models/Course.php';
require_once __DIR__ . '/../app/models/Classroom.php';

if (Auth::role() !== 'admin') {
    http_response_code(403);
    exit('Only admins can add lecturers.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php?section=lecturers');
    exit;
}

Security::requireCsrf();

$password = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';
$courseIds = $_POST['course_ids'] ?? [];
if (!is_array($courseIds)) {
    $courseIds = [$courseIds];
}
$courseIds = array_values(array_unique(array_filter(array_map('intval', $courseIds), fn($id) => $id > 0)));

if ($password !== $confirmPassword) {
    header('Location: dashboard.php?section=lecturers&teacher=invalid');
    exit;
}

try {
    $teacherId = Auth::createTeacher($_POST);
    if ($teacherId > 0 && !empty($courseIds)) {
        $db = Database::connection();
        $userStmt = $db->prepare('SELECT user_id FROM teachers WHERE id = :id LIMIT 1');
        $userStmt->execute(['id' => $teacherId]);
        $teacherUserId = (int) ($userStmt->fetchColumn() ?: 0);

        $updateStmt = $db->prepare('UPDATE courses SET teacher_id = :tid WHERE id = :cid');
        $roomStmt = $db->prepare('SELECT id FROM classrooms WHERE course_id = :cid LIMIT 1');
        foreach ($courseIds as $cid) {
            $updateStmt->execute(['tid' => $teacherId, 'cid' => $cid]);
            if ($updateStmt->rowCount() === 0) {
                continue;
            }
            $roomStmt->execute(['cid' => $cid]);
            $roomId = (int) ($roomStmt->fetchColumn() ?: 0);
            if ($roomId <= 0) {
                $roomId = Classroom::createForCourse($cid, $teacherId);
            } elseif ($teacherUserId > 0) {
                Classroom::addMember($roomId, $teacherUserId, 'teacher');
            }
        }
    }
    header('Location: dashboard.php?section=lecturers&teacher=created');
} catch (InvalidArgumentException $exception) {
    header('Location: dashboard.php?section=lecturers&teacher=invalid');
} catch (PDOException $exception) {
    $status = str_contains($exception->getMessage(), 'UNIQUE') ? 'exists' : 'error';
    header('Location: dashboard.php?section=lecturers&teacher=' . $status);
}
exit;
