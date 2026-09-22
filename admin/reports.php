<?php
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $reportId = (int)($_POST['report_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($action === 'dismiss') {
        $db->prepare("UPDATE reports SET status='dismissed' WHERE id=?")->execute([$reportId]);
        setFlash('success', 'Report dismissed.');
    } elseif ($action === 'remove_content') {
        $stmt = $db->prepare("SELECT * FROM reports WHERE id=?");
        $stmt->execute([$reportId]);
        $report = $stmt->fetch();
        if ($report) {
            if ($report['target_type'] === 'idea') {
                $db->prepare("UPDATE startup_ideas SET status='removed' WHERE id=?")->execute([$report['target_id']]);
            } else {
                $db->prepare("UPDATE comments SET status='removed' WHERE id=?")->execute([$report['target_id']]);
            }
            $db->prepare("UPDATE reports SET status='reviewed' WHERE id=?")->execute([$reportId]);
            setFlash('success', 'Content removed and report marked as reviewed.');
        }
    }
    redirect('admin/reports.php');
}

$reports = $db->query("
  SELECT r.*, u.name AS reporter_name FROM reports r
  JOIN users u ON u.id = r.reporter_id
  ORDER BY FIELD(r.status,'pending','reviewed','dismissed'), r.created_at DESC
")->fetchAll();

$pageTitle = 'Reports';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>
<div class="app-body">
  <?php include __DIR__ . '/sidebar.php'; ?>
  <main class="app-main">
    <?php include __DIR__ . '/../includes/flash.php'; ?>
    <h3 class="fw-bold mb-4">Content Reports</h3>

    <div class="card p-3">
      <div class="table-responsive">
        <table class="table align-middle">
          <thead><tr><th>Type</th><th>Reported By</th><th>Reason</th><th>Status</th><th>Date</th><th class="text-end">Actions</th></tr></thead>
          <tbody>
          <?php foreach ($reports as $r): ?>
            <tr>
              <td><span class="badge text-bg-light border"><?= ucfirst($r['target_type']) ?> #<?= (int)$r['target_id'] ?></span></td>
              <td><?= e($r['reporter_name']) ?></td>
              <td class="small"><?= e($r['reason']) ?></td>
              <td>
                <span class="badge <?= $r['status']==='pending' ? 'text-bg-warning' : ($r['status']==='reviewed' ? 'text-bg-success' : 'text-bg-secondary') ?>">
                  <?= ucfirst($r['status']) ?>
                </span>
              </td>
              <td class="small text-muted"><?= timeAgo($r['created_at']) ?></td>
              <td class="text-end">
                <?php if ($r['status'] === 'pending'): ?>
                <form method="POST" class="d-inline">
                  <?= csrf_field() ?>
                  <input type="hidden" name="report_id" value="<?= (int)$r['id'] ?>">
                  <button name="action" value="remove_content" class="btn btn-sm btn-outline-danger">Remove Content</button>
                  <button name="action" value="dismiss" class="btn btn-sm btn-outline-secondary">Dismiss</button>
                </form>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($reports)): ?><tr><td colspan="6" class="text-center text-muted py-4">No reports filed.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
