<?php
$section = $_GET['section'] ?? 'overview';
require __DIR__ . '/../layouts/header.php';
?>
<main class="app-shell">
    <?php require __DIR__ . '/../layouts/sidebar.php'; ?>
    <section class="workspace">
        <div class="workspace__head">
            <div><span class="eyebrow"><?= e($classroom['program_name']) ?></span><h1><?= e($classroom['title']) ?></h1></div>
            <div class="classroom-banner__actions">
                <a class="btn btn--ghost btn--3d" href="classroom.php?action=view&amp;id=<?= (int) $classroom['id'] ?>"><i class="fa-solid fa-arrow-up-right-from-square"></i> Open classroom</a>
            </div>
        </div>

        <nav class="classroom-tabs" aria-label="Manage sections">
            <a class="<?= $section === 'overview' ? 'active' : '' ?>" href="classroom.php?action=manage&amp;id=<?= (int) $classroom['id'] ?>&amp;section=overview">Overview</a>
            <a class="<?= $section === 'classwork' ? 'active' : '' ?>" href="classroom.php?action=manage&amp;id=<?= (int) $classroom['id'] ?>&amp;section=classwork">Classwork</a>
            <a class="<?= $section === 'people' ? 'active' : '' ?>" href="classroom.php?action=manage&amp;id=<?= (int) $classroom['id'] ?>&amp;section=people">People</a>
        </nav>

        <?php if (!empty($_GET['invited'])): ?>
            <p class="notice notice--success"><i class="fa-solid fa-circle-check"></i> Student added.</p>
        <?php endif; ?>
        <?php if (!empty($_GET['removed'])): ?>
            <p class="notice notice--success"><i class="fa-solid fa-circle-check"></i> Member removed.</p>
        <?php endif; ?>

        <?php if ($section === 'overview'): ?>
            <section class="panel three-d-card">
                <h2>Class details</h2>
                <p><strong>Class code:</strong> <code class="classroom-code-big"><?= e($classroom['code']) ?></code></p>
                <p><strong>Program:</strong> <?= e($classroom['program_name']) ?></p>
                <p><strong>Course code:</strong> <?= e($classroom['course_code']) ?></p>
                <p><strong>Open join:</strong> <?= (int) $classroom['is_open_join'] === 1 ? 'Yes — students can join with the code' : 'No — invite only' ?></p>
                <p class="muted">Share the class code or the join link to invite students.</p>
                <a class="btn btn--ghost btn--3d" href="classroom.php?action=join&amp;code=<?= e($classroom['code']) ?>"><i class="fa-solid fa-share"></i> Open join link</a>
            </section>
        <?php elseif ($section === 'classwork'): ?>
            <section class="panel three-d-card">
                <h2>Add classwork</h2>
                <p class="muted">Create an assignment (with optional rubric), a material, or a question.</p>
                <form class="upload-form" method="post" action="<?= BASE_URL ?>/classroom_action.php">
                    <?= Security::csrfField() ?>
                    <input type="hidden" name="action" value="create_classwork">
                    <input type="hidden" name="classroom_id" value="<?= (int) $classroom['id'] ?>">
                    <label>
                        Type
                        <select name="work_type" id="cw-type">
                            <option value="material">Material</option>
                            <option value="assignment">Assignment</option>
                            <option value="question">Question</option>
                        </select>
                    </label>
                    <label>
                        Title
                        <input type="text" name="title" placeholder="e.g. Lesson 1: Curriculum overview" required>
                    </label>
                    <label class="upload-form__wide">
                        Description
                        <textarea name="description" rows="3" placeholder="Brief description shown in the classwork list"></textarea>
                    </label>
                    <label class="upload-form__wide">
                        Instructions
                        <textarea name="instructions" rows="3" placeholder="Detailed instructions for students"></textarea>
                    </label>
                    <label>
                        Due date
                        <input type="datetime-local" name="due_at">
                    </label>
                    <label>
                        Max score
                        <input type="number" name="max_score" value="100" min="1" step="0.5">
                    </label>
                    <fieldset class="upload-form__wide rubric-block">
                        <legend>Rubric (assignments only)</legend>
                        <label>Rubric title <input type="text" name="rubric_title" placeholder="Grading rubric"></label>
                        <div class="rubric-rows" id="rubric-rows">
                            <div class="rubric-row">
                                <input type="text" name="rubric_label[]" placeholder="Criterion (e.g. Clarity)">
                                <input type="number" name="rubric_max[]" placeholder="Pts" value="10" min="1" step="0.5">
                                <input type="text" name="rubric_desc[]" placeholder="Description (optional)">
                                <button type="button" class="icon-btn" data-remove-rubric><i class="fa-solid fa-trash"></i></button>
                            </div>
                        </div>
                        <button type="button" class="btn btn--ghost btn--3d" id="add-rubric"><i class="fa-solid fa-plus"></i> Add criterion</button>
                    </fieldset>
                    <button class="btn btn--primary btn--3d upload-form__wide" type="submit"><i class="fa-solid fa-paper-plane"></i> Publish classwork</button>
                </form>
            </section>

            <section class="panel three-d-card">
                <h2>Existing classwork</h2>
                <?php if (empty($classwork)): ?>
                    <p class="muted">Nothing posted yet.</p>
                <?php else: ?>
                    <ul class="aside-list">
                        <?php foreach ($classwork as $item): ?>
                            <li>
                                <a href="classroom.php?action=classwork&amp;work_id=<?= (int) $item['id'] ?>"><?= e($item['title']) ?></a>
                                <small><?= e(ucfirst($item['work_type'])) ?> • <?= e($item['created_at']) ?></small>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </section>
        <?php elseif ($section === 'people'): ?>
            <section class="panel three-d-card">
                <h2>Members</h2>
                <div class="people-grid">
                    <?php foreach ($members as $member): ?>
                        <div class="person-card">
                            <i class="fa-solid fa-circle-user"></i>
                            <strong><?= e($member['full_name']) ?></strong>
                            <small><?= e($member['email']) ?> • <?= e(ucfirst(str_replace('_', '-', $member['role']))) ?></small>
                            <?php if ((int) $member['id'] !== (int) Auth::userId()): ?>
                                <form method="post" action="<?= BASE_URL ?>/classroom_action.php" onsubmit="return confirm('Remove this member?');">
                                    <?= Security::csrfField() ?>
                                    <input type="hidden" name="action" value="remove_member">
                                    <input type="hidden" name="classroom_id" value="<?= (int) $classroom['id'] ?>">
                                    <input type="hidden" name="member_id" value="<?= (int) $member['id'] ?>">
                                    <button type="submit" class="btn btn--ghost btn--3d"><i class="fa-solid fa-user-minus"></i> Remove</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
    </section>
</main>
<?php require __DIR__ . '/../layouts/footer.php'; ?>
