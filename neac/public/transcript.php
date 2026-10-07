<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/core/Auth.php';
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/core/Security.php';

if (!Auth::check()) {
    header('Location: signup.php');
    exit;
}

$role = Auth::role();
$studentId = null;
$userId = (int) Auth::userId();
$db = Database::connection();
Database::migrate();

if ($role === 'student') {
    $studentId = (int) Auth::studentId();
    $status = getCertStatus();
    if (!$status['access']) {
        header('Location: application.php');
        exit;
    }
} elseif ($role === 'admin') {
    $studentId = (int) ($_GET['student_id'] ?? 0);
} elseif ($role === 'teacher') {
    $requested = (int) ($_GET['student_id'] ?? 0);
    if (!$requested) {
        http_response_code(404);
        exit('Student not found.');
    }
    $owns = $db->prepare(
        'SELECT 1 FROM courses c
         INNER JOIN student_marks sm ON sm.course_id = c.id AND sm.student_id = :sid
         WHERE c.teacher_id = :tid LIMIT 1'
    );
    $owns->execute(['sid' => $requested, 'tid' => (int) Auth::teacherId()]);
    if (!$owns->fetchColumn()) {
        http_response_code(403);
        exit('You can only view transcripts of students you teach.');
    }
    $studentId = $requested;
} else {
    header('Location: signup.php');
    exit;
}

if (!$studentId) {
    http_response_code(404);
    exit('Student not found.');
}

$studentInfo = $db->prepare('SELECT students.*, users.full_name, users.email, programs.name AS program_name FROM students INNER JOIN users ON users.id = students.user_id INNER JOIN programs ON programs.id = students.program_id WHERE students.id = :sid');
$studentInfo->execute(['sid' => $studentId]);
$student = $studentInfo->fetch();
if (!$student) {
    http_response_code(404);
    exit('Student not found.');
}

$marks = $db->prepare('SELECT sm.*, courses.title AS course_title, courses.code AS course_code FROM student_marks sm INNER JOIN courses ON courses.id = sm.course_id WHERE sm.student_id = :sid AND sm.status = "approved" ORDER BY courses.title');
$marks->execute(['sid' => $studentId]);
$courseMarks = $marks->fetchAll();

$totalCredits = 0;
$totalWeightedPoints = 0;
foreach ($courseMarks as $cm) {
    $totalCredits += (float) ($cm['credit_value'] ?? 4);
    $totalWeightedPoints += (float) ($cm['weighted_point'] ?? 0);
}
$gpa = $totalCredits > 0 ? round($totalWeightedPoints / $totalCredits, 2) : 0;

function gradePoint($score): float {
    if ($score >= 80) return 4.0;
    if ($score >= 70) return 3.5;
    if ($score >= 60) return 3.0;
    if ($score >= 55) return 2.5;
    if ($score >= 50) return 2.0;
    if ($score >= 45) return 1.5;
    if ($score >= 40) return 1.0;
    return 0;
}

function gradeLetter($score): string {
    if ($score >= 80) return 'A';
    if ($score >= 70) return 'B+';
    if ($score >= 60) return 'B';
    if ($score >= 55) return 'C+';
    if ($score >= 50) return 'C';
    if ($score >= 45) return 'D+';
    if ($score >= 40) return 'D';
    return 'F';
}

function degreeClass($gpa): string {
    if ($gpa >= 3.60) return 'First Class';
    if ($gpa >= 3.00) return 'Second Class - Upper Division';
    if ($gpa >= 2.50) return 'Second Class - Lower Division';
    if ($gpa >= 2.25) return 'Third Class - Upper Division';
    if ($gpa >= 2.00) return 'Pass';
    return 'Fail';
}

$pageTitle = 'Student Transcript';
$hideTopbar = true;
require __DIR__ . '/../views/certification/transcript.view.php';
