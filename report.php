<?php
require_once __DIR__ . '/includes/functions.php';
requireLogin();

$type = $_GET['type'] ?? $_POST['type'] ?? '';
$targetId = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$errors = [];
$success = false;

if (!in_array($type, ['idea', 'comment'], true) || $targetId <= 0) {
    setFlash('danger', 'Invalid report target.');
    redirect('ideas.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $reason = clean($_POST['reason'] ?? '');
    if ($reason === '') {
        $errors[] = 'Please provide a reason for the report.';
    } else {
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO reports (reporter_id, target_type, target_id, reason) VALUES (?,?,?,?)");
        $stmt->execute([currentUserId(), $type, $targetId, $reason]);
        $success = true;
    }
}

$pageTitle = 'Report Content';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>
<div class="app-body">
  <?php include __DIR__ . '/includes/sidebar.php'; ?>
  <main class="app-main">
    <div class="card p-4" style="max-width:500px">
      <h5 class="fw-bold mb-3"><i class="bi bi-flag-fill text-warning me-2"></i>Report <?= $type === 'idea' ? 'Startup Idea' : 'Comment' ?></h5>

      <?php if ($success): ?>
        <div class="alert alert-success">Thank you. Our admin team will review this report shortly.</div>
        <a href="javascript:history.back()" class="btn btn-outline-secondary">Go Back</a>
      <?php else: ?>
        <?php foreach ($errors as $err): ?><div class="alert alert-danger py-2"><?= e($err) ?></div><?php endforeach; ?>
        <form method="POST">
          <?= csrf_field() ?>
          <input type="hidden" name="type" value="<?= e($type) ?>">
          <input type="hidden" name="id" value="<?= (int)$targetId ?>">
          <div class="mb-3">
            <label class="form-label">Reason for reporting</label>
            <textarea name="reason" class="form-control" rows="4" required placeholder="Describe why this content is inappropriate..."></textarea>
          </div>
          <button type="submit" class="btn btn-warning">Submit Report</button>
          <a href="javascript:history.back()" class="btn btn-outline-secondary">Cancel</a>
        </form>
      <?php endif; ?>
    </div>
  </main>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
