            <!-- Step 2: Section C Company Background -->
            <section class="panel three-d-card">
                <div class="panel__head">
                    <i class="fa-solid fa-building"></i>
                    <div><h2>C. Company Background Information</h2><p>Applicant's BB company details</p></div>
                </div>
                <div class="wide-form" style="margin-top:16px">
                    <label>BB Company Name <input type="text" name="bb_company_name" value="<?= e($application['bb_company_name'] ?? '') ?>"></label>
                    <label>Post of Responsibility <input type="text" name="post_of_responsibility" value="<?= e($application['post_of_responsibility'] ?? '') ?>"></label>
                    <label>Company Captain Name <input type="text" name="company_captain_name" value="<?= e($application['company_captain_name'] ?? '') ?>"></label>
                    <label>Company Captain Phone <input type="tel" name="company_captain_phone" value="<?= e($application['company_captain_phone'] ?? '') ?>"></label>
                    <label>Company Chaplain Name <input type="text" name="company_chaplain_name" value="<?= e($application['company_chaplain_name'] ?? '') ?>"></label>
                    <label>Company Chaplain Phone <input type="tel" name="company_chaplain_phone" value="<?= e($application['company_chaplain_phone'] ?? '') ?>"></label>
                    <label>Candidate's Church <input type="text" name="candidate_church" value="<?= e($application['candidate_church'] ?? '') ?>"></label>
                    <label>Candidate's Association <input type="text" name="candidate_association" value="<?= e($application['candidate_association'] ?? '') ?>"></label>
                    <label style="grid-column:1/-1">Candidate's Field <input type="text" name="candidate_field" value="<?= e($application['candidate_field'] ?? '') ?>"></label>
                </div>
            </section>
