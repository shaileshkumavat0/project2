<?php
require_once __DIR__ . '/includes/functions.php';
requireLogin();
$db = getDB();
$uid = currentUserId();
$stmt = $db->prepare("
  SELECT si.*, u.name AS author_name,
    (SELECT COUNT(*) FROM likes l WHERE l.idea_id = si.id) AS like_count
  FROM bookmarks b
  JOIN startup_ideas si ON si.id = b.idea_id
  JOIN users u ON u.id = si.user_id
  WHERE b.user_id = ? AND si.status = 'active'
  ORDER BY b.created_at DESC
");
$stmt->execute([$uid]);
$ideas = $stmt->fetchAll();

$pageTitle = 'Saved Ideas';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>
<div class="app-body">
  <?php include __DIR__ . '/includes/sidebar.php'; ?>
  <main class="app-main">
    <h3 class="fw-bold mb-4">Saved Ideas</h3>
    <div class="row g-4">
      <?php foreach ($ideas as $idea): ?>
        <div class="col-md-6 col-lg-4">
          <div class="card idea-card h-100 shadow-sm">
            <?php if ($idea['image']): ?>
              <img src="<?= BASE_URL ?>/uploads/ideas/<?= e($idea['image']) ?>" class="idea-thumb" alt="">
            <?php else: ?>
              <div class="idea-thumb-placeholder d-flex align-items-center justify-content-center"><i class="bi bi-lightbulb fs-1 text-white-50"></i></div>
            <?php endif; ?>
            <div class="card-body">
              <span class="badge badge-stage mb-2"><?= e($idea['current_stage']) ?></span>
              <h6 class="fw-bold mb-1"><?= e($idea['title']) ?></h6>
              <p class="small text-muted">by <?= e($idea['author_name']) ?></p>
              <div class="d-flex justify-content-between align-items-center">
                <span class="small text-muted"><i class="bi bi-heart me-1"></i><?= (int)$idea['like_count'] ?></span>
                <a href="<?= BASE_URL ?>/idea_details.php?id=<?= (int)$idea['id'] ?>" class="btn btn-sm btn-outline-primary">View</a>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
      <?php if (empty($ideas)): ?>
        <div class="col-12 text-center text-muted py-5">
          <i class="bi bi-bookmark-star fs-1 d-block mb-2"></i>
          You haven't saved any ideas yet. <a href="<?= BASE_URL ?>/ideas.php">Browse the feed</a>.
        </div>
      <?php endif; ?>
    </div>
  </main>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
