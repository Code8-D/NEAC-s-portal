<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/core/Auth.php';
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/core/Security.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::requireCsrf();
}

$role = Auth::role();
$uploadType = $_POST['upload_type'] ?? '';
$allowedTypes = [
    'teaching_material' => ['teacher', 'admin'],
    'assignment' => ['student'],
    'classwork' => ['teacher', 'admin'],
    'submission' => ['student'],
];

if (!isset($allowedTypes[$uploadType]) || !in_array($role, $allowedTypes[$uploadType], true)) {
    http_response_code(403);
    exit('Upload not allowed.');
}

if (empty($_FILES['upload_file']) || $_FILES['upload_file']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    exit('No valid file was uploaded.');
}

$file = $_FILES['upload_file'];
$maxSize = 80 * 1024 * 1024;
$extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$allowedExtensions = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'txt', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'zip', 'mp4', 'mov', 'avi', 'mkv'];
$allowedMimePrefixes = [
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument',
    'application/vnd.ms-',
    'application/zip',
    'image/',
    'text/',
    'video/',
];

[$ok, $message] = Security::uploadIsAllowed($file, $allowedExtensions, $allowedMimePrefixes, $maxSize);
if (!$ok) {
    http_response_code(400);
    exit($message);
}

$uploadRoot = __DIR__ . '/../storage/uploads/' . $uploadType;
if (!is_dir($uploadRoot)) {
    mkdir($uploadRoot, 0775, true);
}

$storedName = Security::safeStorageName($file['name'], $extension);
$targetPath = $uploadRoot . '/' . $storedName;

if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
    http_response_code(500);
    exit('Upload could not be saved.');
}

Database::migrate();
$relativePath = 'storage/uploads/' . $uploadType . '/' . $storedName;
$mime = Security::detectMime($targetPath);

$metadata = [
    'title' => trim($_POST['title'] ?? ''),
    'course' => trim($_POST['course'] ?? ''),
    'description' => trim($_POST['description'] ?? ''),
    'material_type' => trim($_POST['material_type'] ?? ''),
    'visibility' => trim($_POST['visibility'] ?? ''),
    'classwork_id' => (int) ($_POST['classwork_id'] ?? 0),
    'return_to' => trim($_POST['return_to'] ?? 'dashboard.php'),
];

$safeLocalPages = ['dashboard.php', 'classroom.php', 'lessons.php', 'quizzes.php', 'results.php', 'recommendation.php', 'payment.php', 'transcript.php'];
$returnTo = $metadata['return_to'];
if ($returnTo === '' || str_contains($returnTo, '://') || str_starts_with($returnTo, '//')) {
    $returnTo = 'dashboard.php';
} else {
    $returnPage = parse_url($returnTo, PHP_URL_PATH);
    $returnPage = str_replace('\\', '/', (string) $returnPage);
    $returnPage = ltrim($returnPage, '/');
    $returnBase = strtok($returnPage, '/');
    if (!in_array($returnBase, $safeLocalPages, true)) {
        $returnTo = 'dashboard.php';
    }
}
$metadata['return_to'] = $returnTo;

$statement = Database::connection()->prepare(
    'INSERT INTO files (uploaded_by, original_name, stored_path, mime_type, file_size, upload_context)
     VALUES (:uploaded_by, :original_name, :stored_path, :mime_type, :file_size, :upload_context)'
);
$statement->execute([
    'uploaded_by' => Auth::userId(),
    'original_name' => substr($file['name'], 0, 255),
    'stored_path' => $relativePath,
    'mime_type' => $mime,
    'file_size' => (int) $file['size'],
    'upload_context' => $uploadType . ':' . json_encode($metadata, JSON_UNESCAPED_UNICODE),
]);

$fileId = (int) Database::connection()->lastInsertId();

if ($uploadType === 'submission' && !empty($metadata['classwork_id']) && Auth::studentId()) {
    $cw = Database::connection()->prepare('SELECT classroom_id FROM classroom_classwork WHERE id = :id LIMIT 1');
    $cw->execute(['id' => (int) $metadata['classwork_id']]);
    $roomId = (int) ($cw->fetchColumn() ?: 0);
    require_once __DIR__ . '/../app/models/Classroom.php';
    if ($roomId <= 0 || !Classroom::isMember($roomId, (int) Auth::userId())) {
        http_response_code(403);
        exit('You are not a member of this classroom.');
    }
    $stmt = Database::connection()->prepare(
        'INSERT INTO classroom_submissions (classwork_id, student_id, file_id, note, status)
         VALUES (:cid, :sid, :fid, :note, "submitted")
         ON CONFLICT(classwork_id, student_id) DO UPDATE SET
            file_id = excluded.file_id,
            note = excluded.note,
            submitted_at = CURRENT_TIMESTAMP,
            status = "submitted"'
    );
    $stmt->execute([
        'cid' => (int) $metadata['classwork_id'],
        'sid' => (int) Auth::studentId(),
        'fid' => $fileId,
        'note' => $metadata['description'],
    ]);
    header('Location: ' . $metadata['return_to']);
    exit;
}

if ($uploadType === 'classwork' && !empty($metadata['classwork_id'])) {
    $cw = Database::connection()->prepare('SELECT classroom_id FROM classroom_classwork WHERE id = :id LIMIT 1');
    $cw->execute(['id' => (int) $metadata['classwork_id']]);
    $roomId = (int) ($cw->fetchColumn() ?: 0);
    require_once __DIR__ . '/../app/models/Classroom.php';
    if ($roomId <= 0 || !Classroom::isTeacher($roomId, (int) Auth::userId())) {
        http_response_code(403);
        exit('Only the teacher can attach materials to this classwork.');
    }
    $stmt = Database::connection()->prepare(
        'INSERT INTO classroom_classwork_attachments (classwork_id, file_id, sort_order)
         VALUES (:cid, :fid, (SELECT COALESCE(MAX(sort_order), 0) + 1 FROM classroom_classwork_attachments WHERE classwork_id = :cid2))'
    );
    $stmt->execute([
        'cid' => (int) $metadata['classwork_id'],
        'cid2' => (int) $metadata['classwork_id'],
        'fid' => $fileId,
    ]);
    header('Location: ' . $metadata['return_to']);
    exit;
}

header('Location: dashboard.php?upload=success');
exit;
