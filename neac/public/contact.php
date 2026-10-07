<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/core/Auth.php';
require_once __DIR__ . '/../app/core/Security.php';
require_once __DIR__ . '/../app/helpers/Mail.php';

Database::migrate();
Auth::start();

$pageTitle = 'Contact';
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::requireCsrf();
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $type = $_POST['enquiry_type'] ?? '';
    $message = trim($_POST['message'] ?? '');
    if ($name && $email && $message) {
        $db = Database::connection();
        $stmt = $db->prepare(
            'INSERT INTO contact_messages (full_name, email, enquiry_type, message) VALUES (:n, :e, :t, :m)'
        );
        $stmt->execute(['n' => $name, 'e' => $email, 't' => $type, 'm' => $message]);
        Mail::send(
            'bbcameroonnationalexam@gmail.com',
            'Contact form: ' . $type . ' - ' . $name,
            '<p><strong>From:</strong> ' . e($name) . ' (' . e($email) . ')</p>'
            . '<p><strong>Type:</strong> ' . e($type) . '</p>'
            . '<p><strong>Message:</strong></p><blockquote style="padding:12px 16px;border-left:3px solid #102b4e;background:#f5f8fb;margin:0">'
            . nl2br(e($message)) . '</blockquote>',
            $email
        );
        $success = 'Your message has been sent. We will respond shortly.';
    } else {
        $error = 'Please fill in all required fields.';
    }
}

require __DIR__ . '/../views/layouts/header.php';
?>

<main>
    <section class="page-hero page-hero--contact">
        <div class="hero__media hero__media--primary" aria-hidden="true"></div>
        <div class="hero__shade" aria-hidden="true"></div>
        <span class="eyebrow">Contact</span>
        <h1>Reach the NEAC secretariat.</h1>
        <p>Send examination enquiries, curriculum implementation requests, award documentation questions, and official platform support messages.</p>
    </section>

    <section class="section contact-section">
        <div class="contact-card">
            <div>
                <span class="eyebrow">Official communication</span>
                <h2>Contact the commission</h2>
                <p>Use this section for examination enquiries, curriculum implementation support, award documentation, and official communication with the commission.</p>
                <div class="contact-list">
                    <span><i class="fa-solid fa-envelope"></i> bbcameroonnationalexam@gmail.com</span>
                    <span><i class="fa-solid fa-phone"></i> +237 674 318 534</span>
                    <span><i class="fa-solid fa-location-dot"></i> Boys' Brigade Cameroon</span>
                </div>
            </div>
            <form class="contact-form" method="post" action="<?= BASE_URL ?>/contact.php">
                <?= Security::csrfField() ?>
                <?php if ($error): ?><p class="alert"><i class="fa-solid fa-circle-exclamation"></i> <?= e($error) ?></p><?php endif; ?>
                <?php if ($success): ?><p class="notice notice--success"><i class="fa-solid fa-circle-check"></i> <?= e($success) ?></p><?php endif; ?>
                <input type="text" name="name" placeholder="Full name" aria-label="Full name" required>
                <input type="email" name="email" placeholder="Email address" aria-label="Email address" required>
                <select name="enquiry_type" aria-label="Enquiry type">
                    <option value="Examinations">Examinations</option>
                    <option value="Curriculum implementation">Curriculum implementation</option>
                    <option value="Awards and certificates">Awards and certificates</option>
                    <option value="Technical support">Technical support</option>
                </select>
                <textarea name="message" placeholder="Message" aria-label="Message" required></textarea>
                <button class="btn btn--primary" type="submit"><i class="fa-solid fa-paper-plane"></i> Send message</button>
            </form>
        </div>
    </section>
</main>

<?php require __DIR__ . '/../views/layouts/footer.php'; ?>
