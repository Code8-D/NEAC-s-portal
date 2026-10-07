<section class="panel upload-panel" id="assignments">
    <div class="panel__head">
        <div>
            <h2>Submit assignment</h2>
            <p>Upload assignment documents, images, PDFs, or supporting files for review.</p>
        </div>
        <i class="fa-solid fa-cloud-arrow-up"></i>
    </div>
    <form class="upload-form" method="post" action="<?= BASE_URL ?>/upload.php" enctype="multipart/form-data">
        <?= Security::csrfField() ?>
        <input type="hidden" name="upload_type" value="assignment">
        <label>
            Assignment title
            <input type="text" name="title" placeholder="e.g. Curriculum reflection assignment" required>
        </label>
        <label>
            Related course
            <select name="course">
                <?php foreach ($courses as $course): ?>
                    <option value="<?= e($course['code']) ?>"><?= e($course['title']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="upload-form__wide">
            Submission note
            <textarea name="description" placeholder="Add a short note for your teacher"></textarea>
        </label>
        <label class="file-drop upload-form__wide">
            <i class="fa-solid fa-file-arrow-up"></i>
            <span>Choose assignment file</span>
            <small>PDF, Word, images, presentations, ZIP, or video files up to 80MB.</small>
            <input type="file" name="upload_file" required>
        </label>
        <button class="btn btn--primary upload-form__wide" type="submit"><i class="fa-solid fa-paper-plane"></i> Upload submission</button>
    </form>
</section>
