<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/core/Auth.php';
require_once __DIR__ . '/../app/core/Security.php';
require_once __DIR__ . '/../app/models/Classroom.php';

if (!Auth::check()) {
    header('Location: signup.php');
    exit;
}

Security::requireCsrf();

$action = $_POST['action'] ?? '';
$userId = (int) Auth::userId();
$role = Auth::role();

try {
    if ($action === 'post_announcement') {
        $classroomId = (int) ($_POST['classroom_id'] ?? 0);
        if ($role !== 'admin' && !Classroom::isTeacher($classroomId, $userId)) {
            throw new RuntimeException('Only teachers can post announcements.');
        }
        $body = trim($_POST['body'] ?? '');
        if ($body === '') {
            throw new RuntimeException('Announcement cannot be empty.');
        }
        Classroom::postAnnouncement($classroomId, $userId, $body);
        require_once __DIR__ . '/../app/models/Notification.php';
        Notification::broadcastToClassroom($classroomId, 'New announcement', mb_substr($body, 0, 120), 'classroom', 'classroom.php?action=view&id=' . $classroomId);
        header('Location: classroom.php?action=view&id=' . $classroomId . '&section=stream&posted=1');
        exit;
    }

    if ($action === 'create_classwork') {
        $classroomId = (int) ($_POST['classroom_id'] ?? 0);
        if ($role !== 'admin' && !Classroom::isTeacher($classroomId, $userId)) {
            throw new RuntimeException('Only teachers can add classwork.');
        }
        $type = $_POST['work_type'] ?? 'material';
        if (!in_array($type, ['assignment', 'material', 'question'], true)) {
            throw new RuntimeException('Invalid classwork type.');
        }
        $title = trim($_POST['title'] ?? '');
        if ($title === '') {
            throw new RuntimeException('Title is required.');
        }
        $maxScore = (float) ($_POST['max_score'] ?? 100);
        $dueAt = trim($_POST['due_at'] ?? '') ?: null;
        $classworkId = Classroom::createClasswork(
            $classroomId, $userId, $type, $title,
            trim($_POST['description'] ?? ''),
            trim($_POST['instructions'] ?? ''),
            $dueAt, $maxScore
        );
        if ($type === 'assignment' && !empty($_POST['rubric'])) {
            $rubricTitle = trim($_POST['rubric_title'] ?? 'Grading rubric');
            $criteria = [];
            $labels = $_POST['rubric_label'] ?? [];
            $maxes = $_POST['rubric_max'] ?? [];
            $descs = $_POST['rubric_desc'] ?? [];
            foreach ($labels as $i => $label) {
                $label = trim($label);
                if ($label === '') {
                    continue;
                }
                $criteria[] = [
                    'label' => $label,
                    'description' => trim($descs[$i] ?? ''),
                    'max_points' => (float) ($maxes[$i] ?? 10),
                ];
            }
        if (!empty($criteria)) {
            Classroom::createRubric($classworkId, $rubricTitle, $criteria);
        }
    }
    require_once __DIR__ . '/../app/models/Notification.php';
    Notification::broadcastToClassroom($classroomId, 'New classwork: ' . $title, 'A new ' . $type . ' has been posted in the classroom.', 'classwork', 'classroom.php?action=classwork&work_id=' . $classworkId);
    header('Location: classroom.php?action=classwork&work_id=' . $classworkId . '&created=1');
    exit;
}

if ($action === 'invite_student') {
    $classroomId = (int) ($_POST['classroom_id'] ?? 0);
        if ($role !== 'admin' && !Classroom::isTeacher($classroomId, $userId)) {
            throw new RuntimeException('Only teachers can invite students.');
        }
        $email = strtolower(trim($_POST['email'] ?? ''));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Please enter a valid email address.');
        }
        $user = Classroom::findUserByEmail($email);
        if (!$user) {
            throw new RuntimeException('No NEAC user with that email. Ask them to sign up first.');
        }
        $userRole = is_array($user) ? ($user['role'] ?? '') : '';
        if ($role === 'admin' || (is_string($userRole) && strtolower($userRole) === 'student') || (is_numeric($userRole) && (int) $userRole === 0)) {
            // proceed
        } else {
            throw new RuntimeException('Only students can be invited to a classroom.');
        }
        Classroom::addMember($classroomId, (int) $user['id'], 'student');
        header('Location: classroom.php?action=manage&id=' . $classroomId . '&invited=1');
        exit;
    }

    if ($action === 'remove_member') {
        $classroomId = (int) ($_POST['classroom_id'] ?? 0);
        $memberId = (int) ($_POST['member_id'] ?? 0);
        if ($role !== 'admin' && !Classroom::isTeacher($classroomId, $userId)) {
            throw new RuntimeException('Only teachers can remove members.');
        }
        Classroom::removeMember($classroomId, $memberId);
        header('Location: classroom.php?action=manage&id=' . $classroomId . '&removed=1');
        exit;
    }

    if ($action === 'submit_assignment') {
        $classworkId = (int) ($_POST['classwork_id'] ?? 0);
        $studentId = Auth::studentId();
        if (!$studentId) {
            throw new RuntimeException('Only students can submit assignments.');
        }
        $note = trim($_POST['note'] ?? '');
        $classwork = Classroom::findClasswork($classworkId);
        if (!$classwork) {
            throw new RuntimeException('Classwork not found.');
        }
        if (!Classroom::isMember((int) $classwork['classroom_id'], $userId)) {
            throw new RuntimeException('You are not a member of this classroom.');
        }
        if (empty($_FILES['upload_file']) || $_FILES['upload_file']['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Please attach a file before submitting.');
        }
        $file = $_FILES['upload_file'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        [$ok, $msg] = Security::uploadIsAllowed(
            $file,
            ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'txt', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'zip', 'mp4', 'mov'],
            ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument', 'application/vnd.ms-', 'image/', 'text/', 'video/'],
            80 * 1024 * 1024
        );
        if (!$ok) {
            throw new RuntimeException($msg);
        }
        $uploadRoot = __DIR__ . '/../storage/uploads/submission';
        if (!is_dir($uploadRoot)) {
            mkdir($uploadRoot, 0775, true);
        }
        $storedName = Security::safeStorageName($file['name'], $ext);
        $target = $uploadRoot . '/' . $storedName;
        if (!move_uploaded_file($file['tmp_name'], $target)) {
            throw new RuntimeException('Could not save the uploaded file.');
        }
        $mime = Security::detectMime($target);
        $rel = 'storage/uploads/submission/' . $storedName;
        $fileStmt = Database::connection()->prepare(
            'INSERT INTO files (uploaded_by, original_name, stored_path, mime_type, file_size, upload_context)
             VALUES (:u, :o, :s, :m, :fs, :c)'
        );
        $fileStmt->execute([
            'u' => $userId, 'o' => substr($file['name'], 0, 255), 's' => $rel, 'm' => $mime,
            'fs' => (int) $file['size'], 'c' => 'submission:' . $classworkId,
        ]);
        $fileId = (int) Database::connection()->lastInsertId();
        $stmt = Database::connection()->prepare(
            'INSERT INTO classroom_submissions (classwork_id, student_id, file_id, note, status)
             VALUES (:c, :s, :f, :n, "submitted")
             ON CONFLICT(classwork_id, student_id) DO UPDATE SET
                file_id = excluded.file_id,
                note = excluded.note,
                submitted_at = CURRENT_TIMESTAMP,
                status = "submitted"'
        );
        $stmt->execute(['c' => $classworkId, 's' => $studentId, 'f' => $fileId, 'n' => $note]);
        header('Location: classroom.php?action=classwork&work_id=' . $classworkId . '&submitted=1');
        exit;
    }

    if ($action === 'save_grade') {
        $submissionId = (int) ($_POST['submission_id'] ?? 0);
        $submission = Classroom::findSubmission($submissionId);
        if (!$submission) {
            throw new RuntimeException('Submission not found.');
        }
        if ($role !== 'admin' && !Classroom::isTeacher((int) $submission['classroom_id'], $userId)) {
            throw new RuntimeException('Only the teacher can grade.');
        }
        $criterionIds = $_POST['criterion_id'] ?? [];
        $points = $_POST['points'] ?? [];
        $comments = $_POST['comment'] ?? [];
        $feedback = trim($_POST['overall_feedback'] ?? '');
        foreach ($criterionIds as $i => $criterionId) {
            $pts = (float) ($points[$i] ?? 0);
            $cmt = trim($comments[$i] ?? '');
            Classroom::saveGrade($submissionId, (int) $criterionId, $userId, $pts, $cmt);
        }
        if ($feedback !== '') {
            $stmt = Database::connection()->prepare(
                'INSERT INTO classroom_submission_comments (submission_id, author_id, body) VALUES (:s, :a, :b)'
            );
            $stmt->execute(['s' => $submissionId, 'a' => $userId, 'b' => $feedback]);
        }
        header('Location: classroom.php?action=classwork&work_id=' . (int) $submission['classwork_id'] . '&graded=1');
        exit;
    }

    if ($action === 'add_classroom') {
        if ($role !== 'admin') {
            throw new RuntimeException('Only admins can create classrooms.');
        }
        $courseId = (int) ($_POST['course_id'] ?? 0);
        if ($courseId <= 0) {
            throw new RuntimeException('A valid course is required.');
        }
        $cid = Classroom::createForCourse($courseId);
        header('Location: classroom.php?action=manage&id=' . $cid . '&created=1');
        exit;
    }

    if ($action === 'private_comment') {
        $classroomId = (int) ($_POST['classroom_id'] ?? 0);
        if ($role !== 'admin' && !Classroom::isTeacher($classroomId, $userId)) {
            throw new RuntimeException('Only teachers can send private comments.');
        }
        $studentId = (int) ($_POST['student_id'] ?? 0);
        $body = trim($_POST['body'] ?? '');
        if (!$studentId || !$body) {
            throw new RuntimeException('Student and comment body are required.');
        }
        require_once __DIR__ . '/../app/models/PrivateComment.php';
        PrivateComment::create($classroomId, $studentId, $userId, $body);
        header('Location: classroom.php?action=view&id=' . $classroomId . '&section=private_comments');
        exit;
    }

    if ($action === 'record_attendance') {
        $classroomId = (int) ($_POST['classroom_id'] ?? 0);
        if ($role !== 'admin' && !Classroom::isTeacher($classroomId, $userId)) {
            throw new RuntimeException('Only teachers can record attendance.');
        }
        $courseId = (int) ($_POST['course_id'] ?? 0);
        require_once __DIR__ . '/../app/models/Attendance.php';
        $studentIds = $_POST['student_ids'] ?? [];
        foreach ($studentIds as $sid) {
            $sid = (int) $sid;
            if ($sid <= 0) continue;
            $status = $_POST['status_' . $sid] ?? 'present';
            if (!in_array($status, ['present', 'absent', 'late', 'excused'], true)) continue;
            if (!Attendance::todayExists($sid, $courseId ?: null)) {
                Attendance::record($sid, $status, $courseId ?: null, $userId);
            }
        }
        header('Location: classroom.php?action=view&id=' . $classroomId . '&section=attendance&attendance_saved=1');
        exit;
    }

    throw new RuntimeException('Unknown classroom action.');
} catch (Throwable $exception) {
    $back = 'dashboard.php?section=classrooms';
    header('Location: ' . $back . '&error=' . urlencode($exception->getMessage()));
    exit;
}
