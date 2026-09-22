<?php
require_once __DIR__ . '/includes/functions.php';
requireLogin();
$db = getDB();
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

    $imageName = null;
    $docName = null;
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
        $stmt = $db->prepare("INSERT INTO startup_ideas
          (user_id, title, problem_statement, proposed_solution, description, category_id, required_skills, current_stage, team_size_needed, image, document)
          VALUES (?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([
            currentUserId(), $title, $problem, $solution, $description,
            $categoryId, $skills, $stage, $teamSize, $imageName, $docName
        ]);
        setFlash('success', 'Your startup idea has been published!');
        redirect('idea_details.php?id=' . $db->lastInsertId());
    }
}

$pageTitle = 'Share a New Idea';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>
<div class="app-body">
  <?php include __DIR__ . '/includes/sidebar.php'; ?>
  <main class="app-main">
    <h3 class="fw-bold mb-4">Share a New Startup Idea</h3>

    <?php foreach ($errors as $err): ?><div class="alert alert-danger py-2"><?= e($err) ?></div><?php endforeach; ?>

    <form method="POST" enctype="multipart/form-data" class="card p-4 needs-validation" novalidate>
      <?= csrf_field() ?>
      <div class="row g-3">
        <div class="col-12">
          <label class="form-label">Startup Title *</label>
          <input type="text" name="title" class="form-control" required minlength="5" value="<?= e($_POST['title'] ?? '') ?>">
        </div>
        <div class="col-12">
          <label class="form-label">Problem Statement *</label>
          <textarea name="problem_statement" class="form-control" rows="3" required><?= e($_POST['problem_statement'] ?? '') ?></textarea>
        </div>
        <div class="col-12">
          <label class="form-label">Proposed Solution *</label>
          <textarea name="proposed_solution" class="form-control" rows="3" required><?= e($_POST['proposed_solution'] ?? '') ?></textarea>
        </div>
        <div class="col-12">
          <label class="form-label">Full Description *</label>
          <textarea name="description" class="form-control" rows="5" required><?= e($_POST['description'] ?? '') ?></textarea>
        </div>
        <div class="col-md-6">
          <label class="form-label">Industry Category</label>
          <select name="category_id" class="form-select">
            <option value="">Select category</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?= (int)$cat['id'] ?>"><?= e($cat['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label">Current Stage *</label>
          <select name="current_stage" class="form-select" required>
            <?php foreach (['Idea','Prototype','MVP','Launched','Scaling'] as $s): ?>
              <option value="<?= $s ?>"><?= $s ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label">Required Skills</label>
          <input type="text" name="required_skills" class="form-control" placeholder="e.g. React, UI/UX, Marketing" value="<?= e($_POST['required_skills'] ?? '') ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label">Team Size Needed</label>
          <input type="number" name="team_size_needed" class="form-control" min="1" value="<?= e($_POST['team_size_needed'] ?? '1') ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label">Cover Image</label>
          <input type="file" id="image" name="image" class="form-control" accept=".jpg,.jpeg,.png,.webp">
          <img id="imagePreview" class="d-none mt-2 rounded-14" style="max-height:160px" alt="preview">
        </div>
        <div class="col-md-6">
          <label class="form-label">Supporting Document</label>
          <input type="file" name="document" class="form-control" accept=".pdf,.doc,.docx,.ppt,.pptx">
          <div class="form-text">Pitch deck, business plan, etc. (PDF/DOC/PPT)</div>
        </div>
      </div>
      <div class="mt-4 d-flex gap-2">
        <button type="submit" class="btn btn-primary px-4">Publish Idea</button>
        <a href="<?= BASE_URL ?>/dashboard.php" class="btn btn-outline-secondary">Cancel</a>
      </div>
    </form>
  </main>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
