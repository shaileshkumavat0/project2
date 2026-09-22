<?php
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $userId = (int)($_POST['user_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($userId === currentUserId() && in_array($action, ['ban', 'delete'], true)) {
        setFlash('danger', 'You cannot ban or delete your own account.');
    } else {
        switch ($action) {
            case 'ban':
                $db->prepare("UPDATE users SET status='banned' WHERE id=?")->execute([$userId]);
                setFlash('success', 'User has been banned.');
                break;
            case 'unban':
                $db->prepare("UPDATE users SET status='active' WHERE id=?")->execute([$userId]);
                setFlash('success', 'User has been unbanned.');
                break;
            case 'delete':
                $db->prepare("DELETE FROM users WHERE id=?")->execute([$userId]);
                setFlash('success', 'User account deleted.');
                break;
        }
    }
    redirect('admin/users.php');
}

$q = trim($_GET['q'] ?? '');
$params = [];
$where = "role = 'student'";
if ($q !== '') {
    $where .= " AND (name LIKE ? OR email LIKE ?)";
    $params = ["%$q%", "%$q%"];
}
$stmt = $db->prepare("SELECT * FROM users WHERE $where ORDER BY created_at DESC");
$stmt->execute($params);
$users = $stmt->fetchAll();

$pageTitle = 'Manage Users';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>
<div class="app-body">
  <?php include __DIR__ . '/sidebar.php'; ?>
  <main class="app-main">
    <?php include __DIR__ . '/../includes/flash.php'; ?>
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h3 class="fw-bold mb-0">Manage Users</h3>
      <form method="GET" class="d-flex gap-2">
        <input type="text" name="q" class="form-control" placeholder="Search users..." value="<?= e($q) ?>">
        <button class="btn btn-outline-primary"><i class="bi bi-search"></i></button>
      </form>
    </div>

    <div class="card p-3">
      <div class="table-responsive">
        <table class="table align-middle">
          <thead><tr><th>Name</th><th>Email</th><th>Status</th><th>Joined</th><th class="text-end">Actions</th></tr></thead>
          <tbody>
          <?php foreach ($users as $u): ?>
            <tr>
              <td><?= e($u['name']) ?></td>
              <td><?= e($u['email']) ?></td>
              <td><span class="badge <?= $u['status']==='active' ? 'text-bg-success' : 'text-bg-danger' ?>"><?= ucfirst($u['status']) ?></span></td>
              <td class="small text-muted"><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
              <td class="text-end">
                <form method="POST" class="d-inline">
                  <?= csrf_field() ?>
                  <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                  <?php if ($u['status'] === 'active'): ?>
                    <button name="action" value="ban" class="btn btn-sm btn-outline-warning">Ban</button>
                  <?php else: ?>
                    <button name="action" value="unban" class="btn btn-sm btn-outline-success">Unban</button>
                  <?php endif; ?>
                  <button name="action" value="delete" class="btn btn-sm btn-outline-danger" onclick="return confirm('Permanently delete this user and all their data?');">Delete</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($users)): ?><tr><td colspan="5" class="text-center text-muted py-4">No users found.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
