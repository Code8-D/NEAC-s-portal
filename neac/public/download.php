<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/core/Auth.php';
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/models/Classroom.php';

if (!Auth::check()) {
    http_response_code(403);
    exit('Authentication required.');
}

$db = Database::connection();
$userId = (int) Auth::userId();
$isAdmin = Auth::isAdmin();

$storageRoot = realpath(__DIR__ . '/../storage/uploads');
if ($storageRoot === false) {
    http_response_code(404);
    exit('File not found.');
}

function resolveStoredPath(string $rel, string $storageRoot): ?string
{
    $rel = str_replace('\\', '/', trim($rel));
    $rel = ltrim($rel, '/');
    if (strpos($rel, 'storage/uploads/') !== 0) {
        return null;
    }
    $abs = realpath(__DIR__ . '/../' . $rel);
    if ($abs === false || strpos($abs, $storageRoot) !== 0 || !is_file($abs)) {
        return null;
    }
    return $abs;
}

function serveFile(string $absPath, string $originalName, string $mime, int $size): void
{
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . $size);
    header('Content-Disposition: attachment; filename="' . str_replace(['"', "\r", "\n"], '', basename($originalName)) . '"');
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: private, max-age=3600');
    readfile($absPath);
    exit;
}

function classworkAccessible(int $classworkId, int $userId, bool $isAdmin): bool
{
    if ($classworkId <= 0) {
        return false;
    }
    $db = Database::connection();
    $cw = $db->prepare('SELECT classroom_id FROM classroom_classwork WHERE id = :id LIMIT 1');
    $cw->execute(['id' => $classworkId]);
    $roomId = (int) ($cw->fetchColumn() ?: 0);
    return $roomId > 0 && ($isAdmin || Classroom::isMember($roomId, $userId));
}

function fileRowAllowed(array $file, int $userId, bool $isAdmin): bool
{
    if ($isAdmin || (int) $file['uploaded_by'] === $userId) {
        return true;
    }
    $ctx = (string) ($file['upload_context'] ?? '');
    if (strpos($ctx, 'submission:') === 0) {
        return classworkAccessible((int) substr($ctx, 11), $userId, $isAdmin);
    }
    $parts = explode(':', $ctx, 2);
    if (count($parts) === 2) {
        $meta = json_decode($parts[1], true);
        if (is_array($meta)) {
            $classworkId = (int) ($meta['classwork_id'] ?? 0);
            if ($classworkId > 0) {
                return classworkAccessible($classworkId, $userId, $isAdmin);
            }
        }
    }
    return false;
}

if (isset($_GET['file'])) {
    $stmt = $db->prepare('SELECT * FROM files WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => (int) $_GET['file']]);
    $file = $stmt->fetch();
    if (!$file) {
        http_response_code(404);
        exit('File not found.');
    }
    if (!fileRowAllowed($file, $userId, $isAdmin)) {
        http_response_code(403);
        exit('Access denied.');
    }
    $abs = resolveStoredPath((string) $file['stored_path'], $storageRoot);
    if ($abs === null) {
        http_response_code(404);
        exit('File not found.');
    }
    serveFile($abs, (string) $file['original_name'], (string) $file['mime_type'], (int) $file['file_size']);
}

if (isset($_GET['path'])) {
    $rel = (string) $_GET['path'];
    $abs = resolveStoredPath($rel, $storageRoot);
    if ($abs === null) {
        http_response_code(404);
        exit('File not found.');
    }

    $allowed = false;
    $originalName = basename($rel);
    $mime = 'application/octet-stream';

    if (strpos($rel, 'storage/uploads/payments/') === 0) {
        $stmt = $db->prepare('SELECT 1 FROM payment_verifications WHERE screenshot_path = :p LIMIT 1');
        $stmt->execute(['p' => $rel]);
        $pay = $stmt->fetchColumn();
        if ($pay) {
            $owner = $db->prepare(
                'SELECT pv.student_id FROM payment_verifications pv
                 WHERE pv.screenshot_path = :p AND EXISTS (SELECT 1 FROM students s WHERE s.id = pv.student_id AND s.user_id = :u) LIMIT 1'
            );
            $owner->execute(['p' => $rel, 'u' => $userId]);
            $allowed = $isAdmin || (bool) $owner->fetchColumn();
        }
    } elseif (strpos($rel, 'storage/uploads/recommendations/') === 0) {
        $stmt = $db->prepare('SELECT 1 FROM recommendations WHERE file_path = :p LIMIT 1');
        $stmt->execute(['p' => $rel]);
        $rec = $stmt->fetchColumn();
        if ($rec) {
            $owner = $db->prepare(
                'SELECT r.student_id FROM recommendations r
                 WHERE r.file_path = :p AND EXISTS (SELECT 1 FROM students s WHERE s.id = r.student_id AND s.user_id = :u) LIMIT 1'
            );
            $owner->execute(['p' => $rel, 'u' => $userId]);
            $allowed = $isAdmin || (bool) $owner->fetchColumn();
        }
    } else {
        $stmt = $db->prepare('SELECT * FROM files WHERE stored_path = :p LIMIT 1');
        $stmt->execute(['p' => $rel]);
        $file = $stmt->fetch();
        if ($file) {
            $allowed = fileRowAllowed($file, $userId, $isAdmin);
            $originalName = (string) $file['original_name'];
            $mime = (string) $file['mime_type'];
        }
    }

    if (!$allowed) {
        http_response_code(403);
        exit('Access denied.');
    }
    serveFile($abs, $originalName, $mime, (int) filesize($abs));
}

http_response_code(400);
exit('Missing file reference.');
