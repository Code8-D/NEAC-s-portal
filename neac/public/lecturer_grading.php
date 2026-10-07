<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/core/Auth.php';
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/core/Security.php';

if (!Auth::check() || Auth::role() !== 'teacher') {
    http_response_code(403);
    exit('Teacher access required.');
}

$teacherId = (int) Auth::teacherId();
$userId = (int) Auth::userId();
$db = Database::connection();
Database::migrate();

$myCourses = $db->prepare('SELECT courses.id, courses.title, courses.code FROM courses WHERE teacher_id = :tid ORDER BY courses.title');
$myCourses->execute(['tid' => $teacherId]);
$courses = $myCourses->fetchAll();

$error = '';
$success = '';
$selectedCourseId = (int) ($_GET['course_id'] ?? 0);
$selectedStudentId = (int) ($_GET['student_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::requireCsrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'save_marks') {
        $courseId = (int) ($_POST['course_id'] ?? 0);
        $studentIds = $_POST['student_ids'] ?? [];
        $caScores = $_POST['ca_score'] ?? [];
        $examScores = $_POST['exam_score'] ?? [];
        $ownCourseIds = array_map('intval', array_column($courses, 'id'));
        if (!$courseId || !in_array($courseId, $ownCourseIds, true)) {
            $error = 'You can only grade courses assigned to you.';
        } elseif (empty($studentIds)) {
            $error = 'Please select a course and enter marks.';
        } else {
            $validStudents = $db->prepare(
                'SELECT DISTINCT students.id AS student_id FROM classroom_members cm
                 INNER JOIN students ON students.user_id = cm.user_id
                 WHERE cm.classroom_id IN (SELECT id FROM classrooms WHERE course_id = :cid)
                   AND cm.role = "student" AND cm.status = "active"'
            );
            $validStudents->execute(['cid' => $courseId]);
            $validStudentIds = array_map('intval', array_column($validStudents->fetchAll(), 'student_id'));
            $db->beginTransaction();
            try {
                foreach ($studentIds as $sid) {
                    $sid = (int) $sid;
                    if (!in_array($sid, $validStudentIds, true)) {
                        continue;
                    }
                    $ca = (float) ($caScores[$sid] ?? 0);
                    $exam = (float) ($examScores[$sid] ?? 0);
                    $total = $ca + $exam;
                    $gp = 0;
                    $grade = 'F';
                    if ($total >= 80) { $gp = 4.0; $grade = 'A'; }
                    elseif ($total >= 70) { $gp = 3.5; $grade = 'B+'; }
                    elseif ($total >= 60) { $gp = 3.0; $grade = 'B'; }
                    elseif ($total >= 55) { $gp = 2.5; $grade = 'C+'; }
                    elseif ($total >= 50) { $gp = 2.0; $grade = 'C'; }
                    elseif ($total >= 45) { $gp = 1.5; $grade = 'D+'; }
                    elseif ($total >= 40) { $gp = 1.0; $grade = 'D'; }
                    $wp = $gp * 4;
                    $stmt = $db->prepare('INSERT INTO student_marks (student_id, course_id, lecturer_id, continuous_assessment, exam_score, total_score, grade, grade_point, weighted_point, credit_value, status)
                        VALUES (:sid, :cid, :lid, :ca, :exam, :total, :grade, :gp, :wp, 4, "draft")
                        ON CONFLICT(student_id, course_id) DO UPDATE SET continuous_assessment = :ca2, exam_score = :exam2, total_score = :total2, grade = :grade2, grade_point = :gp2, weighted_point = :wp2, status = "draft", updated_at = CURRENT_TIMESTAMP');
                    $stmt->execute(['sid' => $sid, 'cid' => $courseId, 'lid' => $teacherId, 'ca' => $ca, 'exam' => $exam, 'total' => $total, 'grade' => $grade, 'gp' => $gp, 'wp' => $wp, 'ca2' => $ca, 'exam2' => $exam, 'total2' => $total, 'grade2' => $grade, 'gp2' => $gp, 'wp2' => $wp]);
                }
                $db->commit();
                $success = 'Marks saved as draft. Submit them to admin for approval.';
            } catch (Throwable $e) {
                $db->rollBack();
                $error = 'Error saving marks: ' . $e->getMessage();
            }
        }
    } elseif ($action === 'submit_marks') {
        $courseId = (int) ($_POST['course_id'] ?? 0);
        $ownCourseIds = array_map('intval', array_column($courses, 'id'));
        if ($courseId && in_array($courseId, $ownCourseIds, true)) {
            $db->prepare('UPDATE student_marks SET status = "submitted", submitted_at = CURRENT_TIMESTAMP WHERE course_id = :cid AND lecturer_id = :lid AND status = "draft"')->execute(['cid' => $courseId, 'lid' => $teacherId]);
            require_once __DIR__ . '/../app/models/Notification.php';
            $adminStmt = $db->query('SELECT id FROM users WHERE role = "admin" LIMIT 1');
            $adminId = (int) $adminStmt->fetchColumn();
            if ($adminId) {
                Notification::create($adminId, 'Marks Submitted for Approval', 'A lecturer has submitted marks for a course. Review in the grading panel.', 'grading');
            }
            $success = 'Marks submitted for admin approval.';
        }
    }
}

$students = [];
$existingMarks = [];
if ($selectedCourseId) {
    $students = $db->prepare('SELECT DISTINCT users.id AS user_id, users.full_name, students.id AS student_id FROM classroom_members cm INNER JOIN users ON users.id = cm.user_id INNER JOIN students ON students.user_id = users.id WHERE cm.classroom_id IN (SELECT id FROM classrooms WHERE course_id = :cid) AND cm.role = "student" AND cm.status = "active" ORDER BY users.full_name');
    $students->execute(['cid' => $selectedCourseId]);
    $students = $students->fetchAll();
    $marks = $db->prepare('SELECT * FROM student_marks WHERE course_id = :cid');
    $marks->execute(['cid' => $selectedCourseId]);
    foreach ($marks as $m) { $existingMarks[(int) $m['student_id']] = $m; }
}

$pageTitle = 'Grading';
require __DIR__ . '/../views/layouts/header.php';
?>
<main class="app-shell">
    <?php require __DIR__ . '/../views/layouts/sidebar.php'; ?>
    <section class="workspace">
        <div class="workspace__head">
            <div><span class="eyebrow">Teacher portal</span><h1>Student Grading</h1></div>
        </div>
        <?php if ($success): ?><p class="notice notice--success"><?= e($success) ?></p><?php endif; ?>
        <?php if ($error): ?><p class="notice notice--error"><?= e($error) ?></p><?php endif; ?>

        <section class="panel three-d-card">
            <div class="panel__head"><h2><i class="fa-solid fa-book"></i> Select Course</h2></div>
            <form method="get" style="display:flex;gap:12px;align-items:end;margin-top:12px">
                <label style="flex:1">
                    Course
                    <select name="course_id" onchange="this.form.submit()">
                        <option value="">-- Select --</option>
                        <?php foreach ($courses as $c): ?>
                            <option value="<?= (int) $c['id'] ?>" <?= $selectedCourseId === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['code']) ?> - <?= e($c['title']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </form>
        </section>

        <?php if ($selectedCourseId && !empty($students)): ?>
            <form method="post">
                <?= Security::csrfField() ?>
                <input type="hidden" name="action" value="save_marks">
                <input type="hidden" name="course_id" value="<?= $selectedCourseId ?>">
                <section class="panel three-d-card">
                    <div class="panel__head">
                        <h2><i class="fa-solid fa-users"></i> Enter Marks</h2>
                        <div style="display:flex;gap:8px">
                            <button class="btn btn--primary btn--3d" type="submit"><i class="fa-solid fa-save"></i> Save Draft</button>
                            <button class="btn btn--ghost btn--3d" type="submit" formaction="?course_id=<?= $selectedCourseId ?>" formmethod="post" name="action" value="submit_marks" onclick="return confirm('Submit all marks for admin approval? This cannot be undone.')"><i class="fa-solid fa-paper-plane"></i> Submit to Admin</button>
                        </div>
                    </div>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr><th>Student</th><th>CA (0-40)</th><th>Exam (0-60)</th><th>Total</th><th>Grade</th><th>Status</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($students as $stu): $sid = (int) $stu['student_id']; $em = $existingMarks[$sid] ?? null; ?>
                                    <tr>
                                        <td><strong><?= e($stu['full_name']) ?></strong></td>
                                        <td><input type="hidden" name="student_ids[]" value="<?= $sid ?>"><input type="number" name="ca_score[<?= $sid ?>]" value="<?= (float) ($em['continuous_assessment'] ?? 0) ?>" min="0" max="40" step="0.5" style="width:80px" required></td>
                                        <td><input type="number" name="exam_score[<?= $sid ?>]" value="<?= (float) ($em['exam_score'] ?? 0) ?>" min="0" max="60" step="0.5" style="width:80px" required></td>
                                        <td><strong><?= $em ? (float) $em['total_score'] : '0' ?></strong></td>
                                        <td><?= e($em['grade'] ?? 'N/A') ?></td>
                                        <td><span class="status" style="<?= ($em['status'] ?? '') === 'approved' ? 'background:#ecfdf5;color:#065f46' : (($em['status'] ?? '') === 'submitted' ? 'background:#fffbeb;color:#92400e' : '') ?>"><?= e(ucfirst($em['status'] ?? 'Not set')) ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </form>
        <?php elseif ($selectedCourseId): ?>
            <section class="panel three-d-card">
                <div class="empty-state"><i class="fa-solid fa-users"></i><h3>No students enrolled</h3><p>This course has no enrolled students yet.</p></div>
            </section>
        <?php endif; ?>
    </section>
</main>
<?php require __DIR__ . '/../views/layouts/footer.php'; ?>
