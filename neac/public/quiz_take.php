<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/core/Auth.php';
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/core/Security.php';
require_once __DIR__ . '/../app/models/Quiz.php';

if (!Auth::check()) { header('Location: signup.php'); exit; }
$role = Auth::role();
$studentId = (int) Auth::studentId();

if (!$studentId) {
    http_response_code(403);
    exit('Only students can take quizzes.');
}

$quizId = (int) ($_GET['id'] ?? 0);
$quiz = $quizId > 0 ? Quiz::find($quizId) : null;
if (!$quiz) {
    http_response_code(404);
    exit('Quiz not found.');
}

$db = Database::connection();
$enrolled = $db->prepare('SELECT 1 FROM enrollments WHERE student_id = :s AND course_id = :c AND status = "active" LIMIT 1');
$enrolled->execute(['s' => $studentId, 'c' => (int) $quiz['course_id']]);
if (!$enrolled->fetchColumn()) {
    http_response_code(403);
    exit('You are not enrolled in the course for this quiz.');
}

$attemptStmt = $db->prepare('SELECT * FROM quiz_attempts WHERE quiz_id = :q AND student_id = :s ORDER BY id DESC LIMIT 1');
$attemptStmt->execute(['q' => $quizId, 's' => $studentId]);
$attempt = $attemptStmt->fetch() ?: null;

$now = time();
$opensAt = $quiz['opens_at'] ? strtotime($quiz['opens_at']) : null;
$closesAt = $quiz['closes_at'] ? strtotime($quiz['closes_at']) : null;
$windowError = '';
if ($opensAt && $now < $opensAt) {
    $windowError = 'This quiz opens on ' . date('j M Y, g:ia', $opensAt) . '.';
} elseif ($closesAt && $now > $closesAt) {
    $windowError = 'This quiz closed on ' . date('j M Y, g:ia', $closesAt) . '.';
}

$message = '';
$score = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::requireCsrf();
    if ($windowError !== '') {
        http_response_code(403);
        exit($windowError);
    }
    $attemptId = (int) ($_POST['attempt_id'] ?? 0);
    if (!$attempt || $attempt['id'] !== $attemptId || $attempt['submitted_at']) {
        http_response_code(409);
        exit('This attempt is not active or was already submitted.');
    }
    $answers = [];
    foreach (Quiz::questions($quizId) as $q) {
        $key = 'q_' . $q['id'];
        $raw = $_POST[$key] ?? '';
        if (is_array($raw)) {
            $answers[$key] = implode('|', array_map(fn($v) => trim((string) $v), $raw));
        } else {
            $answers[$key] = trim((string) $raw);
        }
    }
    $score = Quiz::submitAttempt($attemptId, $answers);
    $grade = $quiz['total_marks'] > 0 ? $score / $quiz['total_marks'] * 100 : 0;
    $gradeLetter = $grade >= 80 ? 'A' : ($grade >= 70 ? 'B+' : ($grade >= 60 ? 'B' : ($grade >= 55 ? 'C+' : ($grade >= 50 ? 'C' : ($grade >= 45 ? 'D+' : ($grade >= 40 ? 'D' : 'F'))))));
    $resultStmt = $db->prepare(
        'INSERT INTO results (student_id, course_id, assessment_type, score, grade, remarks, published_at)
         VALUES (:s, :c, :q, :sc, :g, :r, CURRENT_TIMESTAMP)'
    );
    $resultStmt->execute([
        's' => $studentId, 'c' => (int) $quiz['course_id'], 'q' => 'quiz:' . $quizId,
        'sc' => $score, 'g' => $gradeLetter, 'r' => 'Auto-graded quiz',
    ]);
    $attempt = $db->prepare('SELECT * FROM quiz_attempts WHERE id = :id');
    $attempt->execute(['id' => $attemptId]);
    $attempt = $attempt->fetch() ?: null;

    $message = 'Time limit exceeded. Your quiz has been auto-submitted.';
    if ($quiz['time_limit'] && $quiz['time_limit'] > 0) {
        $startedAt = strtotime($attempt['started_at'] ?? 'now');
        $deadline = $startedAt + ($quiz['time_limit'] * 60);
        if (time() > $deadline) {
            $message = 'Time limit exceeded. Your quiz has been auto-submitted.';
        } else {
            $message = 'Quiz submitted. Your auto-graded score is shown below.';
        }
    } else {
        $message = 'Quiz submitted. Your auto-graded score is shown below.';
    }
}

$questions = Quiz::questions($quizId);

if (!$attempt && $windowError === '') {
    $attemptId = Quiz::startAttempt($quizId, $studentId);
    $attempt = $db->prepare('SELECT * FROM quiz_attempts WHERE id = :id');
    $attempt->execute(['id' => $attemptId]);
    $attempt = $attempt->fetch() ?: null;
}

$pageTitle = $quiz['title'] . ' • Quiz';
$hideTopbar = true;
require __DIR__ . '/../views/layouts/header.php';
?>
<main class="app-shell quiz-shell">
    <section class="workspace quiz-workspace">
        <div class="workspace__head">
            <div>
                <span class="eyebrow"><?= e($quiz['course_title']) ?></span>
                <h1><?= e($quiz['title']) ?></h1>
            </div>
            <div class="workspace__head-actions">
                <a class="btn btn--ghost btn--3d" href="quizzes.php"><i class="fa-solid fa-arrow-left"></i> Back to quizzes</a>
            </div>
        </div>

        <?php if ($windowError !== ''): ?>
            <section class="panel three-d-card">
                <div class="empty-state">
                    <i class="fa-solid fa-hourglass-half"></i>
                    <h3>Quiz unavailable</h3>
                    <p><?= e($windowError) ?></p>
                </div>
            </section>
        <?php elseif ($attempt && $attempt['submitted_at']): ?>
            <section class="panel three-d-card">
                <div class="panel__head"><h2><i class="fa-solid fa-circle-check"></i> Quiz completed</h2></div>
                <?php if ($message): ?><p class="notice notice--success"><i class="fa-solid fa-circle-check"></i> <?= e($message) ?></p><?php endif; ?>
                <div class="stat-grid" style="max-width:520px">
                    <div class="three-d-tile"><i class="fa-solid fa-chart-simple"></i><strong><?= number_format((float) $attempt['score'], 1) ?></strong><span>Your score / <?= (float) $quiz['total_marks'] ?> marks</span></div>
                    <div class="three-d-tile"><i class="fa-solid fa-percent"></i><strong><?= $quiz['total_marks'] > 0 ? round($attempt['score'] / $quiz['total_marks'] * 100) : 0 ?>%</strong><span>Percentage</span></div>
                    <div class="three-d-tile"><i class="fa-regular fa-calendar-check"></i><strong><?= e($attempt['submitted_at']) ?></strong><span>Submitted</span></div>
                </div>
                <?php
                $answers = json_decode($attempt['answers_json'] ?: '[]', true);
                if (is_array($answers) && count($answers) > 0):
                ?>
                <h3>Review</h3>
                <div class="classwork-list">
                    <?php foreach ($answers as $i => $a): ?>
                        <div class="classwork-card">
                            <div class="classwork-card__body">
                                <h3>Q<?= $i + 1 ?>. <?= e($questions[$i]['question'] ?? '') ?></h3>
                                <?php if (!empty($a['manually_graded'])): ?>
                                    <p>Your answer: <strong><?= e($a['given'] !== '' ? $a['given'] : '(no answer)') ?></strong>
                                    <span class="status" style="margin-left:8px"><?= $a['correct'] ? '+' . number_format((float) $a['marks_awarded'], 1) . ' pts' : '0 pts' ?></span>
                                    <?php if (!empty($a['feedback'])): ?>
                                        <br><small class="muted"><i class="fa-solid fa-comment"></i> <?= e($a['feedback']) ?></small>
                                    <?php endif; ?>
                                    </p>
                                <?php else: ?>
                                    <p>Your answer: <strong><?= e($a['given'] !== '' ? $a['given'] : '(no answer)') ?></strong>
                                    <span class="status" style="margin-left:8px"><?= $a['correct'] ? '+' . number_format((float) $a['marks_awarded'], 1) . ' pts' : '0 pts' ?></span></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                <p class="muted">Short-answer, fill-in-the-blank, and essay questions marked "0 pts" pending manual review by your teacher.</p>
            </section>
        <?php elseif ($attempt): ?>
            <section class="panel three-d-card">
                <div class="panel__head">
                    <h2><i class="fa-solid fa-pen-ruler"></i> Answer the questions</h2>
                    <span class="status"><?= (float) $quiz['total_marks'] ?> marks total</span>
                </div>
                <?php if ($quiz['instructions']): ?><p><?= e($quiz['instructions']) ?></p><?php endif; ?>
                <p class="muted">Multiple choice and true/false questions are marked automatically. Short answers, fill-in-the-blank, and essay questions are graded manually by your teacher.</p>

                <?php if ($quiz['time_limit'] && !$quiz['time_limit'] <= 0): ?>
                    <div id="timer-bar" style="margin:12px 0;padding:8px 12px;background:rgba(217,163,33,0.15);border-radius:8px;border:1px solid var(--line);font-weight:600;display:flex;justify-content:space-between;align-items:center">
                        <span><i class="fa-solid fa-clock"></i> Time remaining</span>
                        <span id="timer-display" style="font-size:18px;font-variant-numeric tabular-nums">--:--</span>
                    </div>
                <?php endif; ?>

                <form method="post" action="quiz_take.php?id=<?= (int) $quiz['id'] ?>" class="upload-form" id="quiz-form">
                    <?= Security::csrfField() ?>
                    <input type="hidden" name="attempt_id" value="<?= (int) $attempt['id'] ?>">
                    <?php if ($quiz['time_limit'] && $quiz['time_limit'] > 0): ?>
                        <input type="hidden" id="quiz-deadline" value="<?= $quiz['time_limit'] * 60 + strtotime($attempt['started_at']) ?>">
                    <?php endif; ?>
                    <?php foreach ($questions as $i => $q): ?>
                        <fieldset class="quiz-question" data-qtype="<?= e($q['question_type']) ?>">
                            <legend>Q<?= $i + 1 ?>. <span class="badge badge--assignment"><?= e(str_replace('_', ' ', $q['question_type'])) ?></span> <small>(<?= (float) $q['marks'] ?> pts)</small></legend>
                            <?php if ($q['question_type'] === 'fill_blank'): ?>
                                <p class="fill-blank-question">
                                    <?php
                                    $blanks = preg_split('/(_{3,})/', $q['question'], -1, PREG_SPLIT_DELIM_CAPTURE);
                                    $blankIdx = 0;
                                    foreach ($blanks as $part) {
                                        if (preg_match('/^_{3,}$/', $part)) {
                                            $blankIdx++;
                                            echo '<input type="text" name="q_' . (int) $q['id'] . '[]" class="fill-blank-input" autocomplete="off" style="min-width:120px" required>';
                                        } elseif ($part === '') {
                                            continue;
                                        } else {
                                            echo e($part);
                                        }
                                    }
                                    ?>
                                </p>
                                <?php if ($q['correct_answer']): ?>
                                    <p class="muted" style="font-size:12px"><i class="fa-solid fa-info-circle"></i> Expected: <?= e($q['correct_answer']) ?></p>
                                <?php endif; ?>
                            <?php elseif ($q['question_type'] === 'essay'): ?>
                                <p><?= e($q['question']) ?></p>
                                <textarea name="q_<?= (int) $q['id'] ?>" rows="10" placeholder="Type your essay answer here..."></textarea>
                                <p class="muted" style="font-size:12px"><i class="fa-solid fa-info-circle"></i> This question will be graded manually by your teacher.</p>
                            <?php elseif (($q['question_type'] === 'multiple_choice' || $q['question_type'] === 'true_false') && is_array($q['options'])): ?>
                                <p><?= e($q['question']) ?></p>
                                <div class="quiz-options">
                                    <?php foreach ($q['options'] as $opt): ?>
                                        <label class="quiz-option">
                                            <input type="radio" name="q_<?= (int) $q['id'] ?>" value="<?= e($opt) ?>" required>
                                            <span><?= e($opt) ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p><?= e($q['question']) ?></p>
                                <textarea name="q_<?= (int) $q['id'] ?>" rows="3" placeholder="Type your answer..."></textarea>
                            <?php endif; ?>
                        </fieldset>
                    <?php endforeach; ?>
                    <button class="btn btn--primary btn--3d upload-form__wide" type="submit" id="quiz-submit-btn" onclick="return confirm('Submit your quiz? You cannot change your answers afterwards.');"><i class="fa-solid fa-paper-plane"></i> Submit quiz</button>
                </form>
            </section>

            <?php if ($quiz['time_limit'] && $quiz['time_limit'] > 0): ?>
            <script>
            (function() {
                const deadline = parseInt(document.getElementById('quiz-deadline')?.value || '0', 10);
                if (!deadline) return;
                const display = document.getElementById('timer-display');
                const form = document.getElementById('quiz-form');
                const submitBtn = document.getElementById('quiz-submit-btn');
                if (!display || !form) return;

                function updateTimer() {
                    const now = Math.floor(Date.now() / 1000);
                    const remaining = deadline - now;
                    if (remaining <= 0) {
                        display.textContent = '00:00';
                        display.style.color = '#ef4444';
                        display.parentElement.style.background = 'rgba(239,68,68,0.15)';
                        display.parentElement.style.borderColor = '#ef4444';
                        if (submitBtn) submitBtn.disabled = true;
                        form.submit();
                        return;
                    }
                    const m = Math.floor(remaining / 60);
                    const s = remaining % 60;
                    display.textContent = String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
                    if (remaining <= 60) {
                        display.style.color = '#ef4444';
                    } else if (remaining <= 300) {
                        display.style.color = '#ea580c';
                    }
                }

                updateTimer();
                setInterval(updateTimer, 1000);
            })();
            </script>
            <?php endif; ?>
        <?php else: ?>
            <section class="panel three-d-card">
                <div class="empty-state">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <h3>No active attempt</h3>
                    <p>Refresh the page to start a new attempt.</p>
                </div>
            </section>
        <?php endif; ?>
    </section>
</main>
<?php require __DIR__ . '/../views/layouts/footer.php'; ?>
