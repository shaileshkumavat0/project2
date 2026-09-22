<?php
require_once __DIR__ . '/includes/functions.php';
if (isLoggedIn()) redirect('dashboard.php');

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';

    // Basic brute-force throttling per session
    $_SESSION['login_attempts'] = $_SESSION['login_attempts'] ?? 0;
    if ($_SESSION['login_attempts'] >= 6) {
        $errors[] = 'Too many failed attempts. Please wait a moment and try again.';
    } else {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            if ($user['status'] === 'banned') {
                $errors[] = 'Your account has been suspended. Contact support.';
            } else {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['role'] = $user['role'];
                unset($_SESSION['login_attempts']);

                $stmt = $db->prepare("SELECT photo FROM profiles WHERE user_id = ?");
                $stmt->execute([$user['id']]);
                $_SESSION['photo'] = $stmt->fetchColumn() ?: 'default.png';

                setFlash('success', 'Welcome back, ' . $user['name'] . '!');
                redirect($user['role'] === 'admin' ? 'admin/index.php' : 'dashboard.php');
            }
        } else {
            $_SESSION['login_attempts']++;
            $errors[] = 'Invalid email or password.';
        }
    }
}

$pageTitle = 'Login';
include __DIR__ . '/includes/header.php';
?>
<div class="auth-wrapper">
  <div class="card auth-card shadow-sm p-4 p-md-5">
    <div class="text-center mb-4">
      <span class="brand-badge mx-auto mb-2"><i class="bi bi-rocket-takeoff-fill"></i></span>
      <h3 class="fw-bold mb-0">Welcome back</h3>
      <p class="text-muted small">Log in to continue collaborating</p>
    </div>

    <?php foreach ($errors as $err): ?>
      <div class="alert alert-danger py-2"><?= e($err) ?></div>
    <?php endforeach; ?>

    <form method="POST" class="needs-validation" novalidate>
      <?= csrf_field() ?>
      <div class="mb-3">
        <label class="form-label">Email Address</label>
        <input type="email" name="email" class="form-control" required value="<?= e($_POST['email'] ?? '') ?>">
      </div>
      <div class="mb-2">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-control" required>
      </div>
      <div class="text-end mb-3">
        <a href="<?= BASE_URL ?>/forgot_password.php" class="small">Forgot password?</a>
      </div>
      <button type="submit" class="btn btn-primary w-100">Log In</button>
    </form>

    <p class="text-center small text-muted mt-4 mb-0">
      Don't have an account? <a href="<?= BASE_URL ?>/register.php" class="fw-semibold">Sign up</a>
    </p>
  </div>
</div>
<?php include __DIR__ . '/includes/footer_simple.php'; ?>
