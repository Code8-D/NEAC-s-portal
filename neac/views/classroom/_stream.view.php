<div class="classroom-grid">
    <div class="classroom-stream">
        <?php if ($isTeacher): ?>
            <section class="panel three-d-card">
                <div class="panel__head">
                    <h2><i class="fa-solid fa-bullhorn"></i> Post an announcement</h2>
                </div>
                <form class="upload-form" method="post" action="<?= BASE_URL ?>/classroom_action.php">
                    <?= Security::csrfField() ?>
                    <input type="hidden" name="action" value="post_announcement">
                    <input type="hidden" name="classroom_id" value="<?= (int) $classroom['id'] ?>">
                    <label class="upload-form__wide">
                        Share something with your class
                        <textarea name="body" rows="3" placeholder="What would you like to announce?" required></textarea>
                    </label>
                    <button class="btn btn--primary btn--3d" type="submit"><i class="fa-solid fa-paper-plane"></i> Post</button>
                </form>
            </section>
        <?php endif; ?>

        <?php if (empty($stream)): ?>
            <div class="empty-state">
                <i class="fa-solid fa-comments"></i>
                <h3>No announcements yet</h3>
                <p>Updates from your teacher will appear here.</p>
            </div>
        <?php else: ?>
            <?php foreach ($stream as $item): ?>
                <article class="stream-card three-d-card">
                    <header>
                        <i class="fa-solid fa-circle-user"></i>
                        <strong><?= e($item['author_name']) ?></strong>
                        <small><?= e($item['created_at']) ?></small>
                    </header>
                    <p><?= nl2br(e($item['body'])) ?></p>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <aside class="classroom-aside">
        <section class="panel three-d-card">
            <h3>Class code</h3>
            <p class="classroom-code-big"><?= e($classroom['code']) ?></p>
            <p class="muted">Share this code or a link with students so they can join.</p>
            <a class="btn btn--ghost btn--3d" href="classroom.php?action=join&amp;code=<?= e($classroom['code']) ?>"><i class="fa-solid fa-share"></i> Share join link</a>
        </section>
        <section class="panel three-d-card">
            <h3>Upcoming</h3>
            <?php
            $upcoming = array_filter($classwork, fn($c) => $c['work_type'] === 'assignment' && !empty($c['due_at']));
            ?>
            <?php if (empty($upcoming)): ?>
                <p class="muted">No assignments with due dates yet.</p>
            <?php else: ?>
                <ul class="aside-list">
                    <?php foreach (array_slice($upcoming, 0, 5) as $item): ?>
                        <li>
                            <a href="classroom.php?action=classwork&amp;work_id=<?= (int) $item['id'] ?>"><?= e($item['title']) ?></a>
                            <small>Due <?= e($item['due_at']) ?></small>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
        <?php $courseId = Classroom::courseId((int) $classroom['id']); if ($courseId): ?>
            <section class="panel three-d-card">
                <h3>Quick links</h3>
                <div style="display:grid;gap:8px">
                    <a class="btn btn--ghost btn--3d" href="<?= BASE_URL ?>/quizzes.php?course_id=<?= (int) $courseId ?>&classroom=<?= (int) $classroom['id'] ?>"><i class="fa-solid fa-pen-ruler"></i> Quizzes</a>
                    <a class="btn btn--ghost btn--3d" href="<?= BASE_URL ?>/lessons.php?course_id=<?= (int) $courseId ?>"><i class="fa-solid fa-book-open"></i> Lesson planner</a>
                    <a class="btn btn--ghost btn--3d" href="<?= BASE_URL ?>/discussions.php?course_id=<?= (int) $courseId ?>"><i class="fa-solid fa-comments"></i> Discussions</a>
                </div>
            </section>
        <?php endif; ?>
    </aside>
</div>
