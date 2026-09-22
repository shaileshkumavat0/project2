<?php
require_once __DIR__ . '/includes/functions.php';
if (isLoggedIn()) redirect('dashboard.php');

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name = clean($_POST['name'] ?? '');
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($name === '' || strlen($name) < 2) $errors[] = 'Please enter your full name.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
    if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';
    if ($password !== $confirm) $errors[] = 'Passwords do not match.';

    if (empty($errors)) {
        $db = getDB();
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = 'An account with this email already exists.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $token = bin2hex(random_bytes(20));
            $db->beginTransaction();
            try {
                $stmt = $db->prepare("INSERT INTO users (name, email, password, role, verification_token) VALUES (?,?,?,'student',?)");
                $stmt->execute([$name, $email, $hash, $token]);
                $userId = (int)$db->lastInsertId();

                $stmt = $db->prepare("INSERT INTO profiles (user_id) VALUES (?)");
                $stmt->execute([$userId]);

                $db->commit();
                setFlash('success', 'Account created successfully! You can now log in.');
                redirect('login.php');
            } catch (Exception $e) {
                $db->rollBack();
                $errors[] = 'Something went wrong. Please try again.';
            }
        }
    }
}

$pageTitle = 'Register';
include __DIR__ . '/includes/header.php';
?>
<div class="auth-wrapper">
  <div class="card auth-card shadow-sm p-4 p-md-5">
    <div class="text-center mb-4">
      <span class="brand-badge mx-auto mb-2"><i class="bi bi-rocket-takeoff-fill"></i></span>
      <h3 class="fw-bold mb-0">Create your account</h3>
      <p class="text-muted small">Join the student startup community</p>
    </div>

    <?php foreach ($errors as $err): ?>
      <div class="alert alert-danger py-2"><?= e($err) ?></div>
    <?php endforeach; ?>

    <form method="POST" class="needs-validation" novalidate>
      <?= csrf_field() ?>
      <div class="mb-3">
        <label class="form-label">Full Name</label>
        <input type="text" name="name" class="form-control" required value="<?= e($_POST['name'] ?? '') ?>">
      </div>
      <div class="mb-3">
        <label class="form-label">Email Address</label>
        <input type="email" name="email" class="form-control" required value="<?= e($_POST['email'] ?? '') ?>">
      </div>
      <div class="mb-3">
        <label class="form-label">Password</label>
        <input type="password" id="password" name="password" class="form-control" minlength="8" required>
        <div class="form-text">At least 8 characters.</div>
      </div>
      <div class="mb-3">
        <label class="form-label">Confirm Password</label>
        <input type="password" id="confirm_password" name="confirm_password" class="form-control" minlength="8" required>
      </div>
      <button type="submit" class="btn btn-primary w-100">Create Account</button>
    </form>

    <p class="text-center small text-muted mt-4 mb-0">
      Already have an account? <a href="<?= BASE_URL ?>/login.php" class="fw-semibold">Log in</a>
    </p>
  </div>
</div>
<?php include __DIR__ . '/includes/footer_simple.php'; ?>
