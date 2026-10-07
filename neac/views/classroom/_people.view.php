<div class="people-layout">
    <div class="people-main">
        <h2><i class="fa-solid fa-people-group"></i> People</h2>
        <p class="muted">Teachers and students who are part of this classroom.</p>

        <?php if ($isTeacher): ?>
            <section class="panel three-d-card">
                <h3>Invite a student</h3>
                <form class="upload-form" method="post" action="<?= BASE_URL ?>/classroom_action.php">
                    <?= Security::csrfField() ?>
                    <input type="hidden" name="action" value="invite_student">
                    <input type="hidden" name="classroom_id" value="<?= (int) $classroom['id'] ?>">
                    <label class="upload-form__wide">
                        Student email
                        <input type="email" name="email" placeholder="student@example.com" required>
                    </label>
                    <button class="btn btn--primary btn--3d" type="submit"><i class="fa-solid fa-user-plus"></i> Add to classroom</button>
                </form>
            </section>
        <?php endif; ?>

        <section class="panel three-d-card">
            <h3>Members</h3>
            <div class="people-grid">
                <?php foreach ($members as $member): ?>
                    <div class="person-card">
                        <i class="fa-solid fa-circle-user"></i>
                        <strong><?= e($member['full_name']) ?></strong>
                        <small>
                            <?= e($member['email']) ?><br>
                            <?= e(ucfirst(str_replace('_', '-', $member['role']))) ?>
                            <?php if (!empty($member['student_number'])): ?> • <?= e($member['student_number']) ?><?php endif; ?>
                        </small>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
</div>
