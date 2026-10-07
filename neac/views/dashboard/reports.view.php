<section class="panel" id="reports">
    <div class="panel__head">
        <div>
            <h2>Reports</h2>
            <p>Track learning activity, uploads, assessments, attendance, and platform operations.</p>
        </div>
        <i class="fa-solid fa-chart-line"></i>
    </div>
    <div class="report-grid">
        <article class="three-d-tile">
            <strong><?= (int) ($reportStats['courses'] ?? 0) ?></strong>
            <span>Courses created</span>
        </article>
        <article class="three-d-tile">
            <strong><?= (int) ($reportStats['classrooms'] ?? 0) ?></strong>
            <span>Classrooms active</span>
        </article>
        <article class="three-d-tile">
            <strong><?= (int) ($reportStats['students'] ?? 0) ?></strong>
            <span>Students registered</span>
        </article>
        <article class="three-d-tile">
            <strong><?= (int) ($reportStats['lecturers'] ?? 0) ?></strong>
            <span>Lecturers added</span>
        </article>
        <article class="three-d-tile">
            <strong><?= (int) ($reportStats['files'] ?? 0) ?></strong>
            <span>Files uploaded</span>
        </article>
    </div>
</section>
