<?php
require_once __DIR__ . '/includes/functions.php';
if (isLoggedIn()) redirect('dashboard.php');

$token = $_GET['token'] ?? $_POST['token'] ?? '';
$db = getDB();
$errors = [];
$success = false;

$stmt = $db->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_token_expires > NOW()");
$stmt->execute([$token]);
$user = $stmt->fetch();

if (!$user) {
    $errors[] = 'This password reset link is invalid or has expired.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';
    if ($password !== $confirm) $errors[] = 'Passwords do not match.';

    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $db->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expires = NULL WHERE id = ?");
        $stmt->execute([$hash, $user['id']]);
        $success = true;
    }
}

$pageTitle = 'Reset Password';
include __DIR__ . '/includes/header.php';
?>
<div class="auth-wrapper">
  <div class="card auth-card shadow-sm p-4 p-md-5">
    <div class="text-center mb-4">
      <span class="brand-badge mx-auto mb-2"><i class="bi bi-shield-lock-fill"></i></span>
      <h3 class="fw-bold mb-0">Set a new password</h3>
    </div>

    <?php foreach ($errors as $err): ?><div class="alert alert-danger py-2"><?= e($err) ?></div><?php endforeach; ?>

    <?php if ($success): ?>
      <div class="alert alert-success py-2">Password updated! You can now <a href="<?= BASE_URL ?>/login.php">log in</a>.</div>
    <?php elseif ($user): ?>
      <form method="POST" class="needs-validation" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="token" value="<?= e($token) ?>">
        <div class="mb-3">
          <label class="form-label">New Password</label>
          <input type="password" id="password" name="password" class="form-control" minlength="8" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Confirm New Password</label>
          <input type="password" id="confirm_password" name="confirm_password" class="form-control" minlength="8" required>
        </div>
        <button type="submit" class="btn btn-primary w-100">Update Password</button>
      </form>
    <?php endif; ?>
  </div>
</div>
<?php include __DIR__ . '/includes/footer_simple.php'; ?>
