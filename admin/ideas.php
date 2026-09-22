<?php
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $ideaId = (int)($_POST['idea_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    if ($action === 'remove') {
        $db->prepare("UPDATE startup_ideas SET status='removed' WHERE id=?")->execute([$ideaId]);
        setFlash('success', 'Idea removed from public view.');
    } elseif ($action === 'restore') {
        $db->prepare("UPDATE startup_ideas SET status='active' WHERE id=?")->execute([$ideaId]);
        setFlash('success', 'Idea restored.');
    } elseif ($action === 'delete') {
        $db->prepare("DELETE FROM startup_ideas WHERE id=?")->execute([$ideaId]);
        setFlash('success', 'Idea permanently deleted.');
    }
    redirect('admin/ideas.php');
}

$q = trim($_GET['q'] ?? '');
$params = [];
$where = "1=1";
if ($q !== '') { $where .= " AND si.title LIKE ?"; $params[] = "%$q%"; }

$stmt = $db->prepare("
  SELECT si.*, u.name AS author_name FROM startup_ideas si
  JOIN users u ON u.id = si.user_id WHERE $where ORDER BY si.created_at DESC
");
$stmt->execute($params);
$ideas = $stmt->fetchAll();

$pageTitle = 'Manage Ideas';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>
<div class="app-body">
  <?php include __DIR__ . '/sidebar.php'; ?>
  <main class="app-main">
    <?php include __DIR__ . '/../includes/flash.php'; ?>
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h3 class="fw-bold mb-0">Manage Startup Ideas</h3>
      <form method="GET" class="d-flex gap-2">
        <input type="text" name="q" class="form-control" placeholder="Search ideas..." value="<?= e($q) ?>">
        <button class="btn btn-outline-primary"><i class="bi bi-search"></i></button>
      </form>
    </div>

    <div class="card p-3">
      <div class="table-responsive">
        <table class="table align-middle">
          <thead><tr><th>Title</th><th>Author</th><th>Stage</th><th>Status</th><th>Posted</th><th class="text-end">Actions</th></tr></thead>
          <tbody>
          <?php foreach ($ideas as $idea): ?>
            <tr>
              <td><a href="<?= BASE_URL ?>/idea_details.php?id=<?= (int)$idea['id'] ?>"><?= e($idea['title']) ?></a></td>
              <td><?= e($idea['author_name']) ?></td>
              <td><span class="badge badge-stage"><?= e($idea['current_stage']) ?></span></td>
              <td><span class="badge <?= $idea['status']==='active' ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= ucfirst($idea['status']) ?></span></td>
              <td class="small text-muted"><?= date('M j, Y', strtotime($idea['created_at'])) ?></td>
              <td class="text-end">
                <form method="POST" class="d-inline">
                  <?= csrf_field() ?>
                  <input type="hidden" name="idea_id" value="<?= (int)$idea['id'] ?>">
                  <?php if ($idea['status'] === 'active'): ?>
                    <button name="action" value="remove" class="btn btn-sm btn-outline-warning">Remove</button>
                  <?php else: ?>
                    <button name="action" value="restore" class="btn btn-sm btn-outline-success">Restore</button>
                  <?php endif; ?>
                  <button name="action" value="delete" class="btn btn-sm btn-outline-danger" onclick="return confirm('Permanently delete this idea?');">Delete</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($ideas)): ?><tr><td colspan="6" class="text-center text-muted py-4">No ideas found.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
