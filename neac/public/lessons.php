<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/core/Auth.php';
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/core/Security.php';
require_once __DIR__ . '/../app/models/Lesson.php';
require_once __DIR__ . '/../app/models/Notification.php';

if (!Auth::check()) { header('Location: signup.php'); exit; }
$role = Auth::role();
$userId = (int) Auth::userId();
$teacherId = Auth::teacherId();
$isAdmin = $role === 'admin';
$canPlan = $isAdmin || $role === 'teacher';

$action = $_GET['action'] ?? 'list';
$courseId = (int) ($_GET['course_id'] ?? 0);
$lessonId = (int) ($_GET['id'] ?? 0);
$statusFilter = trim($_GET['status'] ?? '');
$error = '';
$success = $_GET['saved'] ?? '';

Database::migrate();

function lessonUploadPath(?array $file): ?string
{
    if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
    [$ok, $msg] = Security::uploadIsAllowed(
        $file,
        ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'txt', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'zip', 'mp4', 'mov', 'avi', 'mkv'],
        ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument', 'application/vnd.ms-', 'application/zip', 'image/', 'text/', 'video/'],
        80 * 1024 * 1024
    );
    if (!$ok) {
        throw new RuntimeException($msg);
    }
    $uploadRoot = __DIR__ . '/../storage/uploads/lessons';
    if (!is_dir($uploadRoot)) {
        mkdir($uploadRoot, 0775, true);
    }
    $storedName = Security::safeStorageName($file['name'], $ext);
    if (!move_uploaded_file($file['tmp_name'], $uploadRoot . '/' . $storedName)) {
        throw new RuntimeException('Upload could not be saved.');
    }
    $relativePath = 'storage/uploads/lessons/' . $storedName;
    $mime = Security::detectMime($uploadRoot . '/' . $storedName);
    $stmt = Database::connection()->prepare(
        'INSERT INTO files (uploaded_by, original_name, stored_path, mime_type, file_size, upload_context)
         VALUES (:u, :o, :s, :m, :z, "lesson_attachment")'
    );
    $stmt->execute([
        'u' => Auth::userId(),
        'o' => substr($file['name'], 0, 255),
        's' => $relativePath,
        'm' => $mime,
        'z' => (int) $file['size'],
    ]);
    return $relativePath;
}

function lessonPlanFromPost(array $post): array
{
    return [
        'objectives' => trim($post['objectives'] ?? ''),
        'lesson_date' => trim($post['lesson_date'] ?? ''),
        'start_time' => trim($post['start_time'] ?? ''),
        'duration_minutes' => (int) ($post['duration_minutes'] ?? 60),
        'venue' => trim($post['venue'] ?? ''),
        'status' => trim($post['status'] ?? 'planned'),
        'is_published' => isset($post['is_published']) ? 1 : 0,
        'planned_by' => Auth::userId(),
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::requireCsrf();
    if (!$canPlan) {
        http_response_code(403);
        exit('Only lecturers and admins can plan lessons.');
    }
    try {
        if ($action === 'create') {
            $title = trim($_POST['title'] ?? '');
            $type = $_POST['lesson_type'] ?? 'document';
            $content = trim($_POST['content'] ?? '');
            $targetCourse = (int) ($_POST['course_id'] ?? $courseId);
            if ($title === '' || !$targetCourse) {
                throw new RuntimeException('Lesson title and course are required.');
            }
            if (!Lesson::canPlanCourse($role, $teacherId, $targetCourse)) {
                http_response_code(403);
                exit('You can only plan lessons for your own courses.');
            }
            // Attachment: either a newly uploaded file or a previously uploaded teaching material.
            $filePath = trim($_POST['material_path'] ?? '') ?: null;
            $uploaded = isset($_FILES['lesson_file']) ? lessonUploadPath($_FILES['lesson_file']) : null;
            if ($uploaded) {
                $filePath = $uploaded;
            }
            $db = Database::connection();
            $stmt = $db->prepare('SELECT COALESCE(MAX(sort_order),-1)+1 FROM lessons WHERE course_id=:c');
            $stmt->execute(['c' => $targetCourse]);
            $sort = (int) $stmt->fetchColumn();
            $plan = lessonPlanFromPost($_POST);
            // Publishing implies visible to students.
            if (($plan['status'] ?? '') === 'published' || !empty($plan['is_published'])) {
                $plan['status'] = $plan['status'] === '' ? 'published' : $plan['status'];
            }
            Lesson::create($targetCourse, $title, $type, $content ?: null, $filePath, $sort, $plan);
            $success = 'Lesson planned successfully.';
            $classroomId = (int) ($_POST['classroom_id'] ?? 0);
            if ($classroomId > 0) {
                Notification::broadcastToClassroom(
                    $classroomId, 'New lesson planned: ' . $title,
                    'A new lesson "' . $title . '" has been planned.',
                    'lesson'
                );
            }
            header('Location: lessons.php?course_id=' . $targetCourse . '&saved=' . urlencode($success));
            exit;
        }

        if ($action === 'update') {
            $lesson = Lesson::find($lessonId);
            if (!$lesson) {
                throw new RuntimeException('Lesson not found.');
            }
            if (!Lesson::canPlanCourse($role, $teacherId, (int) $lesson['course_id'])) {
                http_response_code(403);
                exit('You can only edit lessons for your own courses.');
            }
            $title = trim($_POST['title'] ?? '');
            if ($title === '') {
                throw new RuntimeException('Lesson title is required.');
            }
            $type = $_POST['lesson_type'] ?? $lesson['lesson_type'];
            $content = trim($_POST['content'] ?? '');
            $filePath = trim($_POST['material_path'] ?? '') ?: ($lesson['file_path'] ?? null);
            if (!empty($_POST['remove_file'])) {
                $filePath = null;
            }
            $uploaded = isset($_FILES['lesson_file']) ? lessonUploadPath($_FILES['lesson_file']) : null;
            if ($uploaded) {
                $filePath = $uploaded;
            }
            $plan = lessonPlanFromPost($_POST);
            // Preserve publish checkbox state when editing (unchecked means unpublish unless status says published).
            if (!isset($_POST['is_published']) && ($plan['status'] ?? '') !== 'published') {
                $plan['is_published'] = 0;
            }
            Lesson::update($lessonId, $title, $type, $content ?: null, $filePath, $plan);
            header('Location: lessons.php?course_id=' . (int) $lesson['course_id'] . '&saved=' . urlencode('Lesson plan updated.'));
            exit;
        }

        if ($action === 'delete') {
            $lesson = Lesson::find($lessonId);
            if (!$lesson) {
                throw new RuntimeException('Lesson not found.');
            }
            if (!Lesson::canPlanCourse($role, $teacherId, (int) $lesson['course_id'])) {
                http_response_code(403);
                exit('You can only delete lessons for your own courses.');
            }
            Lesson::delete($lessonId);
            header('Location: lessons.php?course_id=' . (int) $lesson['course_id'] . '&saved=' . urlencode('Lesson deleted.'));
            exit;
        }

        if ($action === 'status') {
            $lesson = Lesson::find($lessonId);
            if (!$lesson) {
                throw new RuntimeException('Lesson not found.');
            }
            if (!Lesson::canPlanCourse($role, $teacherId, (int) $lesson['course_id'])) {
                http_response_code(403);
                exit('Not allowed.');
            }
            $newStatus = trim($_POST['status'] ?? '');
            if (!in_array($newStatus, Lesson::STATUSES, true)) {
                throw new RuntimeException('Invalid status.');
            }
            Lesson::setStatus($lessonId, $newStatus);
            header('Location: lessons.php?course_id=' . (int) $lesson['course_id'] . '&saved=' . urlencode('Lesson marked as ' . $newStatus . '.'));
            exit;
        }

        if ($action === 'move') {
            $lesson = Lesson::find($lessonId);
            if (!$lesson) {
                throw new RuntimeException('Lesson not found.');
            }
            if (!Lesson::canPlanCourse($role, $teacherId, (int) $lesson['course_id'])) {
                http_response_code(403);
                exit('Not allowed.');
            }
            $dir = ($_POST['direction'] ?? 'up') === 'down' ? 1 : -1;
            Lesson::move($lessonId, $dir);
            header('Location: lessons.php?course_id=' . (int) $lesson['course_id']);
            exit;
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$courses = Lesson::plannableCourses($role, $teacherId);
// Students don't have plannable courses — show all courses read-only for selection.
if ($role === 'student') {
    $courses = Database::connection()->query(
        'SELECT c.*, users.full_name AS teacher FROM courses c
         LEFT JOIN teachers t ON t.id = c.teacher_id
         LEFT JOIN users ON users.id = t.user_id
         ORDER BY c.created_at DESC'
    )->fetchAll();
}
if ($courseId && empty(array_filter($courses, fn($c) => (int) $c['id'] === $courseId))) {
    // Unknown / unauthorized course — reset selection.
    if ($role !== 'student') {
        $courseId = 0;
    }
}
$allLessons = $courseId ? Lesson::forCourse($courseId) : [];
// Students only see published/completed material.
if ($role === 'student') {
    $allLessons = array_values(array_filter($allLessons, fn($l) => (int) ($l['is_published'] ?? 0) === 1 || ($l['status'] ?? '') === 'completed'));
}
if ($statusFilter !== '' && in_array($statusFilter, Lesson::STATUSES, true)) {
    $allLessons = array_values(array_filter($allLessons, fn($l) => ($l['status'] ?? 'planned') === $statusFilter));
}
$editing = null;
if ($canPlan && ($action === 'edit') && $lessonId) {
    $found = Lesson::find($lessonId);
    if ($found && Lesson::canPlanCourse($role, $teacherId, (int) $found['course_id'])) {
        $editing = $found;
        $courseId = (int) $found['course_id'];
        $allLessons = Lesson::forCourse($courseId);
    }
}
$materials = $canPlan ? Lesson::uploadedMaterials($role, $userId) : [];
$upcoming = $canPlan ? Lesson::upcomingForTeacher($teacherId, $isAdmin, 8) : [];
$activeCourse = null;
foreach ($courses as $c) {
    if ((int) $c['id'] === $courseId) {
        $activeCourse = $c;
        break;
    }
}
$classroomIdForCourse = 0;
if ($courseId) {
    try {
        $stmt = Database::connection()->prepare('SELECT id FROM classrooms WHERE course_id = :c LIMIT 1');
        $stmt->execute(['c' => $courseId]);
        $classroomIdForCourse = (int) ($stmt->fetchColumn() ?: 0);
    } catch (Throwable $e) {}
}

$statusMeta = [
    'planned' => ['Planned', '#64748b'],
    'published' => ['Published', '#2563eb'],
    'in_progress' => ['In progress', '#d97706'],
    'completed' => ['Completed', '#059669'],
    'cancelled' => ['Cancelled', '#dc2626'],
];

$pageTitle = 'Lesson Planner';
$hideTopbar = true;
require __DIR__ . '/../views/layouts/header.php';
?>

<main class="app-shell">
    <?php require __DIR__ . '/../views/layouts/sidebar.php'; ?>
    <section class="workspace">
        <div class="workspace__head">
            <div><span class="eyebrow">Curriculum • <?= $isAdmin ? 'Admin' : e(ucfirst($role)) ?> planning</span><h1>Lesson planner</h1>
            <p class="muted" style="margin:4px 0 0">Plan lessons for any course uploaded by a lecturer. Both admins and lecturers can create, schedule, edit and track lessons.</p></div>
            <div class="workspace__head-actions">
                <?php if ($canPlan && $courseId): ?>
                    <button class="btn btn--primary btn--3d" onclick="document.getElementById('plan-lesson').classList.toggle('hidden')"><i class="fa-solid fa-plus"></i> Plan lesson</button>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($error): ?><p class="notice notice--error"><i class="fa-solid fa-circle-exclamation"></i> <?= e($error) ?></p><?php endif; ?>
        <?php if ($success): ?><p class="notice notice--success"><i class="fa-solid fa-circle-check"></i> <?= e($success) ?></p><?php endif; ?>

        <?php if ($canPlan && !empty($upcoming)): ?>
        <section class="panel three-d-card">
            <div class="panel__head"><h2><i class="fa-solid fa-calendar-days"></i> Upcoming scheduled lessons</h2></div>
            <div class="table-wrap"><table>
                <thead><tr><th>Date</th><th>Time</th><th>Lesson</th><th>Course</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach ($upcoming as $u): ?>
                    <tr>
                        <td><strong><?= e($u['lesson_date'] ?? '—') ?></strong></td>
                        <td><?= e($u['start_time'] ?? '—') ?></td>
                        <td><a href="lessons.php?course_id=<?= (int) $u['course_id'] ?>"><?= e($u['title']) ?></a></td>
                        <td><?= e($u['course_code'] ?? '') ?></td>
                        <td><span class="status" style="background:#f1f5f9"><?= e(ucfirst(str_replace('_', ' ', $u['status'] ?? 'planned'))) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table></div>
        </section>
        <?php endif; ?>

        <section class="panel three-d-card">
            <div class="panel__head"><h2><i class="fa-solid fa-book"></i> Select a course</h2>
            <?php if ($activeCourse): ?><span class="muted"><?= e($activeCourse['code'] ?? '') ?> • <?= e($activeCourse['teacher'] ?? 'Unassigned') ?></span><?php endif; ?></div>
            <?php if (empty($courses)): ?>
                <div class="empty-state"><i class="fa-solid fa-book-open"></i><h3>No courses available</h3><p><?= $role === 'teacher' ? 'The admin will assign you to a course before you can plan lessons.' : 'Create a course first.' ?></p></div>
            <?php else: ?>
            <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:12px">
                <?php foreach ($courses as $c): ?>
                    <a class="btn <?= $courseId === (int) $c['id'] ? 'btn--primary' : 'btn--ghost' ?> btn--3d" href="lessons.php?course_id=<?= (int) $c['id'] ?>"><?= e($c['title']) ?></a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </section>

        <?php if ($courseId && $canPlan): ?>
        <section class="panel three-d-card <?= $editing ? '' : 'hidden' ?>" id="plan-lesson">
            <div class="panel__head"><h2><i class="fa-solid fa-calendar-plus"></i> <?= $editing ? 'Edit lesson plan #' . (int) $editing['id'] : 'Plan a new lesson' ?></h2>
            <?php if ($editing): ?><a class="btn btn--ghost btn--3d" href="lessons.php?course_id=<?= (int) $courseId ?>">Cancel edit</a><?php endif; ?></div>
            <form method="post" action="lessons.php?action=<?= $editing ? 'update&id=' . (int) $editing['id'] : 'create' ?>&course_id=<?= (int) $courseId ?>" class="upload-form" enctype="multipart/form-data">
                <?= Security::csrfField() ?>
                <input type="hidden" name="course_id" value="<?= (int) $courseId ?>">
                <input type="hidden" name="classroom_id" value="<?= (int) $classroomIdForCourse ?>">
                <label>Lesson title <input type="text" name="title" value="<?= e($editing['title'] ?? '') ?>" placeholder="e.g. Week 3 — Officer ethics" required></label>
                <label>Type
                    <select name="lesson_type">
                        <?php foreach (['document' => 'Document / Notes', 'video' => 'Video lesson', 'live' => 'Live session', 'text' => 'Text content'] as $val => $label): ?>
                            <option value="<?= $val ?>" <?= ($editing['lesson_type'] ?? 'document') === $val ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Scheduled date <input type="date" name="lesson_date" value="<?= e($editing['lesson_date'] ?? '') ?>"></label>
                <label>Start time <input type="time" name="start_time" value="<?= e($editing['start_time'] ?? '') ?>"></label>
                <label>Duration (minutes) <input type="number" name="duration_minutes" min="15" max="480" step="15" value="<?= e((string) ($editing['duration_minutes'] ?? 60)) ?>"></label>
                <label>Venue / link <input type="text" name="venue" value="<?= e($editing['venue'] ?? '') ?>" placeholder="e.g. Room A2 or Meet link"></label>
                <label>Status
                    <select name="status">
                        <?php foreach (Lesson::STATUSES as $st): ?>
                            <option value="<?= $st ?>" <?= ($editing['status'] ?? 'planned') === $st ? 'selected' : '' ?>><?= e(ucfirst(str_replace('_', ' ', $st))) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label style="display:flex;gap:8px;align-items:center;font-weight:600">
                    <input type="checkbox" name="is_published" value="1" <?= ((int) ($editing['is_published'] ?? 1) === 1) ? 'checked' : '' ?> style="width:auto;height:auto"> Visible to students
                </label>
                <label class="upload-form__wide">Learning objectives <textarea name="objectives" rows="3" placeholder="What should students know or be able to do after this lesson?"><?= e($editing['objectives'] ?? '') ?></textarea></label>
                <label class="upload-form__wide">Lesson content / notes <textarea name="content" rows="6" placeholder="Lesson content, notes, or embed code"><?= e($editing['content'] ?? '') ?></textarea></label>
                <label class="upload-form__wide">Reuse uploaded lecturer material
                    <select name="material_path">
                        <option value="">— None —</option>
                        <?php foreach ($materials as $m): ?>
                            <option value="<?= e($m['stored_path']) ?>" <?= ($editing['file_path'] ?? '') === $m['stored_path'] ? 'selected' : '' ?>>
                                <?= e($m['original_name']) ?> (<?= e($m['uploader'] ?? '') ?> • <?= e(substr($m['created_at'] ?? '', 0, 10)) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="file-drop upload-form__wide">
                    <i class="fa-solid fa-file-arrow-up"></i>
                    <span>Or attach a new lesson file</span>
                    <small>PDF, Word, PowerPoint, Excel, images, ZIP, video up to 80MB.</small>
                    <input type="file" name="lesson_file">
                </label>
                <?php if (!empty($editing['file_path'])): ?>
                    <p class="muted upload-form__wide">Current file: <code><?= e($editing['file_path']) ?></code>
                    <label style="display:inline-flex;gap:6px;align-items:center;margin-left:10px"><input type="checkbox" name="remove_file" value="1" style="width:auto;height:auto"> Remove file</label></p>
                <?php endif; ?>
                <button class="btn btn--primary upload-form__wide" type="submit"><i class="fa-solid fa-save"></i> <?= $editing ? 'Save changes' : 'Save lesson plan' ?></button>
            </form>
            <style>.hidden{display:none!important}</style>
        </section>
        <?php endif; ?>

        <?php if ($courseId): ?>
        <section class="panel three-d-card">
            <div class="panel__head"><h2><i class="fa-solid fa-list"></i> Lesson plans <?= $activeCourse ? '— ' . e($activeCourse['title']) : '' ?></h2>
                <form method="get" action="lessons.php" style="display:flex;gap:8px;align-items:center">
                    <input type="hidden" name="course_id" value="<?= (int) $courseId ?>">
                    <select name="status" onchange="this.form.submit()">
                        <option value="">All statuses</option>
                        <?php foreach (Lesson::STATUSES as $st): ?>
                            <option value="<?= $st ?>" <?= $statusFilter === $st ? 'selected' : '' ?>><?= e(ucfirst(str_replace('_', ' ', $st))) ?></option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
            <?php if (empty($allLessons)): ?>
                <div class="empty-state"><i class="fa-solid fa-book-open"></i><h3>No lessons yet</h3><p><?= $canPlan ? 'Use “Plan lesson” to schedule the first lesson for this course — you can reuse any material uploaded by a lecturer.' : 'Lessons for this course will appear here once planned.' ?></p></div>
            <?php else: ?>
                <div class="classwork-list">
                    <?php foreach ($allLessons as $l):
                        $meta = $statusMeta[$l['status'] ?? 'planned'] ?? $statusMeta['planned'];
                    ?>
                        <div class="classwork-card">
                            <div class="classwork-card__icon classwork-card__icon--material"><i class="fa-solid <?= $l['lesson_type'] === 'video' ? 'fa-video' : ($l['lesson_type'] === 'live' ? 'fa-chalkboard-user' : 'fa-file-lines') ?>"></i></div>
                            <div class="classwork-card__body">
                                <h3><?= e($l['title']) ?></h3>
                                <p>
                                    <span class="badge badge--material"><?= e(ucfirst($l['lesson_type'])) ?></span>
                                    <span class="status" style="background:#f1f5f9;color:<?= e($meta[1]) ?>;border:1px solid <?= e($meta[1]) ?>"><?= e($meta[0]) ?></span>
                                    <?php if (!empty($l['lesson_date'])): ?> • <i class="fa-solid fa-calendar-day"></i> <?= e($l['lesson_date']) ?><?= !empty($l['start_time']) ? ' ' . e($l['start_time']) : '' ?><?php endif; ?>
                                    <?php if (!empty($l['duration_minutes'])): ?> • <?= (int) $l['duration_minutes'] ?> min<?php endif; ?>
                                    <?php if (!empty($l['venue'])): ?> • <i class="fa-solid fa-location-dot"></i> <?= e($l['venue']) ?><?php endif; ?>
                                </p>
                                <?php if (!empty($l['objectives'])): ?><p><strong>Objectives:</strong> <?= e(mb_substr($l['objectives'], 0, 300)) ?></p><?php endif; ?>
                                <?php if (!empty($l['content'])): ?><p><?= e(mb_substr($l['content'], 0, 220)) ?></p><?php endif; ?>
                                <?php if (!empty($l['file_path'])): ?><p><a class="btn btn--ghost btn--3d" style="font-size:12px;min-height:30px;padding:0 10px" href="<?= BASE_URL ?>/<?= e($l['file_path']) ?>" target="_blank" rel="noopener"><i class="fa-solid fa-paperclip"></i> Open attached material</a></p><?php endif; ?>
                                <?php if (!empty($l['planner_name'])): ?><small class="muted">Planned by <?= e($l['planner_name']) ?></small><?php endif; ?>
                                <?php if ($canPlan): ?>
                                <div style="display:flex;gap:6px;flex-wrap:wrap;margin-top:10px">
                                    <a class="btn btn--ghost btn--3d" style="font-size:12px;min-height:30px;padding:0 10px" href="lessons.php?action=edit&id=<?= (int) $l['id'] ?>&course_id=<?= (int) $courseId ?>"><i class="fa-solid fa-pen"></i> Edit</a>
                                    <form method="post" action="lessons.php?action=move&id=<?= (int) $l['id'] ?>&course_id=<?= (int) $courseId ?>" style="display:inline">
                                        <?= Security::csrfField() ?>
                                        <input type="hidden" name="direction" value="up">
                                        <button class="btn btn--ghost btn--3d" style="font-size:12px;min-height:30px;padding:0 10px" title="Move up"><i class="fa-solid fa-arrow-up"></i></button>
                                    </form>
                                    <form method="post" action="lessons.php?action=move&id=<?= (int) $l['id'] ?>&course_id=<?= (int) $courseId ?>" style="display:inline">
                                        <?= Security::csrfField() ?>
                                        <input type="hidden" name="direction" value="down">
                                        <button class="btn btn--ghost btn--3d" style="font-size:12px;min-height:30px;padding:0 10px" title="Move down"><i class="fa-solid fa-arrow-down"></i></button>
                                    </form>
                                    <form method="post" action="lessons.php?action=status&id=<?= (int) $l['id'] ?>&course_id=<?= (int) $courseId ?>" style="display:inline">
                                        <?= Security::csrfField() ?>
                                        <select name="status" onchange="this.form.submit()" style="font-size:12px;min-height:30px">
                                            <?php foreach (Lesson::STATUSES as $st): ?>
                                                <option value="<?= $st ?>" <?= ($l['status'] ?? 'planned') === $st ? 'selected' : '' ?>><?= e(ucfirst(str_replace('_', ' ', $st))) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </form>
                                    <form method="post" action="lessons.php?action=delete&id=<?= (int) $l['id'] ?>&course_id=<?= (int) $courseId ?>" style="display:inline" onsubmit="return confirm('Delete this lesson plan?')">
                                        <?= Security::csrfField() ?>
                                        <button class="btn btn--ghost btn--3d" style="font-size:12px;min-height:30px;padding:0 10px;color:#dc2626"><i class="fa-solid fa-trash"></i></button>
                                    </form>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
        <?php else: ?>
        <div class="empty-state" style="margin-top:20px">
            <i class="fa-solid fa-book-open"></i>
            <h3>Select a course to plan lessons</h3>
            <p>Choose a course above to view and manage its lesson plans, schedule dates, and reuse uploaded lecturer materials.</p>
        </div>
        <?php endif; ?>
    </section>
</main>
<?php require __DIR__ . '/../views/layouts/footer.php'; ?>
