<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/core/Database.php';
$pageTitle = 'Home';

$homePosts = [];
$certPeriodState = 'open';
$certBatchLabel = '4th Batch';
if (file_exists(DB_PATH)) {
    try {
        require_once __DIR__ . '/../app/models/HomePost.php';
        $homePosts = HomePost::published(6);
        require_once __DIR__ . '/../app/models/Setting.php';
        $certBatchLabel = (string) (Setting::get('cert_batch_label') ?? '4th Batch');
        $opens = (string) (Setting::get('cert_opens_at') ?? '');
        $closes = (string) (Setting::get('cert_closes_at') ?? '');
        $today = date('Y-m-d');
        if (Setting::get('cert_applications_open') === '0') {
            $certPeriodState = 'closed';
        } elseif ($opens !== '' && $today < $opens) {
            $certPeriodState = 'not_open';
        } elseif ($closes !== '' && $today > $closes) {
            $certPeriodState = 'closed';
        }
    } catch (Throwable $e) {
        $homePosts = [];
    }
}
require __DIR__ . '/../views/layouts/header.php';
?>

<main>
    <section class="hero hero--home" id="home">
        <div class="hero__media hero__media--primary" aria-hidden="true"></div>
        <div class="hero__media hero__media--secondary" aria-hidden="true"></div>
        <div class="hero__shade" aria-hidden="true"></div>

        <div class="hero__content">
            <div class="eyebrow"><i class="fa-solid fa-shield-halved"></i> National Examination and Award Commission</div>
            <h1>Quality, standard, and fairness in examinations and awards.</h1>
            <p>NEAC promotes fair treatment of brigadiers, monitors curriculum implementation, and supports examination, certification, ranking, and award processes across the Brigade.</p>
            <div class="hero__actions">
                <a class="btn btn--primary btn--3d" href="login.php"><i class="fa-solid fa-right-to-bracket"></i> Enter portal</a>
                <a class="btn btn--ghost btn--light btn--3d" href="about.php"><i class="fa-solid fa-circle-info"></i> About NEAC</a>
            </div>
        </div>
        <div class="hero__panel">
            <span>Policy mandate</span>
            <strong>2023 - 2028</strong>
            <div class="mini-grid">
                <div><b>5 yrs</b><small>Trial period</small></div>
                <div><b>NEAC</b><small>Commission</small></div>
                <div><b>2028</b><small>Curriculum goal</small></div>
            </div>
        </div>
    </section>

    <section class="section home-intro">
        <div class="section__heading">
            <span class="eyebrow">Central academic operations</span>
            <h2>A practical platform for examination work, curriculum monitoring, and award administration.</h2>
            <p>NEAC's official digital platform brings together learning activities, student records, examination processes, and official communication in one system.</p>
        </div>
        <div class="home-feature-grid">
            <article class="three-d-card">
                <i class="fa-solid fa-book-open"></i>
                <h3>Curriculum support</h3>
                <p>Coordinate teaching resources, track curriculum implementation, collect feedback, and support the development of the final BB Curriculum by 2028.</p>
            </article>
            <article class="three-d-card">
                <i class="fa-solid fa-file-circle-check"></i>
                <h3>Examination management</h3>
                <p>Support registration, exam preparation, grading workflows, result publication, oral defence sessions, and secure preservation of examination records.</p>
            </article>
            <article class="three-d-card">
                <i class="fa-solid fa-award"></i>
                <h3>Awards and ranks</h3>
                <p>Organise promotion files, certificate records, rank recommendations, badges, authorised cards, and award documentation with better traceability.</p>
            </article>
        </div>
    </section>

    <section class="section home-posts" id="announcements">
        <div class="section__heading">
            <span class="eyebrow">Latest from NEAC</span>
            <h2>Announcements & events</h2>
            <p>Official updates, upcoming events, and status information from the Commission.</p>
        </div>
        <?php if (empty($homePosts)): ?>
            <div class="empty-state" style="border:1px dashed var(--line);border-radius:10px">
                <i class="fa-solid fa-bullhorn"></i>
                <h3>No posts yet</h3>
                <p>Check back soon for the latest announcements and events.</p>
            </div>
        <?php else: ?>
            <div class="home-posts-grid">
                <?php foreach ($homePosts as $hp):
                    $typeMeta = [
                        'event' => ['Event', 'fa-calendar-day'],
                        'status' => ['Status update', 'fa-circle-info'],
                        'announcement' => ['Announcement', 'fa-bullhorn'],
                    ][$hp['post_type']] ?? ['Announcement', 'fa-bullhorn'];
                    ?>
                    <article class="home-post-card three-d-card">
                        <?php if ($hp['image_path']): ?>
                            <img class="home-post-card__image" src="<?= BASE_URL ?>/post_image.php?id=<?= (int) $hp['id'] ?>" alt="<?= e($hp['title']) ?>">
                        <?php endif; ?>
                        <span class="home-post-card__type">
                            <i class="fa-solid <?= $typeMeta[1] ?>"></i> <?= $typeMeta[0] ?>
                        </span>
                        <h3><?= e($hp['title']) ?></h3>
                        <p><?= nl2br(e($hp['body'])) ?></p>
                        <small class="muted">
                            <?php if ($hp['post_type'] === 'event' && $hp['event_date']): ?>
                                <i class="fa-solid fa-calendar-day"></i> <?= e(date('j F Y', strtotime($hp['event_date']))) ?>
                            <?php else: ?>
                                <i class="fa-solid fa-clock"></i> <?= e(date('j F Y', strtotime($hp['created_at']))) ?>
                            <?php endif; ?>
                        </small>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="section" style="background:#102b4e;color:white;padding:clamp(42px,6vw,76px) clamp(18px,4vw,56px)">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:32px;align-items:center">
            <div>
                <span class="eyebrow" style="color:#d9a321"><?= e($certBatchLabel) ?> Certification</span>
                <h2 style="color:white;margin:8px 0 14px;font-size:clamp(30px,4vw,50px);line-height:1.05">BB Officers Certification Examination</h2>
                <?php if ($certPeriodState === 'closed'): ?>
                    <p style="color:rgba(255,255,255,.78);line-height:1.65">The <?= e($certBatchLabel) ?> BB Officers Certification Exam is currently going on. New applications are closed until the next batch is announced — please check back later or contact the commission for updates.</p>
                    <div class="hero__actions" style="margin-top:18px">
                        <a class="btn btn--ghost btn--light btn--3d" href="modules.php"><i class="fa-solid fa-circle-info"></i> How it works</a>
                    </div>
                <?php elseif ($certPeriodState === 'not_open'): ?>
                    <p style="color:rgba(255,255,255,.78);line-height:1.65">Applications for the <?= e($certBatchLabel) ?> BB Officers Certification Exam open on <strong style="color:#d9a321"><?= e(date('j F Y', strtotime($opens))) ?></strong>. Registration, the application form, recommendation forms, exam fee payment, and course access are all handled through the portal.</p>
                    <div class="hero__actions" style="margin-top:18px">
                        <a class="btn btn--primary btn--3d" href="signup.php" style="background:#d9a321;border-color:#d9a321;color:#102b4e"><i class="fa-solid fa-file-pen"></i> Apply Now</a>
                        <a class="btn btn--ghost btn--light btn--3d" href="modules.php"><i class="fa-solid fa-circle-info"></i> How it works</a>
                    </div>
                <?php else: ?>
                    <p style="color:rgba(255,255,255,.78);line-height:1.65">The <?= e($certBatchLabel) ?> BB Officers Certification Exam is now open. Registration, the application form, recommendation forms, exam fee payment, and course access are all handled through the portal.</p>
                    <div class="hero__actions" style="margin-top:18px">
                        <a class="btn btn--primary btn--3d" href="signup.php" style="background:#d9a321;border-color:#d9a321;color:#102b4e"><i class="fa-solid fa-file-pen"></i> Apply Now</a>
                        <a class="btn btn--ghost btn--light btn--3d" href="modules.php"><i class="fa-solid fa-circle-info"></i> How it works</a>
                    </div>
                <?php endif; ?>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                <div style="padding:20px;border-radius:10px;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.2)">
                    <i class="fa-solid fa-file-pen" style="color:#d9a321;font-size:24px"></i>
                    <h3 style="color:white;margin:10px 0 4px;font-size:16px">1. Apply</h3>
                    <p style="color:rgba(255,255,255,.7);margin:0;font-size:13px">Fill the digital application form</p>
                </div>
                <div style="padding:20px;border-radius:10px;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.2)">
                    <i class="fa-solid fa-file-signature" style="color:#d9a321;font-size:24px"></i>
                    <h3 style="color:white;margin:10px 0 4px;font-size:16px">2. Recommend</h3>
                    <p style="color:rgba(255,255,255,.7);margin:0;font-size:13px">Upload recommendation forms</p>
                </div>
                <div style="padding:20px;border-radius:10px;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.2)">
                    <i class="fa-solid fa-credit-card" style="color:#d9a321;font-size:24px"></i>
                    <h3 style="color:white;margin:10px 0 4px;font-size:16px">3. Pay</h3>
                    <p style="color:rgba(255,255,255,.7);margin:0;font-size:13px">Pay exam fee via Mobile Money</p>
                </div>
                <div style="padding:20px;border-radius:10px;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.2)">
                    <i class="fa-solid fa-school" style="color:#d9a321;font-size:24px"></i>
                    <h3 style="color:white;margin:10px 0 4px;font-size:16px">4. Learn</h3>
                    <p style="color:rgba(255,255,255,.7);margin:0;font-size:13px">Access courses and classrooms</p>
                </div>
            </div>
        </div>
    </section>

    <section class="section home-band">
        <div>
            <span class="eyebrow">Current release</span>
            <h2>Built for real use, and improved as usage grows.</h2>
            <p>The current release covers the workflows NEAC needs first: secure access, role dashboards, course and lesson management, assignments, basic quizzes, results, notifications, and records. Additional features will follow once staff, teachers, and students begin using the system.</p>
        </div>
        <div class="home-steps counter-row">
            <div class="counter-cell">
                <strong>3+</strong>
                <small>Core modules live</small>
            </div>
            <div class="counter-cell">
                <strong>100%</strong>
                <small>Role-aware dashboards</small>
            </div>
            <div class="counter-cell">
                <strong>2028</strong>
                <small>Curriculum target year</small>
            </div>
            <div class="counter-cell">
                <strong>5 yrs</strong>
                <small>Policy trial period</small>
            </div>
        </div>
    </section>

    <section class="section home-split">
        <div class="home-image-panel" aria-hidden="true"></div>
        <div>
            <span class="eyebrow">Who it serves</span>
            <h2>One portal for the teams behind NEAC's daily operations.</h2>
            <div class="role-list role-list--stacked">
                <div><i class="fa-solid fa-user-tie"></i><strong>Administrators</strong><span>Manage users, reports, system settings, payments, audit logs, courses, and institutional records from a restricted area.</span></div>
                <div><i class="fa-solid fa-person-chalkboard"></i><strong>Teachers</strong><span>Create courses, upload lessons, manage assignments and quizzes, grade submissions, and track student progress.</span></div>
                <div><i class="fa-solid fa-graduation-cap"></i><strong>Students</strong><span>Access enrolled courses, submit assignments, take quizzes and exams, view results, and receive announcements.</span></div>
            </div>
        </div>
    </section>

    <section class="section home-cta">
        <div class="cta-3d">
            <span class="eyebrow" style="color: rgba(255,255,255,.85);">Ready for use</span>
            <h2 style="color: #fff;">Sign in through the portal, then work in the dashboard for your role.</h2>
            <div class="hero__actions">
                <a class="btn btn--primary btn--3d" href="login.php"><i class="fa-solid fa-right-to-bracket"></i> Enter portal</a>
                <a class="btn btn--ghost btn--light btn--3d" href="modules.php"><i class="fa-solid fa-layer-group"></i> View modules</a>
            </div>
        </div>
    </section>
</main>

<?php require __DIR__ . '/../views/layouts/footer.php'; ?>
