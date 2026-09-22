<?php
require_once __DIR__ . '/includes/functions.php';
if (isLoggedIn()) redirect('dashboard.php');

$sent = false;
$resetLinkForDemo = null; // In production, this would be emailed, not displayed.

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $db = getDB();
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // Always show the same message whether or not the email exists (avoid user enumeration)
    $sent = true;

    if ($user) {
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', time() + 3600);
        $stmt = $db->prepare("UPDATE users SET reset_token = ?, reset_token_expires = ? WHERE id = ?");
        $stmt->execute([$token, $expires, $user['id']]);

        // TODO: integrate PHPMailer/SMTP to actually email this link.
        $resetLinkForDemo = BASE_URL . '/reset_password.php?token=' . $token;
    }
}

$pageTitle = 'Forgot Password';
include __DIR__ . '/includes/header.php';
?>
<div class="auth-wrapper">
  <div class="card auth-card shadow-sm p-4 p-md-5">
    <div class="text-center mb-4">
      <span class="brand-badge mx-auto mb-2"><i class="bi bi-key-fill"></i></span>
      <h3 class="fw-bold mb-0">Reset your password</h3>
      <p class="text-muted small">We'll send you a reset link</p>
    </div>

    <?php if ($sent): ?>
      <div class="alert alert-success py-2">If that email exists in our system, a reset link has been sent.</div>
      <?php if ($resetLinkForDemo): ?>
        <div class="alert alert-info small">
          <strong>Demo mode</strong> (no mail server configured): 
          <a href="<?= e($resetLinkForDemo) ?>"><?= e($resetLinkForDemo) ?></a>
        </div>
      <?php endif; ?>
    <?php else: ?>
      <form method="POST" class="needs-validation" novalidate>
        <?= csrf_field() ?>
        <div class="mb-3">
          <label class="form-label">Email Address</label>
          <input type="email" name="email" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary w-100">Send Reset Link</button>
      </form>
    <?php endif; ?>

    <p class="text-center small text-muted mt-4 mb-0">
      <a href="<?= BASE_URL ?>/login.php" class="fw-semibold">Back to login</a>
    </p>
  </div>
</div>
<?php include __DIR__ . '/includes/footer_simple.php'; ?>
