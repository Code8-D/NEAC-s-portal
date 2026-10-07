<?php
require_once __DIR__ . '/../config/config.php';
$pageTitle = 'Modules';
require_once __DIR__ . '/../app/models/Setting.php';
$certBatch = (string) (Setting::get('cert_batch_label') ?? '4th Batch');
require __DIR__ . '/../views/layouts/header.php';
?>

<main>
    <section class="page-hero page-hero--modules">
        <div class="hero__media hero__media--primary" aria-hidden="true"></div>
        <div class="hero__shade" aria-hidden="true"></div>
        <span class="eyebrow">Core modules</span>
        <h1>Digital tools for the classroom, student management, and academic operations.</h1>
        <p>The platform opens with the classroom module — a space for courses, assignments, grading, and announcements.</p>
    </section>

    <section class="section section--tight">
        <div class="module-grid">
            <article class="module-card three-d-card">
                <i class="fa-solid fa-certificate"></i>
                <h3>BB Officers Certification</h3>
                <p>Complete application, payment, and recommendation workflow for the <?= e($certBatch) ?> BB National Officers Certification Examination. Fully digital form with dynamic transcript generation.</p>
                <a class="btn btn--ghost btn--3d" href="application.php"><i class="fa-solid fa-file-pen"></i> Apply Now</a>
            </article>
            <article class="module-card three-d-card">
                <i class="fa-solid fa-school"></i>
                <h3>Classroom</h3>
                <p>Each course gets its own classroom. Lecturers post announcements, share materials, create assignments, and grade with rubrics. Students join by code or link and submit work from their files.</p>
                <a class="btn btn--ghost btn--3d" href="classroom.php?action=join"><i class="fa-solid fa-keyboard"></i> Enter a class code</a>
            </article>
            <article class="module-card three-d-card">
                <i class="fa-solid fa-user-graduate"></i>
                <h3>Student Management Portal</h3>
                <p>Registration, admissions, profiles, attendance, fees, timetables, results, transcripts, notifications, and announcements.</p>
            </article>
            <article class="module-card three-d-card">
                <i class="fa-solid fa-book-open-reader"></i>
                <h3>Learning Resources</h3>
                <p>Learning resources, news, blog updates, FAQs, downloads, events, webinars, resource library, and search functionality.</p>
            </article>
            <article class="module-card three-d-card">
                <i class="fa-solid fa-chart-simple"></i>
                <h3>Grading & Transcripts</h3>
                <p>Lecturers enter marks per student per course. Admin approves and generates official transcripts with GPA calculation and degree classification.</p>
                <a class="btn btn--ghost btn--3d" href="lecturer_grading.php"><i class="fa-solid fa-chart-simple"></i> Enter Marks</a>
            </article>
        </div>
    </section>

    <section class="section split">
        <div>
            <span class="eyebrow">Role dashboards</span>
            <h2>Built around the people who use it every day.</h2>
            <p>Super admins manage the system, teachers run their classrooms, and students learn, submit work, and receive feedback.</p>
        </div>
        <div class="role-list">
            <div><i class="fa-solid fa-user-tie"></i><strong>Super Admin</strong><span>Users, classrooms, analytics, payments, configuration, audit trail.</span></div>
            <div><i class="fa-solid fa-person-chalkboard"></i><strong>Teachers</strong><span>Classrooms, assignments, materials, announcements, grading with rubrics.</span></div>
            <div><i class="fa-solid fa-graduation-cap"></i><strong>Students</strong><span>Enrolled classrooms, assignments, submissions, grades, announcements.</span></div>
        </div>
    </section>
</main>

<?php require __DIR__ . '/../views/layouts/footer.php'; ?>
