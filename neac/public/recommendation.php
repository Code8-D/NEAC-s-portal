<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/core/Auth.php';
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/core/Security.php';

if (!Auth::check() || Auth::role() !== 'student') {
    header('Location: signup.php');
    exit;
}

$studentId = (int) Auth::studentId();
$userId = (int) Auth::userId();
$db = Database::connection();
Database::migrate();

$status = getCertStatus();
if (!$status['applied']) {
    header('Location: application.php');
    exit;
}

$recs = $db->prepare('SELECT * FROM recommendations WHERE student_id = :sid ORDER BY uploaded_at DESC');
$recs->execute(['sid' => $studentId]);
$recommendations = $recs->fetchAll();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::requireCsrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'upload_recommendation') {
        $type = $_POST['recommendation_type'] ?? '';
        $allowedTypes = ['pastor', 'company', 'association', 'field'];
        if (!in_array($type, $allowedTypes)) {
            $error = 'Invalid recommendation type.';
        } elseif (empty($_FILES['recommendation_file']) || $_FILES['recommendation_file']['error'] !== UPLOAD_ERR_OK) {
            $error = 'Please upload the filled recommendation form.';
        } else {
            $file = $_FILES['recommendation_file'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed = ['pdf', 'jpg', 'jpeg', 'png'];
            [$ok, $msg] = Security::uploadIsAllowed($file, $allowed, ['application/pdf', 'image/'], 10 * 1024 * 1024);
            if (!$ok) {
                $error = $msg;
            } else {
                $uploadDir = __DIR__ . '/../storage/uploads/recommendations/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0775, true);
                }
                $storedName = 'rec_' . $type . '_' . $studentId . '_' . time() . '_' . Security::randomCode(6) . '.' . $ext;
                if (!move_uploaded_file($file['tmp_name'], $uploadDir . $storedName)) {
                    $error = 'Could not save the uploaded file. Please try again.';
                } else {
                $check = $db->prepare('SELECT id FROM recommendations WHERE student_id = :sid AND recommendation_type = :type');
                $check->execute(['sid' => $studentId, 'type' => $type]);
                $existing = $check->fetch();
                if ($existing) {
                    $stmt = $db->prepare('UPDATE recommendations SET file_path = :fp, original_name = :on, status = "pending", uploaded_at = CURRENT_TIMESTAMP WHERE id = :id');
                    $stmt->execute(['fp' => 'storage/uploads/recommendations/' . $storedName, 'on' => $file['name'], 'id' => $existing['id']]);
                } else {
                    $stmt = $db->prepare('INSERT INTO recommendations (student_id, recommendation_type, file_path, original_name, status) VALUES (:sid, :type, :fp, :on, "pending")');
                    $stmt->execute(['sid' => $studentId, 'type' => $type, 'fp' => 'storage/uploads/recommendations/' . $storedName, 'on' => $file['name']]);
                }
                $success = 'Recommendation form uploaded successfully!';
                $recs = $db->prepare('SELECT * FROM recommendations WHERE student_id = :sid ORDER BY uploaded_at DESC');
                $recs->execute(['sid' => $studentId]);
                $recommendations = $recs->fetchAll();
                }
            }
        }
    } elseif ($action === 'delete_recommendation') {
        $recId = (int) ($_POST['rec_id'] ?? 0);
        $stmt = $db->prepare('DELETE FROM recommendations WHERE id = :id AND student_id = :sid');
        $stmt->execute(['id' => $recId, 'sid' => $studentId]);
        $success = 'Recommendation removed.';
        $recs = $db->prepare('SELECT * FROM recommendations WHERE student_id = :sid ORDER BY uploaded_at DESC');
        $recs->execute(['sid' => $studentId]);
        $recommendations = $recs->fetchAll();
    }
}

$pageTitle = 'Recommendation Forms';
$hideTopbar = true;
require __DIR__ . '/../views/certification/recommendation.view.php';
