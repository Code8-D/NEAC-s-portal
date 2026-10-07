<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/core/Auth.php';
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/core/Security.php';
require_once __DIR__ . '/../app/models/Discussion.php';
require_once __DIR__ . '/../app/models/Notification.php';

if (!Auth::check()) { header('Location: signup.php'); exit; }
$role = Auth::role();
$userId = (int) Auth::userId();

$action = $_GET['action'] ?? 'list';
$threadId = (int) ($_GET['thread_id'] ?? 0);
$courseId = (int) ($_GET['course_id'] ?? 0);
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'create_thread') {
    Security::requireCsrf();
    if ($role === 'student') {
        http_response_code(403);
        exit('Only teachers and admins can create discussion threads.');
    }
    $title = trim($_POST['title'] ?? '');
    if ($title && $courseId) {
        Discussion::createThread($courseId, $userId, $title);
        $success = 'Discussion thread created.';
    } else { $error = 'Title is required.'; }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'post_reply') {
    Security::requireCsrf();
    if ($role !== 'admin' && $role !== 'teacher') {
        $thread = Discussion::findThread($threadId);
        if (!$thread) {
            http_response_code(404);
            exit('Thread not found.');
        }
        $enrolled = Database::connection()->prepare(
            'SELECT 1 FROM enrollments WHERE student_id = :s AND course_id = :c'
        );
        $enrolled->execute(['s' => Auth::studentId(), 'c' => (int) $thread['course_id']]);
        if (!$enrolled->fetchColumn()) {
            http_response_code(403);
            exit('You are not enrolled in this course.');
        }
    }
    $body = trim($_POST['body'] ?? '');
    if ($body && $threadId) {
        Discussion::post($threadId, $userId, $body);
        $thread = Discussion::findThread($threadId);
        if ($thread) {
            Notification::broadcastToClassroom(
                (int) ($thread['course_id'] ?? 0), 'New discussion post',
                'A new reply was posted in "' . $thread['title'] . '".',
                'discussion'
            );
        }
        $success = 'Reply posted.';
    } else { $error = 'Reply body is required.'; }
}

$pageTitle = 'Discussions';
$hideTopbar = true;
require __DIR__ . '/../views/layouts/header.php';

$courses = Database::connection()->query(
    'SELECT c.*, users.full_name AS teacher FROM courses c LEFT JOIN teachers t ON t.id = c.teacher_id LEFT JOIN users ON users.id = t.user_id ORDER BY c.created_at DESC'
)->fetchAll();
$threads = $courseId ? Discussion::threads($courseId) : [];
$thread = $threadId ? Discussion::findThread($threadId) : null;
$posts = $thread ? Discussion::posts($threadId) : [];
?>

<main class="app-shell">
    <?php require __DIR__ . '/../views/layouts/sidebar.php'; ?>
    <section class="workspace">
        <div class="workspace__head">
            <div><span class="eyebrow">Forum</span><h1>Discussions</h1></div>
            <div class="workspace__head-actions">
                <?php if ($courseId): ?>
                    <button class="btn btn--primary btn--3d" onclick="document.getElementById('new-thread').classList.toggle('hidden')"><i class="fa-solid fa-plus"></i> New thread</button>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($error): ?><p class="notice notice--error"><i class="fa-solid fa-circle-exclamation"></i> <?= e($error) ?></p><?php endif; ?>
        <?php if ($success): ?><p class="notice notice--success"><i class="fa-solid fa-circle-check"></i> <?= e($success) ?></p><?php endif; ?>

        <section class="panel three-d-card">
            <div class="panel__head">
                <h2><i class="fa-solid fa-comments"></i> Select a course</h2>
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:12px">
                <?php foreach ($courses as $c): ?>
                    <a class="btn <?= $courseId === (int) $c['id'] ? 'btn--primary' : 'btn--ghost' ?> btn--3d" href="discussions.php?course_id=<?= (int) $c['id'] ?>"><?= e($c['title']) ?></a>
                <?php endforeach; ?>
            </div>
        </section>

        <?php if ($courseId && $role !== 'student'): ?>
        <section class="panel three-d-card hidden" id="new-thread">
            <h2>Start a discussion</h2>
            <form method="post" action="discussions.php?action=create_thread&course_id=<?= (int) $courseId ?>" class="upload-form">
                <?= Security::csrfField() ?>
                <label class="upload-form__wide">Title <input type="text" name="title" placeholder="What would you like to discuss?" required></label>
                <button class="btn btn--primary upload-form__wide" type="submit"><i class="fa-solid fa-comment"></i> Create thread</button>
            </form>
            <style>.hidden{display:none!important}</style>
        </section>
        <?php endif; ?>

        <?php if ($thread): ?>
        <section class="panel three-d-card">
            <div class="panel__head">
                <h2><?= e($thread['title']) ?></h2>
                <a class="btn btn--ghost btn--3d" href="discussions.php?course_id=<?= (int) $thread['course_id'] ?>"><i class="fa-solid fa-arrow-left"></i> Back</a>
            </div>
            <p class="muted">Started by <?= e($thread['author_name']) ?></p>
            <div class="classwork-list">
                <?php foreach ($posts as $p): ?>
                    <div class="stream-card">
                        <header><i class="fa-solid fa-circle-user"></i><strong><?= e($p['author_name']) ?></strong><small><?= e($p['created_at']) ?></small></header>
                        <p><?= nl2br(e($p['body'])) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
            <form method="post" action="discussions.php?action=post_reply&thread_id=<?= (int) $thread['id'] ?>" style="margin-top:16px;display:grid;gap:8px">
                <?= Security::csrfField() ?>
                <textarea name="body" rows="3" placeholder="Write your reply..." required style="padding:12px;border:1px solid var(--line);border-radius:8px;font:inherit"></textarea>
                <button class="btn btn--primary" type="submit"><i class="fa-solid fa-reply"></i> Post reply</button>
            </form>
        </section>
        <?php elseif ($courseId): ?>
        <section class="panel three-d-card">
            <div class="panel__head"><h2><i class="fa-solid fa-list"></i> Discussion threads</h2></div>
            <?php if (empty($threads)): ?>
                <div class="empty-state"><i class="fa-solid fa-comments"></i><h3>No discussions yet</h3><p>Start a conversation about this course.</p></div>
            <?php else: ?>
                <div class="classwork-list">
                    <?php foreach ($threads as $t): ?>
                        <a class="classwork-card" href="discussions.php?thread_id=<?= (int) $t['id'] ?>">
                            <div class="classwork-card__icon"><i class="fa-solid fa-comment-dots"></i></div>
                            <div class="classwork-card__body">
                                <h3><?= e($t['title']) ?></h3>
                                <small><i class="fa-solid fa-user"></i> <?= e($t['author_name']) ?> • <?= (int) $t['post_count'] ?> posts • Last: <?= e($t['last_post_at'] ?? $t['created_at']) ?></small>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
        <?php else: ?>
        <div class="empty-state" style="margin-top:20px">
            <i class="fa-solid fa-comments"></i>
            <h3>Select a course to view discussions</h3>
            <p>Choose a course from above to see its discussion threads.</p>
        </div>
        <?php endif; ?>
    </section>
</main>
<?php require __DIR__ . '/../views/layouts/footer.php'; ?>
