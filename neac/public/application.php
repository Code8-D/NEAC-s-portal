<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/core/Auth.php';
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/core/Security.php';

if (!Auth::check() || Auth::role() !== 'student') {
    header('Location: signup.php');
    exit;
}

$studentId = (int) Auth::studentId();
$userId = (int) Auth::userId();
$db = Database::connection();
Database::migrate();

$steps = [
    1 => 'Demographic Information',
    2 => 'Company Background',
    3 => 'BB Experience',
    4 => 'Christian Experience',
    5 => 'Education & Service',
    6 => 'Attestation & Signature',
];

$stepFields = [
    1 => ['full_name', 'date_of_birth', 'place_of_birth', 'residence', 'church_name', 'academic_qualification', 'rank_in_bb', 'phone', 'whatsapp', 'email', 'study_option'],
    2 => ['bb_company_name', 'post_of_responsibility', 'company_captain_name', 'company_captain_phone', 'company_chaplain_name', 'company_chaplain_phone', 'candidate_church', 'candidate_association', 'candidate_field'],
    3 => ['age_joined_bb', 'first_bb_company', 'year_joined_bb', 'first_captain_name', 'national_camps_attended', 'how_joined_bb', 'how_became_officer', 'current_role', 'pledge_for_bb'],
    4 => ['is_christian', 'what_makes_christian', 'date_of_baptism', 'baptized_by', 'salvation_testimony'],
    5 => ['educational_level', 'profession'],
    6 => ['signature'],
];

$step = isset($_GET['step']) && isset($steps[(int) $_GET['step']]) ? (int) $_GET['step'] : 1;

$existing = $db->prepare('SELECT * FROM certification_applications WHERE student_id = :sid LIMIT 1');
$existing->execute(['sid' => $studentId]);
$application = $existing->fetch();

require_once __DIR__ . '/../app/models/Setting.php';
$certBatch = (string) (Setting::get('cert_batch_label') ?? '4th Batch');
$certOpens = (string) (Setting::get('cert_opens_at') ?? '');
$certCloses = (string) (Setting::get('cert_closes_at') ?? '');
$certOpen = Setting::get('cert_applications_open') !== '0';
$today = date('Y-m-d');
$periodState = 'open';
if (!$certOpen) {
    $periodState = 'closed';
} elseif ($certOpens !== '' && $today < $certOpens) {
    $periodState = 'not_open';
} elseif ($certCloses !== '' && $today > $certCloses) {
    $periodState = 'closed';
}

if ($periodState !== 'open' && !$application) {
    $pageTitle = 'Applications Closed';
    $hideTopbar = true;
    require __DIR__ . '/../views/certification/application_closed.view.php';
    exit;
}

$trainings = [];
$services = [];
if ($application) {
    $tStmt = $db->prepare('SELECT * FROM bb_trainings WHERE application_id = :aid ORDER BY sort_order');
    $tStmt->execute(['aid' => $application['id']]);
    $trainings = $tStmt->fetchAll();
    $sStmt = $db->prepare('SELECT * FROM bb_services WHERE application_id = :aid ORDER BY sort_order');
    $sStmt->execute(['aid' => $application['id']]);
    $services = $sStmt->fetchAll();
}

$error = '';
$success = isset($_GET['saved']) ? 'Progress saved. You can continue anytime.' : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::requireCsrf();
    $action = $_POST['action'] ?? 'save';
    $postStep = isset($_POST['step']) && isset($steps[(int) $_POST['step']]) ? (int) $_POST['step'] : $step;
    $step = $postStep;

    $data = [];
    foreach ($stepFields[$step] as $field) {
        $data[$field] = $_POST[$field] ?? '';
    }
    if (isset($data['is_christian'])) {
        $data['is_christian'] = !empty($_POST['is_christian']) ? 1 : 0;
    }

    if ($step === 1 && (empty($data['full_name']) || empty($data['phone']) || empty($data['email']))) {
        $error = 'Full name, phone, and email are required.';
    } elseif ($action === 'submit' && empty($data['signature'])) {
        $error = 'Please type your full name as signature to attest the information.';
    } elseif ($action === 'submit' && empty($application['study_option'] ?? '')) {
        $error = 'Please select a study option (online or onsite) in step 1.';
    } else {
        try {
            $db->beginTransaction();
            if ($application) {
                $cols = [];
                $vals = [];
                foreach ($data as $k => $v) {
                    $cols[] = "$k = :$k";
                    $vals[$k] = $v;
                }
                $vals['aid'] = $application['id'];
                $stmt = $db->prepare('UPDATE certification_applications SET ' . implode(', ', $cols) . ', updated_at = CURRENT_TIMESTAMP WHERE id = :aid');
                $stmt->execute($vals);
                $appId = (int) $application['id'];
            } else {
                $cols = array_keys($data);
                $placeholders = array_map(fn($c) => ":$c", $cols);
                $stmt = $db->prepare('INSERT INTO certification_applications (student_id, ' . implode(', ', $cols) . ') VALUES (:sid, ' . implode(', ', $placeholders) . ')');
                $stmt->execute(array_merge(['sid' => $studentId], $data));
                $appId = (int) $db->lastInsertId();
            }

            if ($step === 5) {
                $trainingsData = $_POST['trainings'] ?? [];
                if (!empty($trainingsData)) {
                    $db->prepare('DELETE FROM bb_trainings WHERE application_id = :aid')->execute(['aid' => $appId]);
                    $ins = $db->prepare('INSERT INTO bb_trainings (application_id, seminar_name, duration, certificate_obtained, signatory, venue_level, training_date, sort_order) VALUES (:aid, :sn, :dur, :cert, :sig, :vl, :dt, :so)');
                    foreach ($trainingsData as $i => $t) {
                        if (!empty(trim($t['seminar_name'] ?? ''))) {
                            $ins->execute(['aid' => $appId, 'sn' => $t['seminar_name'], 'dur' => $t['duration'] ?? '', 'cert' => $t['certificate_obtained'] ?? '', 'sig' => $t['signatory'] ?? '', 'vl' => $t['venue_level'] ?? '', 'dt' => $t['training_date'] ?? '', 'so' => $i]);
                        }
                    }
                }

                $servicesData = $_POST['services'] ?? [];
                if (!empty($servicesData)) {
                    $db->prepare('DELETE FROM bb_services WHERE application_id = :aid')->execute(['aid' => $appId]);
                    $ins = $db->prepare('INSERT INTO bb_services (application_id, role, level, service_date, duration, sort_order) VALUES (:aid, :role, :lvl, :dt, :dur, :so)');
                    foreach ($servicesData as $i => $s) {
                        if (!empty(trim($s['role'] ?? ''))) {
                            $ins->execute(['aid' => $appId, 'role' => $s['role'], 'lvl' => $s['level'] ?? '', 'dt' => $s['service_date'] ?? '', 'dur' => $s['duration'] ?? '', 'so' => $i]);
                        }
                    }
                }
            }

            $db->commit();
            $existing = $db->prepare('SELECT * FROM certification_applications WHERE student_id = :sid LIMIT 1');
            $existing->execute(['sid' => $studentId]);
            $application = $existing->fetch();

            if ($step === 1) {
                $sync = $db->prepare('UPDATE students SET date_of_birth = :dob, place_of_birth = :pob, phone = :phone WHERE id = :sid');
                $sync->execute([
                    'dob' => $data['date_of_birth'] ?? null,
                    'pob' => $data['place_of_birth'] ?? null,
                    'phone' => $data['phone'] ?? null,
                    'sid' => $studentId,
                ]);
                if (!empty($data['full_name']) || !empty($data['email'])) {
                    $userSync = $db->prepare('UPDATE users SET full_name = COALESCE(NULLIF(:name, ""), full_name), email = COALESCE(NULLIF(:email, ""), email) WHERE id = :uid');
                    $userSync->execute(['name' => $data['full_name'] ?? '', 'email' => $data['email'] ?? '', 'uid' => $userId]);
                }
            }

            if ($action === 'submit') {
                $db->prepare('UPDATE certification_applications SET status = "submitted", application_date = CURRENT_TIMESTAMP WHERE id = :aid')->execute(['aid' => $appId]);
                require_once __DIR__ . '/../app/models/Notification.php';
                Notification::create($userId, 'Application Submitted Successfully', 'Your ' . $certBatch . ' BB Officers Certification application has been submitted. Awaiting admin review.', 'application');
                $adminStmt = $db->query('SELECT id FROM users WHERE role = "admin" LIMIT 1');
                $adminId = (int) $adminStmt->fetchColumn();
                if ($adminId) {
                    Notification::create($adminId, 'New Certification Application', 'A student has submitted a certification application. Review it in the admin panel.', 'application');
                }
                header('Location: application.php?submitted=1');
                exit;
            }

            $nextStep = $action === 'next' && $step < 6 ? $step + 1 : $step;
            header('Location: application.php?step=' . $nextStep . '&saved=1');
            exit;
        } catch (Throwable $e) {
            $db->rollBack();
            $error = 'An error occurred: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Certification Application Form';
$hideTopbar = true;
require __DIR__ . '/../views/certification/application_form.view.php';
