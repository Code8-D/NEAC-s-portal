<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/core/Auth.php';
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/core/Security.php';
require_once __DIR__ . '/../app/models/Gradebook.php';
require_once __DIR__ . '/../app/models/Quiz.php';
require_once __DIR__ . '/../app/models/Attendance.php';

if (!Auth::check()) { header('Location: signup.php'); exit; }
$role = Auth::role();
$userId = (int) Auth::userId();

$pageTitle = 'Results & Gradebook';
$hideTopbar = true;
require __DIR__ . '/../views/layouts/header.php';

$studentId = $role === 'student' ? (int) Auth::studentId() : 0;
$courseId = (int) ($_GET['course_id'] ?? 0);

$db = Database::connection();
if ($role === 'teacher' && $courseId) {
    $own = $db->prepare('SELECT 1 FROM courses WHERE id = :id AND teacher_id = :tid');
    $own->execute(['id' => $courseId, 'tid' => Auth::teacherId()]);
    if (!$own->fetchColumn()) {
        http_response_code(403);
        exit('You can only view gradebooks for your own courses.');
    }
}

$results = $studentId ? Gradebook::forStudent($studentId) : [];
$gradebook = $courseId ? Gradebook::courseGradebook($courseId) : [];
$quizAttempts = $role === 'student' && $studentId ? Quiz::studentAttempts($studentId) : [];
$attendanceSummary = $role === 'student' && $studentId ? Attendance::summary($studentId) : null;
?>

<main class="app-shell">
    <?php require __DIR__ . '/../views/layouts/sidebar.php'; ?>
    <section class="workspace">
        <div class="workspace__head">
            <div><span class="eyebrow">Academic records</span><h1>Results & gradebook</h1></div>
        </div>

        <?php if ($role === 'student'): ?>
            <div class="stat-grid">
                <div class="three-d-tile"><i class="fa-solid fa-chart-simple"></i><strong><?= count($results) ?></strong><span>Results published</span></div>
                <div class="three-d-tile"><i class="fa-solid fa-pen-ruler"></i><strong><?= count($quizAttempts) ?></strong><span>Quizzes taken</span></div>
                <div class="three-d-tile"><i class="fa-solid fa-calendar-check"></i><strong><?= $attendanceSummary ? $attendanceSummary['percent'] . '%' : 'N/A' ?></strong><span>Attendance rate</span></div>
                <div class="three-d-tile"><i class="fa-solid fa-trophy"></i><strong><?php
                    $avg = 0;
                    if (count($results) > 0) { $avg = round(array_sum(array_column($results, 'score')) / count($results), 1); }
                    echo $avg;
                ?></strong><span>Average score</span></div>
            </div>

            <section class="panel three-d-card">
                <div class="panel__head"><h2><i class="fa-solid fa-file-lines"></i> Published results</h2></div>
                <?php if (empty($results)): ?>
                    <div class="empty-state"><i class="fa-solid fa-file-lines"></i><h3>No results yet</h3><p>Your results will appear here once your teacher publishes them.</p></div>
                <?php else: ?>
                    <div class="table-wrap">
                        <table>
                            <thead><tr><th>Course</th><th>Assessment</th><th>Score</th><th>Grade</th><th>Published</th></tr></thead>
                            <tbody>
                                <?php foreach ($results as $r): ?>
                                    <tr>
                                        <td><?= e($r['course_title']) ?></td>
                                        <td><?= e(ucfirst($r['assessment_type'])) ?></td>
                                        <td><strong><?= number_format((float) $r['score'], 1) ?></strong></td>
                                        <td><span class="status"><?= e($r['grade'] ?? '-') ?></span></td>
                                        <td><?= e($r['published_at']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>

            <section class="panel three-d-card">
                <div class="panel__head"><h2><i class="fa-solid fa-pen-ruler"></i> Quiz attempts</h2></div>
                <?php if (empty($quizAttempts)): ?>
                    <div class="empty-state"><i class="fa-solid fa-pen-ruler"></i><h3>No quiz attempts</h3><p>Quizzes you complete will show up here.</p></div>
                <?php else: ?>
                    <div class="classwork-list">
                        <?php foreach ($quizAttempts as $a): ?>
                            <div class="classwork-card">
                                <div class="classwork-card__icon classwork-card__icon--assignment"><i class="fa-solid fa-pen-ruler"></i></div>
                                <div class="classwork-card__body">
                                    <h3><?= e($a['quiz_title']) ?></h3>
                                    <p><?= e($a['course_title']) ?></p>
                                    <small>Score: <strong><?= number_format((float) ($a['score'] ?? 0), 1) ?></strong> / <?= (float) $a['total_marks'] ?> • <?= e($a['submitted_at'] ?? 'Not submitted') ?></small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <?php if ($attendanceSummary && $attendanceSummary['total'] > 0): ?>
            <section class="panel three-d-card">
                <div class="panel__head"><h2><i class="fa-solid fa-calendar-check"></i> Attendance</h2></div>
                <div class="report-grid" style="grid-template-columns:repeat(auto-fill,minmax(120px,1fr))">
                    <article><strong><?= $attendanceSummary['present'] ?></strong><span>Present</span></article>
                    <article><strong><?= $attendanceSummary['absent'] ?></strong><span>Absent</span></article>
                    <article><strong><?= $attendanceSummary['late'] ?></strong><span>Late</span></article>
                    <article><strong><?= $attendanceSummary['excused'] ?></strong><span>Excused</span></article>
                </div>
            </section>
            <?php endif; ?>

        <?php elseif ($role === 'teacher' || $role === 'admin'): ?>
            <section class="panel three-d-card">
                <div class="panel__head"><h2><i class="fa-solid fa-school"></i> Gradebook by course</h2></div>
                <?php
                if ($role === 'teacher') {
                    $courses = $db->prepare('SELECT c.* FROM courses c WHERE c.teacher_id = :tid ORDER BY c.title');
                    $courses->execute(['tid' => Auth::teacherId()]);
                    $courses = $courses->fetchAll();
                } else {
                    $courses = $db->query('SELECT c.* FROM courses c ORDER BY c.title')->fetchAll();
                }
                ?>
                <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:12px">
                    <?php foreach ($courses as $c): ?>
                        <a class="btn <?= $courseId === (int) $c['id'] ? 'btn--primary' : 'btn--ghost' ?> btn--3d" href="results.php?course_id=<?= (int) $c['id'] ?>"><?= e($c['title']) ?></a>
                    <?php endforeach; ?>
                </div>
            </section>

            <?php if ($courseId && !empty($gradebook)): ?>
            <section class="panel three-d-card">
                <div class="panel__head"><h2><i class="fa-solid fa-users"></i> Student gradebook</h2></div>
                <?php foreach ($gradebook as $s): ?>
                    <details style="margin-top:12px;padding:12px;border:1px solid var(--line);border-radius:8px">
                        <summary style="cursor:pointer;font-weight:700;display:flex;justify-content:space-between;align-items:center">
                            <span><?= e($s['student_name']) ?> <small class="muted"><?= e($s['student_number'] ?? '') ?></small></span>
                            <span class="status">Avg: <?= number_format($s['average'], 1) ?></span>
                        </summary>
                        <?php if (empty($s['results'])): ?>
                            <p class="muted" style="margin-top:8px">No results recorded.</p>
                        <?php else: ?>
                            <div class="table-wrap" style="margin-top:8px">
                                <table>
                                    <thead><tr><th>Assessment</th><th>Score</th><th>Grade</th><th>Published</th></tr></thead>
                                    <tbody>
                                        <?php foreach ($s['results'] as $r): ?>
                                            <tr>
                                                <td><?= e(ucfirst($r['assessment_type'])) ?></td>
                                                <td><?= number_format((float) $r['score'], 1) ?></td>
                                                <td><span class="status"><?= e($r['grade'] ?? '-') ?></span></td>
                                                <td><?= e($r['published_at']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </details>
                <?php endforeach; ?>
            </section>
            <?php endif; ?>
        <?php endif; ?>
    </section>
</main>
<?php require __DIR__ . '/../views/layouts/footer.php'; ?>
