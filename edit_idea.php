<?php
require_once __DIR__ . '/includes/functions.php';
requireLogin();
$db = getDB();
$id = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare("SELECT * FROM startup_ideas WHERE id = ?");
$stmt->execute([$id]);
$idea = $stmt->fetch();

if (!$idea || ($idea['user_id'] != currentUserId() && !isAdmin())) {
    setFlash('danger', 'Idea not found or you do not have permission to edit it.');
    redirect('dashboard.php');
}

$categories = $db->query("SELECT * FROM categories ORDER BY name")->fetchAll();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $title = clean($_POST['title'] ?? '');
    $problem = trim($_POST['problem_statement'] ?? '');
    $solution = trim($_POST['proposed_solution'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $categoryId = (int)($_POST['category_id'] ?? 0) ?: null;
    $skills = clean($_POST['required_skills'] ?? '');
    $stage = $_POST['current_stage'] ?? 'Idea';
    $teamSize = max(1, (int)($_POST['team_size_needed'] ?? 1));
    $validStages = ['Idea','Prototype','MVP','Launched','Scaling'];

    if ($title === '' || strlen($title) < 5) $errors[] = 'Title must be at least 5 characters.';
    if ($problem === '') $errors[] = 'Problem statement is required.';
    if ($solution === '') $errors[] = 'Proposed solution is required.';
    if ($description === '') $errors[] = 'Description is required.';
    if (!in_array($stage, $validStages, true)) $errors[] = 'Invalid stage selected.';

    $imageName = $idea['image'];
    $docName = $idea['document'];
    try {
        if (!empty($_FILES['image']['name'])) {
            $imageName = uploadFile($_FILES['image'], UPLOAD_DIR_IDEAS, ['jpg','jpeg','png','webp'], 4 * 1024 * 1024);
        }
        if (!empty($_FILES['document']['name'])) {
            $docName = uploadFile($_FILES['document'], UPLOAD_DIR_DOCS, ['pdf','doc','docx','ppt','pptx'], 10 * 1024 * 1024);
        }
    } catch (RuntimeException $e) {
        $errors[] = $e->getMessage();
    }

    if (empty($errors)) {
        $stmt = $db->prepare("UPDATE startup_ideas SET title=?, problem_statement=?, proposed_solution=?, description=?, category_id=?, required_skills=?, current_stage=?, team_size_needed=?, image=?, document=? WHERE id=?");
        $stmt->execute([$title, $problem, $solution, $description, $categoryId, $skills, $stage, $teamSize, $imageName, $docName, $id]);
        setFlash('success', 'Idea updated successfully.');
        redirect('idea_details.php?id=' . $id);
    }
}

$pageTitle = 'Edit Idea';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>
<div class="app-body">
  <?php include __DIR__ . '/includes/sidebar.php'; ?>
  <main class="app-main">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h3 class="fw-bold mb-0">Edit Startup Idea</h3>
      <form method="POST" action="<?= BASE_URL ?>/delete_idea.php" onsubmit="return confirm('Delete this idea permanently?');">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int)$idea['id'] ?>">
        <button type="submit" class="btn btn-outline-danger btn-sm"><i class="bi bi-trash me-1"></i>Delete Idea</button>
      </form>
    </div>

    <?php foreach ($errors as $err): ?><div class="alert alert-danger py-2"><?= e($err) ?></div><?php endforeach; ?>

    <form method="POST" enctype="multipart/form-data" class="card p-4 needs-validation" novalidate>
      <?= csrf_field() ?>
      <div class="row g-3">
        <div class="col-12">
          <label class="form-label">Startup Title *</label>
          <input type="text" name="title" class="form-control" required minlength="5" value="<?= e($idea['title']) ?>">
        </div>
        <div class="col-12">
          <label class="form-label">Problem Statement *</label>
          <textarea name="problem_statement" class="form-control" rows="3" required><?= e($idea['problem_statement']) ?></textarea>
        </div>
        <div class="col-12">
          <label class="form-label">Proposed Solution *</label>
          <textarea name="proposed_solution" class="form-control" rows="3" required><?= e($idea['proposed_solution']) ?></textarea>
        </div>
        <div class="col-12">
          <label class="form-label">Full Description *</label>
          <textarea name="description" class="form-control" rows="5" required><?= e($idea['description']) ?></textarea>
        </div>
        <div class="col-md-6">
          <label class="form-label">Industry Category</label>
          <select name="category_id" class="form-select">
            <option value="">Select category</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?= (int)$cat['id'] ?>" <?= $cat['id'] == $idea['category_id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label">Current Stage *</label>
          <select name="current_stage" class="form-select" required>
            <?php foreach (['Idea','Prototype','MVP','Launched','Scaling'] as $s): ?>
              <option value="<?= $s ?>" <?= $s === $idea['current_stage'] ? 'selected' : '' ?>><?= $s ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label">Required Skills</label>
          <input type="text" name="required_skills" class="form-control" value="<?= e($idea['required_skills']) ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label">Team Size Needed</label>
          <input type="number" name="team_size_needed" class="form-control" min="1" value="<?= (int)$idea['team_size_needed'] ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label">Cover Image</label>
          <?php if ($idea['image']): ?>
            <img src="<?= BASE_URL ?>/uploads/ideas/<?= e($idea['image']) ?>" class="d-block mb-2 rounded-14" style="max-height:120px" alt="">
          <?php endif; ?>
          <input type="file" id="image" name="image" class="form-control" accept=".jpg,.jpeg,.png,.webp">
        </div>
        <div class="col-md-6">
          <label class="form-label">Supporting Document</label>
          <?php if ($idea['document']): ?>
            <div class="mb-2 small"><i class="bi bi-file-earmark-text me-1"></i><?= e($idea['document']) ?></div>
          <?php endif; ?>
          <input type="file" name="document" class="form-control" accept=".pdf,.doc,.docx,.ppt,.pptx">
        </div>
      </div>
      <div class="mt-4 d-flex gap-2">
        <button type="submit" class="btn btn-primary px-4">Save Changes</button>
        <a href="<?= BASE_URL ?>/idea_details.php?id=<?= (int)$idea['id'] ?>" class="btn btn-outline-secondary">Cancel</a>
      </div>
    </form>
  </main>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
