<?php
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();
$db = getDB();

$stats = [
    'users'      => (int)$db->query("SELECT COUNT(*) FROM users WHERE role='student'")->fetchColumn(),
    'ideas'      => (int)$db->query("SELECT COUNT(*) FROM startup_ideas WHERE status='active'")->fetchColumn(),
    'comments'   => (int)$db->query("SELECT COUNT(*) FROM comments WHERE status='active'")->fetchColumn(),
    'teams'      => (int)$db->query("SELECT COUNT(DISTINCT idea_id) FROM teams")->fetchColumn(),
    'requests'   => (int)$db->query("SELECT COUNT(*) FROM collaboration_requests")->fetchColumn(),
    'reports'    => (int)$db->query("SELECT COUNT(*) FROM reports WHERE status='pending'")->fetchColumn(),
];

$recentUsers = $db->query("SELECT id, name, email, created_at FROM users WHERE role='student' ORDER BY created_at DESC LIMIT 5")->fetchAll();
$recentIdeas = $db->query("
  SELECT si.id, si.title, si.created_at, u.name AS author_name
  FROM startup_ideas si JOIN users u ON u.id = si.user_id
  WHERE si.status='active' ORDER BY si.created_at DESC LIMIT 5
")->fetchAll();

$categoryBreakdown = $db->query("
  SELECT c.name, COUNT(si.id) AS cnt FROM categories c
  LEFT JOIN startup_ideas si ON si.category_id = c.id AND si.status='active'
  GROUP BY c.id ORDER BY cnt DESC
")->fetchAll();

$pageTitle = 'Admin Overview';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>
<div class="app-body">
  <?php include __DIR__ . '/sidebar.php'; ?>
  <main class="app-main">
    <?php include __DIR__ . '/../includes/flash.php'; ?>
    <h3 class="fw-bold mb-4">Platform Statistics</h3>

    <div class="row g-3 mb-4">
      <div class="col-6 col-lg-2">
        <div class="stat-card c1"><h3 class="fw-bold mb-0"><?= $stats['users'] ?></h3><small>Students</small></div>
      </div>
      <div class="col-6 col-lg-2">
        <div class="stat-card c2"><h3 class="fw-bold mb-0"><?= $stats['ideas'] ?></h3><small>Ideas</small></div>
      </div>
      <div class="col-6 col-lg-2">
        <div class="stat-card c3"><h3 class="fw-bold mb-0"><?= $stats['comments'] ?></h3><small>Comments</small></div>
      </div>
      <div class="col-6 col-lg-2">
        <div class="stat-card c4"><h3 class="fw-bold mb-0"><?= $stats['teams'] ?></h3><small>Active Teams</small></div>
      </div>
      <div class="col-6 col-lg-2">
        <div class="stat-card c1"><h3 class="fw-bold mb-0"><?= $stats['requests'] ?></h3><small>Collab Requests</small></div>
      </div>
      <div class="col-6 col-lg-2">
        <div class="stat-card c3"><h3 class="fw-bold mb-0"><?= $stats['reports'] ?></h3><small>Pending Reports</small></div>
      </div>
    </div>

    <div class="row g-4">
      <div class="col-lg-4">
        <div class="card p-4">
          <h6 class="fw-bold mb-3">Ideas by Category</h6>
          <?php foreach ($categoryBreakdown as $c): ?>
            <div class="d-flex justify-content-between small border-bottom py-2">
              <span><?= e($c['name']) ?></span><span class="fw-semibold"><?= (int)$c['cnt'] ?></span>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="col-lg-4">
        <div class="card p-4">
          <h6 class="fw-bold mb-3">Recent Users</h6>
          <?php foreach ($recentUsers as $u): ?>
            <div class="border-bottom py-2">
              <div class="small fw-semibold"><?= e($u['name']) ?></div>
              <div class="text-muted" style="font-size:.75rem"><?= e($u['email']) ?> &bull; <?= timeAgo($u['created_at']) ?></div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="col-lg-4">
        <div class="card p-4">
          <h6 class="fw-bold mb-3">Recent Ideas</h6>
          <?php foreach ($recentIdeas as $idea): ?>
            <div class="border-bottom py-2">
              <a href="<?= BASE_URL ?>/idea_details.php?id=<?= (int)$idea['id'] ?>" class="small fw-semibold text-body"><?= e($idea['title']) ?></a>
              <div class="text-muted" style="font-size:.75rem">by <?= e($idea['author_name']) ?> &bull; <?= timeAgo($idea['created_at']) ?></div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </main>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
