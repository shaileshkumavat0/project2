<?php
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $name = clean($_POST['name'] ?? '');
        if ($name !== '') {
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', $name), '-'));
            try {
                $db->prepare("INSERT INTO categories (name, slug) VALUES (?,?)")->execute([$name, $slug]);
                setFlash('success', 'Category added.');
            } catch (PDOException $e) {
                setFlash('danger', 'Category already exists.');
            }
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $db->prepare("DELETE FROM categories WHERE id=?")->execute([$id]);
        setFlash('success', 'Category deleted.');
    }
    redirect('admin/categories.php');
}

$categories = $db->query("
  SELECT c.*, (SELECT COUNT(*) FROM startup_ideas si WHERE si.category_id = c.id) AS idea_count
  FROM categories c ORDER BY c.name
")->fetchAll();

$pageTitle = 'Manage Categories';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>
<div class="app-body">
  <?php include __DIR__ . '/sidebar.php'; ?>
  <main class="app-main">
    <?php include __DIR__ . '/../includes/flash.php'; ?>
    <h3 class="fw-bold mb-4">Manage Categories</h3>

    <div class="row g-4">
      <div class="col-lg-4">
        <div class="card p-4">
          <h6 class="fw-bold mb-3">Add New Category</h6>
          <form method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="add">
            <div class="mb-3">
              <input type="text" name="name" class="form-control" placeholder="Category name" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Add Category</button>
          </form>
        </div>
      </div>
      <div class="col-lg-8">
        <div class="card p-3">
          <table class="table align-middle mb-0">
            <thead><tr><th>Name</th><th>Ideas</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
            <?php foreach ($categories as $c): ?>
              <tr>
                <td><?= e($c['name']) ?></td>
                <td><?= (int)$c['idea_count'] ?></td>
                <td class="text-end">
                  <form method="POST" onsubmit="return confirm('Delete this category?');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                    <button class="btn btn-sm btn-outline-danger">Delete</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </main>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
