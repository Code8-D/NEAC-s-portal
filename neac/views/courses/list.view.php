<section class="panel" id="courses">
    <div class="panel__head">
        <h2>Course management</h2>
        <button class="icon-btn" type="button" title="Filter courses"><i class="fa-solid fa-filter"></i></button>
    </div>
    <?php if (empty($courses)): ?>
        <div class="empty-state">
            <i class="fa-solid fa-book-open"></i>
            <h3>No courses yet</h3>
            <p>Courses will appear here after the admin creates them.</p>
        </div>
    <?php else: ?>
        <div class="course-grid">
            <?php foreach ($courses as $course): ?>
                <article class="course-card">
                    <div class="course-card__top">
                        <span><?= e($course['code']) ?></span>
                        <em><?= e(ucfirst($course['status'])) ?></em>
                    </div>
                    <h3><?= e($course['title']) ?></h3>
                    <p><i class="fa-solid fa-user-tie"></i> <?= e($course['teacher']) ?></p>
                    <div class="progress"><span style="width: <?= (int) $course['progress'] ?>%"></span></div>
                    <div class="course-meta">
                        <span><i class="fa-solid fa-users"></i> <?= (int) $course['students'] ?></span>
                        <span><i class="fa-solid fa-file-lines"></i> <?= (int) $course['lessons'] ?> lessons</span>
                    </div>
                    <?php if (($role ?? '') === 'admin'): ?>
                        <form method="post" action="<?= BASE_URL ?>/delete_course.php" style="margin-top:12px" onsubmit="return confirm('Delete <?= e(addslashes($course['title'])) ?> (<?= e(addslashes($course['code'])) ?>)? Its classroom, lessons, enrollments, assignments and quizzes will also be removed. This cannot be undone.');">
                            <?= Security::csrfField() ?>
                            <input type="hidden" name="id" value="<?= (int) $course['id'] ?>">
                            <button class="btn btn--ghost btn--3d" style="font-size:12px;min-height:32px;color:#dc2626" type="submit"><i class="fa-solid fa-trash"></i> Delete course</button>
                        </form>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
