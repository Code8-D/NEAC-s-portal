<?php require __DIR__ . '/../layouts/header.php'; ?>
<main class="app-shell classroom-shell" style="--banner: <?= e($classroom['banner_color'] ?? '#102b4e') ?>;">
    <div class="classroom-banner" data-banner="<?= e($classroom['banner_color'] ?? '#102b4e') ?>">
        <div class="classroom-banner__bg"></div>
        <div class="classroom-banner__content">
            <span class="eyebrow"><i class="fa-solid fa-school"></i> <?= e($classroom['program_name'] ?? '') ?> • <?= e($classroom['course_code'] ?? '') ?></span>
            <h1><?= e($classroom['title']) ?></h1>
            <div class="classroom-banner__meta">
                <span class="classroom-code"><i class="fa-solid fa-key"></i> <?= e($classroom['code']) ?></span>
                <span><i class="fa-solid fa-user-tie"></i> <?= e($classroom['teacher_name'] ?? 'Unassigned') ?></span>
                <span><i class="fa-solid fa-users"></i> <?= (int) $memberCount ?> members</span>
            </div>
        </div>
        <div class="classroom-banner__actions">
            <?php if ($isTeacher): ?>
                <a class="btn btn--ghost btn--light btn--3d" href="classroom.php?action=manage&amp;id=<?= (int) $classroom['id'] ?>"><i class="fa-solid fa-gear"></i> Manage</a>
            <?php endif; ?>
            <a class="btn btn--ghost btn--light btn--3d" href="dashboard.php?section=classrooms"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </div>
    </div>

    <nav class="classroom-tabs" aria-label="Classroom sections">
        <a class="<?= $section === 'stream' ? 'active' : '' ?>" href="classroom.php?action=view&amp;id=<?= (int) $classroom['id'] ?>&amp;section=stream"><i class="fa-solid fa-bullhorn"></i> Stream</a>
        <a class="<?= $section === 'classwork' ? 'active' : '' ?>" href="classroom.php?action=view&amp;id=<?= (int) $classroom['id'] ?>&amp;section=classwork"><i class="fa-solid fa-folder-tree"></i> Classwork</a>
        <a class="<?= $section === 'people' ? 'active' : '' ?>" href="classroom.php?action=view&amp;id=<?= (int) $classroom['id'] ?>&amp;section=people"><i class="fa-solid fa-people-group"></i> People</a>
        <?php if ($isTeacher): ?>
            <a class="<?= $section === 'private_comments' ? 'active' : '' ?>" href="classroom.php?action=view&amp;id=<?= (int) $classroom['id'] ?>&amp;section=private_comments"><i class="fa-solid fa-lock"></i> Private</a>
            <a class="<?= $section === 'attendance' ? 'active' : '' ?>" href="classroom.php?action=view&amp;id=<?= (int) $classroom['id'] ?>&amp;section=attendance"><i class="fa-solid fa-calendar-check"></i> Attendance</a>
        <?php endif; ?>
        <a class="<?= $section === 'discussions' ? 'active' : '' ?>" href="classroom.php?action=view&amp;id=<?= (int) $classroom['id'] ?>&amp;section=discussions"><i class="fa-solid fa-comments"></i> Discuss</a>
    </nav>

    <section class="workspace classroom-workspace">
        <?php if (!empty($_GET['posted'])): ?>
            <p class="notice notice--success"><i class="fa-solid fa-circle-check"></i> Announcement posted.</p>
        <?php endif; ?>
        <?php if (!empty($_GET['submitted'])): ?>
            <p class="notice notice--success"><i class="fa-solid fa-circle-check"></i> Assignment submitted.</p>
        <?php endif; ?>
        <?php if (!empty($_GET['attendance_saved'])): ?>
            <p class="notice notice--success"><i class="fa-solid fa-circle-check"></i> Attendance recorded.</p>
        <?php endif; ?>

        <?php if ($section === 'stream'): ?>
            <?php require __DIR__ . '/_stream.view.php'; ?>
        <?php elseif ($section === 'classwork'): ?>
            <?php require __DIR__ . '/_classwork_list.view.php'; ?>
        <?php elseif ($section === 'people'): ?>
            <?php require __DIR__ . '/_people.view.php'; ?>
        <?php elseif ($section === 'private_comments' && $isTeacher): ?>
            <section class="panel three-d-card">
                <h2><i class="fa-solid fa-lock"></i> Private comments</h2>
                <p class="muted">Leave private feedback visible only to the individual student.</p>
                <div class="classwork-list">
                    <?php
                    $members = Classroom::members((int) $classroom['id']);
                    $students = array_filter($members, fn($m) => $m['role'] === 'student');
                    foreach ($students as $s):
                        $sc = Database::connection()->prepare('SELECT id FROM students WHERE user_id = :u');
                        $sc->execute(['u' => $s['id']]);
                        $sid = $sc->fetchColumn();
                    ?>
                        <details style="padding:12px;border:1px solid var(--line);border-radius:8px">
                            <summary style="cursor:pointer;font-weight:700"><?= e($s['full_name']) ?></summary>
                            <div style="margin-top:8px">
                                <?php if ($sid):
                                    $pcs = Database::connection()->prepare(
                                        'SELECT pc.*, users.full_name AS author_name FROM classroom_private_comments pc
                                         INNER JOIN users ON users.id = pc.teacher_id
                                         WHERE pc.classroom_id = :c AND pc.student_id = :s ORDER BY pc.created_at DESC'
                                    );
                                    $pcs->execute(['c' => $classroom['id'], 's' => $sid]);
                                    $comments = $pcs->fetchAll();
                                    foreach ($comments as $pc): ?>
                                        <div class="stream-card" style="margin-bottom:8px">
                                            <p><?= nl2br(e($pc['body'])) ?></p>
                                            <small class="muted"><?= e($pc['created_at']) ?></small>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                <form method="post" action="<?= BASE_URL ?>/classroom_action.php" style="display:grid;gap:8px;margin-top:8px">
                                    <?= Security::csrfField() ?>
                                    <input type="hidden" name="action" value="private_comment">
                                    <input type="hidden" name="classroom_id" value="<?= (int) $classroom['id'] ?>">
                                    <input type="hidden" name="student_id" value="<?= (int) ($sid ?? 0) ?>">
                                    <textarea name="body" rows="2" placeholder="Write a private comment..." style="padding:8px;border:1px solid var(--line);border-radius:6px;font:inherit"></textarea>
                                    <button class="btn btn--ghost btn--3d" type="submit"><i class="fa-solid fa-lock"></i> Send private</button>
                                </form>
                            </div>
                        </details>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php elseif ($section === 'attendance' && $isTeacher): ?>
            <section class="panel three-d-card">
                <h2><i class="fa-solid fa-calendar-check"></i> Attendance</h2>
                <p class="muted">Record attendance for today's class.</p>
                <?php
                $today = date('Y-m-d');
                $courseId = Classroom::courseId((int) $classroom['id']);
                ?>
                <form method="post" action="<?= BASE_URL ?>/classroom_action.php" class="upload-form">
                    <?= Security::csrfField() ?>
                    <input type="hidden" name="action" value="record_attendance">
                    <input type="hidden" name="classroom_id" value="<?= (int) $classroom['id'] ?>">
                    <input type="hidden" name="course_id" value="<?= (int) ($courseId ?? 0) ?>">
                    <div class="upload-form__wide">
                        <?php foreach ($members as $m): ?>
                            <?php if ($m['role'] !== 'student') continue; ?>
                            <?php
                            $sc = Database::connection()->prepare('SELECT id FROM students WHERE user_id = :u');
                            $sc->execute(['u' => $m['id']]);
                            $sid = $sc->fetchColumn();
                            if (!$sid) continue;
                            $already = Database::connection()->prepare(
                                "SELECT 1 FROM attendance WHERE student_id = :s AND attendance_date = :d AND course_id = :c"
                            );
                            $already->execute(['s' => $sid, 'd' => $today, 'c' => $courseId]);
                            $recorded = $already->fetchColumn();
                            ?>
                            <div style="display:flex;align-items:center;gap:12px;padding:6px 0;border-bottom:1px solid var(--line)">
                                <span style="flex:1;font-weight:600"><?= e($m['full_name']) ?></span>
                                <?php if ($recorded): ?>
                                    <span class="status" style="background:rgba(16,43,78,.08)">Marked today</span>
                                <?php else: ?>
                                    <input type="hidden" name="student_ids[]" value="<?= (int) $sid ?>">
                                    <select name="status_<?= (int) $sid ?>" style="width:auto;padding:4px 8px;height:auto">
                                        <option value="present">Present</option>
                                        <option value="absent">Absent</option>
                                        <option value="late">Late</option>
                                        <option value="excused">Excused</option>
                                    </select>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button class="btn btn--primary upload-form__wide" type="submit"><i class="fa-solid fa-save"></i> Save attendance</button>
                </form>
            </section>
        <?php elseif ($section === 'discussions'): ?>
            <section class="panel three-d-card">
                <div class="panel__head">
                    <h2><i class="fa-solid fa-comments"></i> Discussions</h2>
                    <a class="btn btn--ghost btn--3d" href="<?= BASE_URL ?>/discussions.php?course_id=<?= (int) ($courseId ?? 0) ?>"><i class="fa-solid fa-arrow-right"></i> Open forum</a>
                </div>
                <?php
                $courseId = Classroom::courseId((int) $classroom['id']);
                if ($courseId):
                    require_once __DIR__ . '/../../app/models/Discussion.php';
                    $threads = Discussion::threads($courseId);
                ?>
                    <?php if (empty($threads)): ?>
                        <div class="empty-state"><i class="fa-solid fa-comments"></i><h3>No discussions yet</h3><p>Start a discussion related to this course.</p></div>
                    <?php else: ?>
                        <div class="classwork-list">
                            <?php foreach ($threads as $t): ?>
                                <a class="classwork-card" href="<?= BASE_URL ?>/discussions.php?thread_id=<?= (int) $t['id'] ?>">
                                    <div class="classwork-card__icon"><i class="fa-solid fa-comment-dots"></i></div>
                                    <div class="classwork-card__body">
                                        <h3><?= e($t['title']) ?></h3>
                                        <small><?= (int) $t['post_count'] ?> posts • by <?= e($t['author_name']) ?></small>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </section>
</main>
<?php require __DIR__ . '/../layouts/footer.php'; ?>
