<?php
$courseStatus = $_GET['course'] ?? '';
$courseMessages = [
    'created' => 'Course created successfully.',
    'deleted' => 'Course deleted successfully.',
    'exists' => 'A course with this code already exists.',
    'invalid' => 'Course title and code are required.',
    'not_found' => 'Course not found. It may have been deleted already.',
    'error' => 'Course operation could not be completed. Please try again.',
];
?>

<?php if (isset($courseMessages[$courseStatus])): ?>
    <p class="notice <?= in_array($courseStatus, ['created', 'deleted'], true) ? 'notice--success' : 'notice--error' ?>">
        <i class="fa-solid <?= in_array($courseStatus, ['created', 'deleted'], true) ? 'fa-circle-check' : 'fa-circle-exclamation' ?>"></i>
        <?= e($courseMessages[$courseStatus]) ?>
    </p>
<?php endif; ?>

<section class="panel upload-panel" id="add-course">
    <div class="panel__head">
        <div>
            <h2>Add course</h2>
            <p>Create courses only when NEAC is ready to publish or prepare learning materials.</p>
        </div>
        <i class="fa-solid fa-book-medical"></i>
    </div>
    <form class="upload-form" method="post" action="<?= BASE_URL ?>/create_course.php">
        <?= Security::csrfField() ?>
        <label>
            Course title
            <input type="text" name="title" placeholder="e.g. Officer Certification Foundations" required>
        </label>
        <label>
            Course code
            <input type="text" name="code" placeholder="e.g. OCP-101" required>
        </label>
        <label>
            Program
            <select name="program_id" required>
                <?php foreach ($programs as $program): ?>
                    <option value="<?= (int) $program['id'] ?>"><?= e($program['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            Lecturer
            <select name="teacher_id">
                <option value="">Unassigned</option>
                <?php foreach ($lecturers as $lecturer): ?>
                    <option value="<?= (int) $lecturer['teacher_id'] ?>"><?= e($lecturer['full_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            Status
            <select name="status">
                <option value="draft">Draft</option>
                <option value="published">Published</option>
            </select>
        </label>
        <label class="upload-form__wide">
            Description
            <textarea name="description" placeholder="Brief course description"></textarea>
        </label>
        <button class="btn btn--primary upload-form__wide" type="submit"><i class="fa-solid fa-plus"></i> Create course</button>
    </form>
</section>
