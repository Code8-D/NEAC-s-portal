<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/core/Auth.php';
require_once __DIR__ . '/../app/core/Security.php';
require_once __DIR__ . '/../app/models/Classroom.php';

if (!Auth::check()) {
    header('Location: signup.php');
    exit;
}

if (Auth::role() === 'student' && !Auth::isAdmin()) {
    require_once __DIR__ . '/../config/config.php';
    if (!hasCertAccess()) {
        require_once __DIR__ . '/../app/models/Setting.php';
        $certBatch = (string) (Setting::get('cert_batch_label') ?? '4th Batch');
        $pageTitle = 'Certification Required';
        $hideTopbar = true;
        require __DIR__ . '/../views/classroom/cert_required.view.php';
        exit;
    }
}

$action = $_GET['action'] ?? 'view';
if ($action === 'list') {
    $action = 'view';
}
$classroomId = (int) ($_GET['id'] ?? 0);
$userId = (int) Auth::userId();
$role = Auth::role();

if ($action === 'join') {
    $code = trim($_GET['code'] ?? '');
    $error = '';
    $classroom = null;
    if ($code !== '') {
        $classroom = Classroom::findByCode($code);
        if (!$classroom) {
            $error = 'No classroom matches that code.';
        } elseif ((int) $classroom['archived_at'] !== 0) {
            $error = 'This classroom has been archived.';
        } elseif (!Classroom::isMember((int) $classroom['id'], $userId)) {
            if ($role === 'student' && (int) $classroom['is_open_join'] === 1) {
                Classroom::addMember((int) $classroom['id'], $userId, 'student');
                header('Location: classroom.php?action=view&id=' . (int) $classroom['id']);
                exit;
            }
            $error = $role === 'student'
                ? 'This classroom is invite-only. Ask your teacher for an invite.'
                : 'Only students can join a classroom via a class code.';
        } else {
            header('Location: classroom.php?action=view&id=' . (int) $classroom['id']);
            exit;
        }
    }
    $pageTitle = 'Join a classroom';
    require __DIR__ . '/../views/classroom/join.view.php';
    exit;
}

if ($action === 'view') {
    $classroom = $classroomId > 0 ? Classroom::find($classroomId) : null;
    if (!$classroom) {
        http_response_code(404);
        $pageTitle = 'Classroom not found';
        require __DIR__ . '/../views/layouts/header.php';
        echo '<main class="app-shell"><section class="workspace"><div class="empty-state"><i class="fa-solid fa-circle-exclamation"></i><h3>Classroom not found</h3></div></section></main>';
        require __DIR__ . '/../views/layouts/footer.php';
        exit;
    }
    if ($role !== 'admin' && !Classroom::isMember($classroomId, $userId)) {
        http_response_code(403);
        $pageTitle = 'Access denied';
        require __DIR__ . '/../views/layouts/header.php';
        echo '<main class="app-shell"><section class="workspace"><div class="empty-state"><i class="fa-solid fa-lock"></i><h3>You are not a member of this classroom</h3></div></section></main>';
        require __DIR__ . '/../views/layouts/footer.php';
        exit;
    }
    $section = $_GET['section'] ?? 'stream';
    $isTeacher = $role === 'admin' || Classroom::isTeacher($classroomId, $userId);
    $pageTitle = $classroom['title'] . ' • Classroom';
    $hideTopbar = true;
    $stream = Classroom::stream($classroomId);
    $classwork = Classroom::classwork($classroomId);
    $courseId = Classroom::courseId($classroomId);
    $members = Classroom::members($classroomId);
    $memberCount = count($members);
    require __DIR__ . '/../views/classroom/dashboard.view.php';
    exit;
}

if ($action === 'manage') {
    if ($role !== 'admin' && !Classroom::isTeacher($classroomId, $userId)) {
        http_response_code(403);
        exit('Only teachers and admins can manage a classroom.');
    }
    $classroom = Classroom::find($classroomId);
    if (!$classroom) {
        http_response_code(404);
        exit('Classroom not found.');
    }
    $pageTitle = 'Manage ' . $classroom['title'];
    $hideTopbar = true;
    $members = Classroom::members($classroomId);
    $classwork = Classroom::classwork($classroomId);
    require __DIR__ . '/../views/classroom/manage.view.php';
    exit;
}

if ($action === 'classwork') {
    if (($_GET['work_id'] ?? '') === 'new') {
        $classroomIdNew = (int) ($_GET['classroom'] ?? ($_GET['id'] ?? 0));
        if ($classroomIdNew <= 0 || ($role !== 'admin' && !Classroom::isTeacher($classroomIdNew, $userId))) {
            http_response_code(403);
            exit('Only teachers can create classwork.');
        }
        header('Location: classroom.php?action=manage&id=' . $classroomIdNew . '&section=classwork');
        exit;
    }
    $classworkId = (int) ($_GET['work_id'] ?? 0);
    $classwork = $classworkId > 0 ? Classroom::findClasswork($classworkId) : null;
    if (!$classwork) {
        http_response_code(404);
        exit('Classwork not found.');
    }
    $isTeacher = $role === 'admin' || Classroom::isTeacher((int) $classwork['classroom_id'], $userId);
    $isMember = $isTeacher || Classroom::isMember((int) $classwork['classroom_id'], $userId);
    if (!$isMember) {
        http_response_code(403);
        exit('You do not have access to this classwork.');
    }
    $attachments = Classroom::attachments($classworkId);
    $rubric = Classroom::rubricForClasswork($classworkId);
    $studentSubmission = null;
    $submissions = [];
    if ($classwork['work_type'] === 'assignment') {
        if ($isTeacher) {
            $submissions = Classroom::submissionsForClasswork($classworkId);
        } elseif (Auth::studentId()) {
            $studentSubmission = Classroom::studentSubmission($classworkId, (int) Auth::studentId());
        }
    }
    $pageTitle = $classwork['title'] . ' • Classwork';
    $hideTopbar = true;
    require __DIR__ . '/../views/classroom/classwork.view.php';
    exit;
}

if ($action === 'grade') {
    $submissionId = (int) ($_GET['submission_id'] ?? 0);
    $submission = $submissionId > 0 ? Classroom::findSubmission($submissionId) : null;
    if (!$submission) {
        http_response_code(404);
        exit('Submission not found.');
    }
    if ($role !== 'admin' && !Classroom::isTeacher((int) $submission['classroom_id'], $userId)) {
        http_response_code(403);
        exit('Only the teacher can grade this submission.');
    }
    $rubric = Classroom::rubricForClasswork((int) $submission['classwork_id']);
    $existingGrades = Classroom::gradesForSubmission($submissionId);
    $gradeByCriterion = [];
    foreach ($existingGrades as $g) {
        $gradeByCriterion[(int) $g['criterion_id']] = $g;
    }
    $pageTitle = 'Grade • ' . $submission['student_name'];
    $hideTopbar = true;
    require __DIR__ . '/../views/classroom/grade.view.php';
    exit;
}

http_response_code(400);
exit('Unknown classroom action.');
