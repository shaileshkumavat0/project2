<?php
require_once __DIR__ . '/includes/functions.php';
requireLogin();
$db = getDB();
$stmt = $db->prepare("
  SELECT si.*, (SELECT COUNT(*) FROM likes l WHERE l.idea_id = si.id) AS like_count,
    (SELECT COUNT(*) FROM comments cm WHERE cm.idea_id = si.id AND cm.status='active') AS comment_count,
    (SELECT COUNT(*) FROM teams t WHERE t.idea_id = si.id) AS team_count
  FROM startup_ideas si WHERE si.user_id = ? AND si.status='active'
  ORDER BY si.created_at DESC
");
$stmt->execute([currentUserId()]);
$ideas = $stmt->fetchAll();

$pageTitle = 'My Ideas';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>
<div class="app-body">
  <?php include __DIR__ . '/includes/sidebar.php'; ?>
  <main class="app-main">
    <?php include __DIR__ . '/includes/flash.php'; ?>
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h3 class="fw-bold mb-0">My Startup Ideas</h3>
      <a href="<?= BASE_URL ?>/create_idea.php" class="btn btn-primary"><i class="bi bi-plus-circle me-1"></i>New Idea</a>
    </div>

    <div class="row g-4">
      <?php foreach ($ideas as $idea): ?>
        <div class="col-md-6 col-lg-4">
          <div class="card idea-card h-100 shadow-sm">
            <?php if ($idea['image']): ?>
              <img src="<?= BASE_URL ?>/uploads/ideas/<?= e($idea['image']) ?>" class="idea-thumb" alt="">
            <?php else: ?>
              <div class="idea-thumb-placeholder d-flex align-items-center justify-content-center">
                <i class="bi bi-lightbulb fs-1 text-white-50"></i>
              </div>
            <?php endif; ?>
            <div class="card-body">
              <span class="badge badge-stage mb-2"><?= e($idea['current_stage']) ?></span>
              <h6 class="fw-bold mb-1"><?= e($idea['title']) ?></h6>
              <div class="small text-muted mb-3">
                <i class="bi bi-heart me-1"></i><?= (int)$idea['like_count'] ?>
                <i class="bi bi-chat ms-2 me-1"></i><?= (int)$idea['comment_count'] ?>
                <i class="bi bi-people ms-2 me-1"></i><?= (int)$idea['team_count'] ?>/<?= (int)$idea['team_size_needed'] ?>
              </div>
              <div class="d-flex gap-2">
                <a href="<?= BASE_URL ?>/idea_details.php?id=<?= (int)$idea['id'] ?>" class="btn btn-sm btn-outline-primary flex-fill">View</a>
                <a href="<?= BASE_URL ?>/edit_idea.php?id=<?= (int)$idea['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                <form method="POST" action="<?= BASE_URL ?>/delete_idea.php" onsubmit="return confirm('Delete this idea permanently?');">
                  <?= csrf_field() ?>
                  <input type="hidden" name="id" value="<?= (int)$idea['id'] ?>">
                  <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                </form>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
      <?php if (empty($ideas)): ?>
        <div class="col-12 text-center text-muted py-5">
          <i class="bi bi-lightbulb fs-1 d-block mb-2"></i>
          You haven't shared any startup ideas yet.
          <div class="mt-3"><a href="<?= BASE_URL ?>/create_idea.php" class="btn btn-primary">Share Your First Idea</a></div>
        </div>
      <?php endif; ?>
    </div>
  </main>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
