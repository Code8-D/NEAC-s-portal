<?php
$teacherStatus = $_GET['teacher'] ?? '';
$teacherMessages = [
    'created' => 'Lecturer account created successfully.',
    'exists' => 'A lecturer with this email already exists.',
    'invalid' => 'Password must be at least 8 characters with uppercase, lowercase and a number, and match the confirmation.',
    'error' => 'Lecturer account could not be created. Please try again.',
];
?>

<?php if (isset($teacherMessages[$teacherStatus])): ?>
    <p class="notice <?= $teacherStatus === 'created' ? 'notice--success' : 'notice--error' ?>">
        <i class="fa-solid <?= $teacherStatus === 'created' ? 'fa-circle-check' : 'fa-circle-exclamation' ?>"></i>
        <?= e($teacherMessages[$teacherStatus]) ?>
    </p>
<?php endif; ?>

<section class="panel lecturer-panel">
    <div class="panel__head">
        <div>
            <h2>Add lecturer</h2>
            <p>Create a teacher account and give the lecturer the email and password to sign in.</p>
        </div>
        <i class="fa-solid fa-person-chalkboard"></i>
    </div>
    <form class="upload-form lecturer-form" method="post" action="<?= BASE_URL ?>/create_lecturer.php">
        <?= Security::csrfField() ?>
        <label>
            Full name
            <input type="text" name="full_name" placeholder="Lecturer full name" required>
        </label>
        <label>
            Email address
            <input type="email" name="email" placeholder="lecturer@neac.org" required>
        </label>
        <label>
            Title / Rank
            <input type="text" name="title" placeholder="e.g. Instructor, Captain, Major">
        </label>
        <label>
            Phone number
            <input type="tel" name="phone" placeholder="+237 000 000 000">
        </label>
        <label>
            Speciality
            <input type="text" name="speciality" placeholder="e.g. Curriculum, Examination, Training">
        </label>
        <label>
            Assign courses
            <?php if (empty($courses)): ?>
                <p class="muted" style="margin:4px 0 0">No courses exist yet — create a course first, then edit the lecturer assignment from the Courses section.</p>
            <?php else: ?>
                <select name="course_ids[]" multiple size="4">
                    <?php foreach ($courses as $course): ?>
                        <option value="<?= (int) $course['id'] ?>"><?= e($course['title']) ?> (<?= e($course['code']) ?>)</option>
                    <?php endforeach; ?>
                </select>
                <small>Leave empty for no assignment. Hold Ctrl (Windows) or Cmd (Mac) to select multiple courses.</small>
            <?php endif; ?>
        </label>
        <label>
            Password
            <input type="password" name="password" placeholder="Min 8 chars, uppercase + lowercase + number" minlength="8" required>
        </label>
        <label>
            Confirm password
            <input type="password" name="confirm_password" placeholder="Repeat password" minlength="8" required>
        </label>
        <label class="upload-form__wide">
            Notes
            <textarea name="bio" placeholder="Short lecturer note or assignment area"></textarea>
        </label>
        <button class="btn btn--primary upload-form__wide" type="submit"><i class="fa-solid fa-user-plus"></i> Create lecturer account</button>
    </form>
</section>

<section class="panel">
    <div class="panel__head">
        <div>
            <h2>Lecturer accounts</h2>
            <p>Teachers listed here can sign in from the normal portal login using the password assigned by admin.</p>
        </div>
        <i class="fa-solid fa-users-gear"></i>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Title</th>
                    <th>Speciality</th>
                    <th>Assigned courses</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($lecturers)): ?>
                    <tr><td colspan="6">No lecturers have been added yet.</td></tr>
                <?php endif; ?>
                <?php foreach ($lecturers as $lecturer): ?>
                    <tr>
                        <td><?= e($lecturer['full_name']) ?></td>
                        <td><?= e($lecturer['email']) ?></td>
                        <td><?= e($lecturer['title'] ?? '') ?></td>
                        <td><?= e($lecturer['speciality'] ?? '') ?></td>
                        <td><?= e($lecturer['assigned_courses'] ?? '—') ?></td>
                        <td><span class="status"><?= e($lecturer['status']) ?></span></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
