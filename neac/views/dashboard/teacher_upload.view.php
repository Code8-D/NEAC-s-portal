<section class="panel upload-panel" id="resources">
    <div class="panel__head">
        <div>
            <h2>Upload teaching material</h2>
            <p>Share notes, PDFs, Word documents, presentations, and class videos with students.</p>
        </div>
        <i class="fa-solid fa-folder-open"></i>
    </div>
    <form class="upload-form" method="post" action="<?= BASE_URL ?>/upload.php" enctype="multipart/form-data">
        <?= Security::csrfField() ?>
        <input type="hidden" name="upload_type" value="teaching_material">
        <input type="hidden" name="return_to" value="dashboard.php?section=materials&upload=success">
        <?php $uploadCourses = $uploadCourses ?? $courses ?? []; ?>
        <?php if (empty($uploadCourses)): ?>
            <p class="notice notice--error"><i class="fa-solid fa-circle-exclamation"></i> No course assigned yet. The admin needs to assign you to a course before you can upload material.</p>
        <?php endif; ?>
        <label>
            Material title
            <input type="text" name="title" placeholder="e.g. Officer certification lecture notes" required>
        </label>
        <label>
            Course
            <select name="course" <?= empty($uploadCourses) ? 'disabled' : '' ?>>
                <?php foreach ($uploadCourses as $course): ?>
                    <option value="<?= e($course['code']) ?>"><?= e($course['title']) ?> (<?= e($course['code']) ?>)</option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            Material type
            <select name="material_type">
                <option value="notes">Notes</option>
                <option value="video">Video lesson</option>
                <option value="document">Document</option>
                <option value="presentation">Presentation</option>
            </select>
        </label>
        <label>
            Visibility
            <select name="visibility">
                <option value="students">Students</option>
                <option value="teachers">Teachers only</option>
            </select>
        </label>
        <label class="upload-form__wide">
            Description
            <textarea name="description" placeholder="Briefly describe the material"></textarea>
        </label>
        <label class="file-drop upload-form__wide">
            <i class="fa-solid fa-file-arrow-up"></i>
            <span>Choose file to upload</span>
            <small>PDF, Word, PowerPoint, Excel, images, ZIP, MP4, MOV, or AVI up to 80MB.</small>
            <input type="file" name="upload_file" required>
        </label>
        <button class="btn btn--primary upload-form__wide" type="submit"><i class="fa-solid fa-cloud-arrow-up"></i> Upload material</button>
    </form>
</section>
