<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/core/Auth.php';
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/core/Security.php';
require_once __DIR__ . '/../app/models/Quiz.php';
require_once __DIR__ . '/../app/models/Lesson.php';
require_once __DIR__ . '/../app/models/Gradebook.php';
require_once __DIR__ . '/../app/models/Notification.php';
require_once __DIR__ . '/../app/helpers/Mail.php';

if (!Auth::check()) { header('Location: signup.php'); exit; }
$role = Auth::role();
$userId = (int) Auth::userId();

$action = $_GET['action'] ?? 'list';
$quizId = (int) ($_GET['id'] ?? 0);
$courseId = (int) ($_GET['course_id'] ?? 0);
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'create') {
    Security::requireCsrf();
    if ($role !== 'teacher' && $role !== 'admin') {
        http_response_code(403);
        exit('Only teachers can create quizzes.');
    }
    $title = trim($_POST['title'] ?? '');
    $instructions = trim($_POST['instructions'] ?? '');
    $totalMarks = (float) ($_POST['total_marks'] ?? 100);
    $opensAt = $_POST['opens_at'] ?: null;
    $closesAt = $_POST['closes_at'] ?: null;
    $timeLimit = isset($_POST['time_limit']) && $_POST['time_limit'] !== '' ? (int) $_POST['time_limit'] : null;
    $generateMode = $_POST['generate_mode'] ?? 'manual';
    if ($title && $courseId) {
        $db = Database::connection();
        if ($role === 'teacher') {
            $own = $db->prepare('SELECT 1 FROM courses WHERE id = :id AND teacher_id = :tid');
            $own->execute(['id' => $courseId, 'tid' => Auth::teacherId()]);
            if (!$own->fetchColumn()) {
                http_response_code(403);
                exit('You can only create quizzes in your own courses.');
            }
        }
        $qid = Quiz::create($courseId, $title, $instructions, $totalMarks, $opensAt, $closesAt, $timeLimit);
        if ($generateMode === 'auto') {
            $topic = trim($_POST['topic'] ?? $title);
            $count = min((int) ($_POST['question_count'] ?? 5), 20);
            Quiz::generateAuto($qid, $topic, $count);
        }
        $success = 'Quiz created successfully.';
        if ($role === 'teacher' || $role === 'admin') {
            Notification::broadcastToClassroom(
                (int) ($_POST['classroom_id'] ?? 0), 'New Quiz: ' . $title,
                'A new quiz "' . $title . '" has been published. Check your quizzes page.',
                'quiz'
            );
        }
    } else {
        $error = 'Quiz title and course are required.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'add_question' && $quizId) {
    Security::requireCsrf();
    if ($role !== 'teacher' && $role !== 'admin') {
        http_response_code(403);
        exit('Only teachers can add questions.');
    }
    $db = Database::connection();
    if ($role === 'teacher') {
        $own = $db->prepare('SELECT 1 FROM quizzes q JOIN courses c ON c.id = q.course_id WHERE q.id = :qid AND c.teacher_id = :tid');
        $own->execute(['qid' => $quizId, 'tid' => Auth::teacherId()]);
        if (!$own->fetchColumn()) {
            http_response_code(403);
            exit('You can only manage quizzes in your own courses.');
        }
    }
    $question = trim($_POST['question'] ?? '');
    $qtype = $_POST['question_type'] ?? 'multiple_choice';
    $marks = (float) ($_POST['marks'] ?? 10);
    $options = null;
    $correctAnswer = trim($_POST['correct_answer'] ?? '');
    if (in_array($qtype, ['multiple_choice', 'true_false'])) {
        $opts = $_POST['options'] ?? [];
        $options = array_values(array_filter($opts, fn($o) => trim($o) !== ''));
    }
    if ($question) {
        $last = Database::connection()->prepare('SELECT COALESCE(MAX(sort_order),-1)+1 FROM quiz_questions WHERE quiz_id=:q');
        $last->execute(['q' => $quizId]);
        $sort = (int) $last->fetchColumn();
        Quiz::addQuestion($quizId, $question, $qtype, $options, $correctAnswer, $marks, $sort);
        $success = 'Question added.';
    } else { $error = 'Question text is required.'; }
}

$db = Database::connection();

$attemptId = (int) ($_GET['attempt'] ?? 0);
$gradeData = null;
if ($attemptId && ($role === 'teacher' || $role === 'admin')) {
    $gradeData = Quiz::attemptWithQuestions($attemptId);
    if (!$gradeData) {
        $error = 'Attempt not found.';
        $attemptId = 0;
    } elseif ($role === 'teacher') {
        $own = $db->prepare('SELECT 1 FROM courses WHERE id = :cid AND teacher_id = :tid');
        $own->execute(['cid' => $gradeData['attempt']['course_id'], 'tid' => Auth::teacherId()]);
        if (!$own->fetchColumn()) {
            $error = 'You can only grade attempts in your own courses.';
            $gradeData = null;
            $attemptId = 0;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($action === 'save_grades' || $action === 'grade_attempt')) {
    Security::requireCsrf();
    if ($role !== 'teacher' && $role !== 'admin') {
        http_response_code(403);
        exit('Only teachers can grade quizzes.');
    }
    $attemptId = (int) ($_POST['attempt_id'] ?? 0);
    $gradeData = Quiz::attemptWithQuestions($attemptId);
    if (!$gradeData) {
        $error = 'Attempt not found.';
    } elseif ($role === 'teacher') {
        $own = $db->prepare('SELECT 1 FROM courses WHERE id = :cid AND teacher_id = :tid');
        $own->execute(['cid' => $gradeData['attempt']['course_id'], 'tid' => Auth::teacherId()]);
        if (!$own->fetchColumn()) {
            http_response_code(403);
            exit('You can only grade attempts in your own courses.');
        }
        $grades = [];
        foreach ($_POST['grades'] ?? [] as $qid => $g) {
            $grades[(int) $qid] = [
                'score' => (float) ($g['score'] ?? 0),
                'feedback' => trim($g['feedback'] ?? ''),
            ];
        }
        Quiz::saveManualGrades($attemptId, $grades);
        $success = 'Manual grades saved.';
        $attemptId = 0;
        $gradeData = null;
    } else {
        $grades = [];
        foreach ($_POST['grades'] ?? [] as $qid => $g) {
            $grades[(int) $qid] = [
                'score' => (float) ($g['score'] ?? 0),
                'feedback' => trim($g['feedback'] ?? ''),
            ];
        }
        Quiz::saveManualGrades($attemptId, $grades);
        $success = 'Manual grades saved.';
        $attemptId = 0;
        $gradeData = null;
    }
}

$pageTitle = 'Quizzes';
$hideTopbar = true;
require __DIR__ . '/../views/layouts/header.php';

$quizzes = $courseId ? Quiz::forCourse($courseId) : [];
$quiz = $quizId ? Quiz::find($quizId) : null;
$db = Database::connection();
if ($role === 'teacher') {
    $courses = $db->prepare(
        'SELECT c.*, users.full_name AS teacher FROM courses c LEFT JOIN teachers t ON t.id = c.teacher_id LEFT JOIN users ON users.id = t.user_id WHERE c.teacher_id = :tid ORDER BY c.created_at DESC'
    );
    $courses->execute(['tid' => Auth::teacherId()]);
    $courses = $courses->fetchAll();
    if ($quiz) {
        $own = $db->prepare('SELECT 1 FROM quizzes q JOIN courses c ON c.id = q.course_id WHERE q.id = :qid AND c.teacher_id = :tid');
        $own->execute(['qid' => $quizId, 'tid' => Auth::teacherId()]);
        if (!$own->fetchColumn()) {
            http_response_code(403);
            exit('You can only view quizzes in your own courses.');
        }
    }
} else {
    $courses = $db->query(
        'SELECT c.*, users.full_name AS teacher FROM courses c LEFT JOIN teachers t ON t.id = c.teacher_id LEFT JOIN users ON users.id = t.user_id ORDER BY c.created_at DESC'
    )->fetchAll();
}
$questions = $quiz ? Quiz::questions($quizId) : [];
$attempts = $quiz ? Quiz::attempts($quizId) : [];
?>

<main class="app-shell">
    <?php require __DIR__ . '/../views/layouts/sidebar.php'; ?>
    <section class="workspace">
        <div class="workspace__head">
            <div><span class="eyebrow">Assessments</span><h1>Quizzes</h1></div>
            <div class="workspace__head-actions">
                <?php if ($role !== 'student'): ?>
                    <button class="btn btn--primary btn--3d" onclick="document.getElementById('create-quiz').classList.toggle('hidden')"><i class="fa-solid fa-plus"></i> New quiz</button>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($error): ?><p class="notice notice--error"><i class="fa-solid fa-circle-exclamation"></i> <?= e($error) ?></p><?php endif; ?>
        <?php if ($success): ?><p class="notice notice--success"><i class="fa-solid fa-circle-check"></i> <?= e($success) ?></p><?php endif; ?>

        <?php if ($role !== 'student'): ?>
        <section class="panel three-d-card hidden" id="create-quiz">
            <h2>Create quiz</h2>
            <form method="post" action="quizzes.php?action=create" class="upload-form">
                <?= Security::csrfField() ?>
                <label>Title <input type="text" name="title" placeholder="e.g. Mid-term assessment" required></label>
                <label>Course
                    <select name="course_id" required>
                        <?php foreach ($courses as $c): ?>
                            <option value="<?= (int) $c['id'] ?>" <?= $courseId === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['title']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="upload-form__wide">Instructions <textarea name="instructions" rows="2" placeholder="Instructions for students"></textarea></label>
                <label>Total marks <input type="number" name="total_marks" value="100" min="1" step="0.5"></label>
                 <label>Opens at <input type="datetime-local" name="opens_at"></label>
                 <label>Closes at <input type="datetime-local" name="closes_at"></label>
                 <label>Time limit (minutes) <input type="number" name="time_limit" value="" min="1" placeholder="e.g. 60 (leave blank for no limit)">
                     <small class="muted">Optional: restricts how long students have to complete the quiz once started</small>
                 </label>
                <label class="upload-form__wide">Generate mode
                    <select name="generate_mode" onchange="document.getElementById('auto-options').classList.toggle('hidden', this.value!=='auto')">
                        <option value="manual">Manual — I will add questions</option>
                        <option value="auto">Auto-generate questions</option>
                    </select>
                </label>
                <div id="auto-options" class="hidden upload-form__wide" style="display:grid;gap:12px">
                    <label>Topic for generation <input type="text" name="topic" placeholder="e.g. Curriculum development"></label>
                    <label>Number of questions <input type="number" name="question_count" value="5" min="1" max="20"></label>
                </div>
                <input type="hidden" name="classroom_id" value="<?= (int) ($_GET['classroom'] ?? 0) ?>">
                <button class="btn btn--primary upload-form__wide" type="submit"><i class="fa-solid fa-paper-plane"></i> Create quiz</button>
            </form>
            <style>.hidden{display:none!important}</style>
        </section>
        <?php endif; ?>

        <?php if ($quiz): ?>
            <section class="panel three-d-card">
                <div class="panel__head">
                    <h2><?= e($quiz['title']) ?></h2>
                    <div style="display:flex;gap:8px;align-items:center">
                        <span class="status"><?= (float) $quiz['total_marks'] ?> marks</span>
                        <?php if ($role === 'student'): ?>
                            <a class="btn btn--primary btn--3d" href="quiz_take.php?id=<?= (int) $quiz['id'] ?>"><i class="fa-solid fa-pen-ruler"></i> Take quiz</a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if ($quiz['instructions']): ?><p><?= e($quiz['instructions']) ?></p><?php endif; ?>
                <?php if ($quiz['opens_at']): ?><p class="muted">Opens: <?= e($quiz['opens_at']) ?> • Closes: <?= e($quiz['closes_at'] ?? 'N/A') ?></p><?php endif; ?>
                <?php if ($quiz['time_limit']): ?><p class="muted"><i class="fa-solid fa-clock"></i> Time limit: <?= (int) $quiz['time_limit'] ?> minutes</p><?php endif; ?>

                <h3>Questions (<?= count($questions) ?>)</h3>
                <?php if (empty($questions)): ?>
                    <div class="empty-state"><i class="fa-solid fa-circle-question"></i><h3>No questions yet</h3><p>Add questions to this quiz.</p></div>
                <?php else: ?>
                    <div class="classwork-list">
                        <?php foreach ($questions as $i => $q): ?>
                            <div class="classwork-card">
                                <div class="classwork-card__icon classwork-card__icon--assignment"><i class="fa-solid fa-circle-question"></i></div>
                                <div class="classwork-card__body">
                                    <h3><?= e($q['question']) ?></h3>
                                    <p>
                                        <span class="badge badge--assignment"><?= e(str_replace('_', ' ', $q['question_type'])) ?></span>
                                        • <?= (float) $q['marks'] ?> pts
                                        <?php if ($q['options']): ?> • <?= count($q['options']) ?> options<?php endif; ?>
                                    </p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if ($role !== 'student'): ?>
                <details style="margin-top:16px">
                    <summary style="cursor:pointer;font-weight:700;padding:8px 0">Add a question</summary>
                    <form method="post" action="quizzes.php?action=add_question&id=<?= (int) $quiz['id'] ?>" class="upload-form" style="margin-top:12px">
                        <?= Security::csrfField() ?>
                        <label>Question <textarea name="question" rows="2" required></textarea></label>
                        <label>Type <select name="question_type" onchange="document.querySelectorAll('.q-opts').forEach(e=>e.classList.toggle('hidden',true));document.getElementById('qopts-'+this.value).classList.remove('hidden')">
                            <option value="multiple_choice">Multiple choice</option>
                            <option value="true_false">True / False</option>
                            <option value="short_answer">Short answer</option>
                            <option value="fill_blank">Fill in the blank</option>
                            <option value="essay">Essay</option>
                        </select></label>
                        <div id="qopts-multiple_choice" class="q-opts upload-form__wide">
                            <label>Options (one per line) <textarea name="options[]" rows="3" placeholder="Option A&#10;Option B&#10;Option C&#10;Option D"></textarea></label>
                        </div>
                        <div id="qopts-true_false" class="q-opts"hidden upload-form__wide">
                            <label>Options</label>
                            <div style="display:flex;gap:8px">
                                <label style="flex:1"><input type="radio" name="options[]" value="True" checked> True</label>
                                <label style="flex:1"><input type="radio" name="options[]" value="False"> False</label>
                            </div>
                        </div>
                        <div id="qopts-short_answer" class="q-opts hidden upload-form__wide">
                            <p class="muted">Short answer — grading will be manual.</p>
                        </div>
                        <div id="qopts-fill_blank" class="q-opts hidden upload-form__wide">
                            <p class="muted">Use <code>____</code> in the question text above for each blank. Enter pipe-separated correct answers below (e.g. <code>Paris|London|Berlin</code> for 3 blanks, <code>Paris,London</code> for aliases).</p>
                        </div>
                        <div id="qopts-essay" class="q-opts hidden upload-form__wide">
                            <p class="muted">Essay question — long-form answer, graded manually by your teacher.</p>
                        </div>
                        <label>Correct answer <input type="text" id="correct_answer_input" name="correct_answer" placeholder="e.g. Option A or True (pipe-separated for fill-in-the-blank aliases)"></label>
                        <label>Marks <input type="number" name="marks" value="10" min="0.5" step="0.5"></label>
                        <button class="btn btn--primary upload-form__wide" type="submit"><i class="fa-solid fa-plus"></i> Add question</button>
                    </form>
                </details>
                <?php endif; ?>

                <?php if ($role !== 'student' && !empty($attempts)): ?>
                <h3 style="margin-top:24px">Attempts</h3>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Student</th><th>Score</th><th>Submitted</th><th>Action</th></tr></thead>
                        <tbody>
                            <?php foreach ($attempts as $a): ?>
                                <tr>
                                    <td><?= e($a['student_name']) ?><br><small><?= e($a['student_number'] ?? '') ?></small></td>
                                    <td><strong><?= number_format((float) ($a['score'] ?? 0), 1) ?></strong> / <?= (float) ($quiz['total_marks'] ?? 100) ?></td>
                                    <td><?= e($a['submitted_at'] ?? 'Not yet') ?></td>
                                    <td><a class="btn btn--ghost btn--3d" href="quizzes.php?id=<?= (int) $quiz['id'] ?>&attempt=<?= (int) $a['id'] ?>" style="font-size:12px;padding:4px 8px"><i class="fa-solid fa-pen"></i> Grade</a></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>

                <?php if ($gradeData): ?>
                <section class="panel three-d-card" style="margin-top:24px">
                    <div class="panel__head">
                        <h2><i class="fa-solid fa-pen"></i> Manual grading: <?= e($gradeData['attempt']['student_name']) ?> — <?= e($gradeData['attempt']['quiz_title']) ?></h2>
                    </div>
                    <?php
                    $manuallyGradedTypes = ['short_answer', 'fill_blank', 'essay'];
                    $manualQuestions = [];
                    foreach ($gradeData['questions'] as $q) {
                        if (in_array($q['question_type'], $manuallyGradedTypes)) {
                            $manualQuestions[] = $q;
                        }
                    }
                    ?>
                    <?php if (empty($manualQuestions)): ?>
                        <div class="empty-state"><i class="fa-solid fa-check-circle"></i><h3>No manual questions</h3><p>This quiz has no short-answer, fill-in-the-blank, or essay questions requiring manual grading.</p></div>
                    <?php else: ?>
                        <form method="post" action="quizzes.php?action=save_grades&id=<?= (int) $quiz['id'] ?>" class="upload-form">
                            <?= Security::csrfField() ?>
                            <input type="hidden" name="action" value="save_grades">
                            <input type="hidden" name="attempt_id" value="<?= (int) $gradeData['attempt']['id'] ?>">
                            <div class="table-wrap">
                                <table>
                                    <thead><tr><th>#</th><th>Question</th><th>Type</th><th>Student answer</th><th>Max. marks</th><th>Earned</th><th>Feedback</th></tr></thead>
                                    <tbody>
                                        <?php foreach ($manualQuestions as $i => $q):
                                            $ans = $gradeData['answers'][$q['id']] ?? null;
                                            $earned = $ans ? number_format((float) ($ans['marks_awarded'] ?? 0), 1) : '0';
                                            $feedback = $ans['feedback'] ?? '';
                                            $alreadyGraded = $ans && !empty($ans['manually_graded']);
                                        ?>
                                            <tr>
                                                <td><?= $i + 1 ?></td>
                                                <td><?= e($q['question']) ?></td>
                                                <td><span class="badge badge--assignment"><?= e(str_replace('_', ' ', $q['question_type'])) ?></span></td>
                                                <td><small><?= e($ans['given'] ?? '(no answer)') ?></small></td>
                                                <td><?= number_format((float) $q['marks'], 1) ?></td>
                                                <td style="min-width:100px"><input type="number" name="grades[<?= (int) $q['id'] ?>][score]" value="<?= $earned ?>" min="0" max="<?= (float) $q['marks'] ?>" step="0.5" style="width:80px" <?= $alreadyGraded ? '' : '' ?>></td>
                                                <td><textarea name="grades[<?= (int) $q['id'] ?>][feedback]" rows="2" placeholder="Optional feedback..." style="width:200px"><?= e($feedback) ?></textarea></td>
                                            </tr>
                                        <?php endforeach; ?></tbody>
                                </table>
                            </div>
                            <div style="margin-top:16px;display:flex;gap:8px">
                                <button class="btn btn--primary btn--3d" type="submit"><i class="fa-solid fa-save"></i> Save grades</button>
                                <a class="btn btn--ghost btn--3d" href="quizzes.php?id=<?= (int) $quiz['id'] ?>"><i class="fa-solid fa-arrow-left"></i> Back to quiz</a>
                            </div>
                        </form>
                    <?php endif; ?>
                </section>
                <?php endif; ?>
            </section>
        <?php endif; ?>

        <section class="panel three-d-card">
            <div class="panel__head"><h2><i class="fa-solid fa-pen-ruler"></i> All quizzes</h2></div>
            <?php if (empty($quizzes)): ?>
                <div class="empty-state"><i class="fa-solid fa-pen-ruler"></i><h3>No quizzes yet</h3><p>Create a quiz to assess student knowledge.</p></div>
            <?php else: ?>
                <div class="classroom-card-grid">
                    <?php foreach ($quizzes as $q): ?>
                        <a class="classroom-card" href="<?= $role === 'student' ? 'quiz_take.php' : 'quizzes.php' ?>?id=<?= (int) $q['id'] ?>">
                            <div class="classroom-card__banner"></div>
                            <div class="classroom-card__body">
                                <h3><?= e($q['title']) ?></h3>
                                <small><i class="fa-solid fa-weight-scale"></i> <?= (float) $q['total_marks'] ?> marks • <?= (int) $q['attempts'] ?> attempts<?= $q['time_limit'] ? ' • <i class="fa-solid fa-clock"></i>' . (int) $q['time_limit'] . ' min' : '' ?></small>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </section>
</main>

<script>
document.querySelectorAll('textarea[name="options[]"]').forEach(t => {
    t.addEventListener('change', function() { this.value = this.value.split('\n').filter(l => l.trim()).join('\n'); });
});
</script>
<?php require __DIR__ . '/../views/layouts/footer.php'; ?>
