<?php
require_once __DIR__ . '/../config/config.php';
$pageTitle = 'About';
require __DIR__ . '/../views/layouts/header.php';
?>

<main>
    <section class="page-hero page-hero--about">
        <div class="hero__media hero__media--primary" aria-hidden="true"></div>
        <div class="hero__shade" aria-hidden="true"></div>
        <span class="eyebrow">About NEAC</span>
        <h1>Guiding policy for the organisation and functioning of NEAC.</h1>
        <p>The BB National Examination and Award Commission exists to implement, monitor, evaluate, and refine the BB Curriculum while managing examination and award standards.</p>
    </section>

    <section class="section about-section">
        <div class="policy-grid">
            <article class="policy-card policy-card--feature">
                <i class="fa-solid fa-eye"></i>
                <h3>Vision</h3>
                <p>To promote quality, standard, and fair treatment of all brigadiers in matters of examinations and awards.</p>
            </article>
            <article class="policy-card policy-card--feature">
                <i class="fa-solid fa-bullseye"></i>
                <h3>Mission</h3>
                <p>To implement, monitor, and evaluate the new BB Curriculum and make firm proposals toward a final contextual and contemporary curriculum by 2028.</p>
            </article>
        </div>

        <div class="about-columns">
            <article>
                <h3><i class="fa-solid fa-users-gear"></i> Membership</h3>
                <p>NEAC is made up of a Chairman, Secretary, National BB Chaplain, experienced BB Generals, Data Analysts, Educationists, a Military Officer, and other competent resource persons where required.</p>
            </article>
            <article>
                <h3><i class="fa-solid fa-user-tie"></i> Appointment and Tenure</h3>
                <p>Members are proposed by the Brigade Field Marshall and ratified by the National Executives. The first mandate covers the five-year period from 2023 to 2028.</p>
            </article>
            <article>
                <h3><i class="fa-solid fa-clipboard-check"></i> Chairman's Duties</h3>
                <p>The Chairman convenes meetings, leads implementation of resolutions, forwards proposals to the National BB Exco, appoints resource persons with NEAC, and approves NEAC-related expenses in collaboration with the Field Marshal.</p>
            </article>
            <article>
                <h3><i class="fa-solid fa-sitemap"></i> Technical Team</h3>
                <p>The technical structure includes the Archive Team, Rank Desk, Central Examiner, Designer, and Christian Education Desk for records, examinations, ranks, awards, and curriculum support.</p>
            </article>
        </div>

        <div class="function-panel">
            <div>
                <span class="eyebrow">Guiding functions</span>
                <h3>What NEAC is responsible for</h3>
            </div>
            <ul class="function-list">
                <li><i class="fa-solid fa-check"></i> Monitor and evaluate implementation of the BB Curriculum across the CBC.</li>
                <li><i class="fa-solid fa-check"></i> Receive proposals, corrections, and amendments during the five-year trial period.</li>
                <li><i class="fa-solid fa-check"></i> Organize seminars and workshops for curriculum implementation.</li>
                <li><i class="fa-solid fa-check"></i> Study promotion files and recommend awards or ranks to the National Exco.</li>
                <li><i class="fa-solid fa-check"></i> Set policies for curriculum, uniforms, ranks, awards, and badge regulations.</li>
                <li><i class="fa-solid fa-check"></i> Organize, scrutinize, grade, preserve, and run the National Officers Examination.</li>
                <li><i class="fa-solid fa-check"></i> Appoint examination focal persons including chiefs of centres, examiners, presidents of juries, and members.</li>
                <li><i class="fa-solid fa-check"></i> Recruit or approve lecturers and facilitators for National Officers Training and Examination.</li>
                <li><i class="fa-solid fa-check"></i> Organize oral interviews or defence sessions for top-ranking officials.</li>
                <li><i class="fa-solid fa-check"></i> Issue certificates and awards through the Field Marshal and report to the National Exco.</li>
            </ul>
        </div>

        <div class="about-columns">
            <article>
                <h3><i class="fa-solid fa-money-bill-wave"></i> Budget and Financing</h3>
                <p>NEAC may collect approved funds for its activities, deposit balances in the BB SPS account, submit annual budgets for approval, and process rank, defence, interview, and examination fees through the approved account.</p>
            </article>
            <article>
                <h3><i class="fa-solid fa-people-group"></i> Local Evaluation Committee</h3>
                <p>NEAC works with local monitoring representatives from companies implementing the curriculum, including Church Captains, teaching officers, chaplains, Association Majors, and Field Colonels.</p>
            </article>
            <article>
                <h3><i class="fa-solid fa-handshake-angle"></i> Local Committee Duties</h3>
                <p>The committee assesses curriculum implementation, provides local solutions, exchanges resources, sets proposed examination questions, and recommends areas for curriculum improvement.</p>
            </article>
            <article>
                <h3><i class="fa-solid fa-certificate"></i> Records and Awards</h3>
                <p>NEAC preserves examination records, publishes results, manages promotion and ranking files, and supports the design of badges, ranks, awards, certificates, and authorized cards.</p>
            </article>
        </div>
    </section>
</main>

<?php require __DIR__ . '/../views/layouts/footer.php'; ?>
