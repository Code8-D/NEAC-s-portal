<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/core/Auth.php';
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/core/Security.php';

if (!Auth::check() || !Auth::isAdmin()) {
    http_response_code(403);
    exit('Admin access required.');
}

$db = Database::connection();
Database::migrate();
$userId = (int) Auth::userId();
$error = '';
$success = '';

$action = $_GET['action'] ?? 'list';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::requireCsrf();
    $markId = (int) ($_POST['mark_id'] ?? 0);
    $newStatus = $_POST['status'] ?? '';
    if ($markId && $newStatus === 'approved') {
        $stmt = $db->prepare('UPDATE student_marks SET status = "approved", approved_by = :ab, approved_at = CURRENT_TIMESTAMP WHERE id = :id');
        $stmt->execute(['ab' => $userId, 'id' => $markId]);
        $success = 'Mark approved.';
        require_once __DIR__ . '/../app/models/Notification.php';
        $mark = $db->prepare('SELECT sm.student_id, sm.course_id, courses.title AS course_title FROM student_marks sm INNER JOIN courses ON courses.id = sm.course_id WHERE sm.id = :id');
        $mark->execute(['id' => $markId]);
        $m = $mark->fetch();
        if ($m) {
            $stu = $db->prepare('SELECT user_id FROM students WHERE id = :sid');
            $stu->execute(['sid' => $m['student_id']]);
            $uid = (int) $stu->fetchColumn();
            Notification::create($uid, 'Mark Approved - ' . e($m['course_title']), 'Your mark for ' . e($m['course_title']) . ' has been approved by the admin.', 'grading');
        }
    } elseif ($markId && $newStatus === 'approve_all') {
        $courseId = (int) ($_POST['course_id'] ?? 0);
        if ($courseId) {
            $db->prepare('UPDATE student_marks SET status = "approved", approved_by = :ab, approved_at = CURRENT_TIMESTAMP WHERE course_id = :cid AND status = "submitted"')->execute(['ab' => $userId, 'cid' => $courseId]);
            $success = 'All submitted marks for this course approved.';
        }
    } elseif ($_POST['action'] === 'generate_transcript') {
        $studentId = (int) ($_POST['student_id'] ?? 0);
        $programId = (int) ($_POST['program_id'] ?? 0);
        if ($studentId && $programId) {
            $marks = $db->prepare('SELECT sm.*, courses.title AS course_title, courses.code AS course_code FROM student_marks sm INNER JOIN courses ON courses.id = sm.course_id WHERE sm.student_id = :sid AND sm.status = "approved" ORDER BY courses.title');
            $marks->execute(['sid' => $studentId]);
            $courseMarks = $marks->fetchAll();
            if (empty($courseMarks)) {
                $error = 'No approved marks found for this student.';
            } else {
                $totalCredits = 0; $totalWP = 0;
                foreach ($courseMarks as $cm) {
                    $totalCredits += (float) ($cm['credit_value'] ?? 4);
                    $totalWP += (float) ($cm['weighted_point'] ?? 0);
                }
                $gpa = $totalCredits > 0 ? round($totalWP / $totalCredits, 2) : 0;
                $class = $gpa >= 3.60 ? 'First Class' : ($gpa >= 3.00 ? 'Second Class - Upper Division' : ($gpa >= 2.50 ? 'Second Class - Lower Division' : ($gpa >= 2.25 ? 'Third Class - Upper Division' : ($gpa >= 2.00 ? 'Pass' : 'Fail'))));
                $db->beginTransaction();
                try {
                    $ins = $db->prepare('INSERT INTO transcripts (student_id, program_id, academic_year, total_credits, total_weighted_points, gpa, cgpa, degree_classification, remark, generated_by) VALUES (:sid, :pid, :ay, :tc, :twp, :gpa, :cgpa, :dc, :rem, :gb)');
                    $ins->execute(['sid' => $studentId, 'pid' => $programId, 'ay' => '2024/2025', 'tc' => $totalCredits, 'twp' => $totalWP, 'gpa' => $gpa, 'cgpa' => $gpa, 'dc' => $class, 'rem' => $gpa >= 2.0 ? 'Passed' : 'Failed', 'gb' => $userId]);
                    $transcriptId = (int) $db->lastInsertId();
                    $tcIns = $db->prepare('INSERT INTO transcript_courses (transcript_id, course_code, course_title, credit_value, marks, grade, grade_point, weighted_point, sort_order) VALUES (:tid, :cc, :ct, :cv, :m, :g, :gp, :wp, :so)');
                    foreach ($courseMarks as $i => $cm) {
                        $tcIns->execute(['tid' => $transcriptId, 'cc' => $cm['course_code'], 'ct' => $cm['course_title'], 'cv' => $cm['credit_value'] ?? 4, 'm' => $cm['total_score'], 'g' => $cm['grade'], 'gp' => $cm['grade_point'], 'wp' => $cm['weighted_point'], 'so' => $i]);
                    }
                    $db->commit();
                    $success = 'Transcript generated successfully! GPA: ' . $gpa . ' - ' . $class;
                    require_once __DIR__ . '/../app/models/Notification.php';
                    $stu = $db->prepare('SELECT user_id FROM students WHERE id = :sid');
                    $stu->execute(['sid' => $studentId]);
                    $uid = (int) $stu->fetchColumn();
                    Notification::create($uid, 'Transcript Generated', 'Your official transcript has been generated. GPA: ' . $gpa . ' - ' . $class, 'transcript');
                } catch (Throwable $e) {
                    $db->rollBack();
                    $error = 'Error generating transcript: ' . $e->getMessage();
                }
            }
        }
    }
}

$allCourses = $db->query('SELECT id, title, code FROM courses ORDER BY title')->fetchAll();
$students = $db->query('SELECT students.id, users.full_name, students.program_id, programs.name AS program_name FROM students INNER JOIN users ON users.id = students.user_id INNER JOIN programs ON programs.id = students.program_id ORDER BY users.full_name')->fetchAll();
$submittedMarks = $db->query('SELECT sm.*, users.full_name AS student_name, courses.title AS course_title, courses.code AS course_code FROM student_marks sm INNER JOIN students ON students.id = sm.student_id INNER JOIN users ON users.id = students.user_id INNER JOIN courses ON courses.id = sm.course_id WHERE sm.status = "submitted" ORDER BY sm.updated_at DESC')->fetchAll();
$approvedMarks = $db->query('SELECT sm.*, users.full_name AS student_name, courses.title AS course_title, courses.code AS course_code FROM student_marks sm INNER JOIN students ON students.id = sm.student_id INNER JOIN users ON users.id = students.user_id INNER JOIN courses ON courses.id = sm.course_id WHERE sm.status = "approved" ORDER BY sm.updated_at DESC')->fetchAll();
$transcripts = $db->query('SELECT t.*, users.full_name AS student_name FROM transcripts t INNER JOIN students ON students.id = t.student_id INNER JOIN users ON users.id = students.user_id ORDER BY t.generated_at DESC LIMIT 20')->fetchAll();

$pageTitle = 'Grading & Transcripts';
require __DIR__ . '/../views/layouts/header.php';
?>
<main class="app-shell">
    <?php require __DIR__ . '/../views/layouts/sidebar.php'; ?>
    <section class="workspace">
        <div class="workspace__head">
            <div><span class="eyebrow">Super admin</span><h1>Grading & Transcript Management</h1></div>
        </div>
        <?php if ($success): ?><p class="notice notice--success"><?= e($success) ?></p><?php endif; ?>
        <?php if ($error): ?><p class="notice notice--error"><?= e($error) ?></p><?php endif; ?>

        <div class="stat-grid">
            <div class="stat-tile three-d-tile"><i class="fa-solid fa-clock"></i><strong><?= count($submittedMarks) ?></strong><span>Pending Approval</span></div>
            <div class="stat-tile three-d-tile"><i class="fa-solid fa-check-circle"></i><strong><?= count($approvedMarks) ?></strong><span>Approved Marks</span></div>
            <div class="stat-tile three-d-tile"><i class="fa-solid fa-file-lines"></i><strong><?= count($transcripts) ?></strong><span>Transcripts Generated</span></div>
        </div>

        <?php if (!empty($submittedMarks)): ?>
            <section class="panel three-d-card">
                <div class="panel__head"><h2><i class="fa-solid fa-clock"></i> Pending Mark Approvals</h2></div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Student</th><th>Course</th><th>CA</th><th>Exam</th><th>Total</th><th>Grade</th><th>Action</th></tr></thead>
                        <tbody>
                            <?php foreach ($submittedMarks as $sm): ?>
                                <tr>
                                    <td><?= e($sm['student_name']) ?></td>
                                    <td><?= e($sm['course_code']) ?> - <?= e($sm['course_title']) ?></td>
                                    <td><?= (float) $sm['continuous_assessment'] ?></td>
                                    <td><?= (float) $sm['exam_score'] ?></td>
                                    <td><strong><?= (float) $sm['total_score'] ?></strong></td>
                                    <td><?= e($sm['grade']) ?></td>
                                    <td>
                                        <form method="post" style="display:inline">
                                            <?= Security::csrfField() ?>
                                            <input type="hidden" name="mark_id" value="<?= (int) $sm['id'] ?>">
                                            <button class="btn btn--primary btn--3d" name="status" value="approved" style="font-size:12px;min-height:30px;padding:0 10px"><i class="fa-solid fa-check"></i> Approve</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php
                $courseGroups = [];
                foreach ($submittedMarks as $sm) { $courseGroups[(int) $sm['course_id']] = (int) $sm['course_id']; }
                foreach ($courseGroups as $cid): ?>
                    <form method="post" style="margin-top:12px">
                        <?= Security::csrfField() ?>
                        <input type="hidden" name="course_id" value="<?= $cid ?>">
                        <button class="btn btn--ghost btn--3d" name="status" value="approve_all"><i class="fa-solid fa-check-double"></i> Approve All for Course #<?= $cid ?></button>
                    </form>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>

        <section class="panel three-d-card">
            <div class="panel__head"><h2><i class="fa-solid fa-file-pen"></i> Generate Transcript</h2></div>
            <form method="post" style="display:flex;gap:12px;align-items:end;flex-wrap:wrap;margin-top:12px">
                <?= Security::csrfField() ?>
                <input type="hidden" name="action" value="generate_transcript">
                <label style="min-width:200px;flex:1">
                    Student
                    <select name="student_id" required>
                        <option value="">-- Select --</option>
                        <?php foreach ($students as $s): ?>
                            <option value="<?= (int) $s['id'] ?>"><?= e($s['full_name']) ?> (<?= e($s['program_name']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label style="min-width:150px">
                    Program ID
                    <input type="number" name="program_id" value="2" required>
                </label>
                <button class="btn btn--primary btn--3d" type="submit"><i class="fa-solid fa-file-lines"></i> Generate Transcript</button>
            </form>
        </section>

        <?php if (!empty($transcripts)): ?>
            <section class="panel three-d-card">
                <div class="panel__head"><h2><i class="fa-solid fa-file-lines"></i> Recent Transcripts</h2></div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Student</th><th>GPA</th><th>Classification</th><th>Generated</th><th>View</th></tr></thead>
                        <tbody>
                            <?php foreach ($transcripts as $t): ?>
                                <tr>
                                    <td><?= e($t['student_name']) ?></td>
                                    <td><strong><?= (float) $t['gpa'] ?></strong></td>
                                    <td><?= e($t['degree_classification']) ?></td>
                                    <td><?= e($t['generated_at']) ?></td>
                                    <td><a class="btn btn--ghost btn--3d" href="transcript.php?student_id=<?= (int) $t['student_id'] ?>" style="font-size:12px;min-height:30px;padding:0 10px"><i class="fa-solid fa-eye"></i> View</a></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        <?php endif; ?>
    </section>
</main>
<?php require __DIR__ . '/../views/layouts/footer.php'; ?>
