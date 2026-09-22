<?php
require_once __DIR__ . '/includes/functions.php';
requireLogin();
$uid = currentUserId();
$db = getDB();

$myIdeasCount = $db->prepare("SELECT COUNT(*) FROM startup_ideas WHERE user_id = ? AND status='active'");
$myIdeasCount->execute([$uid]); $myIdeasCount = (int)$myIdeasCount->fetchColumn();

$pendingRequests = $db->prepare("SELECT COUNT(*) FROM collaboration_requests WHERE receiver_id = ? AND status='pending'");
$pendingRequests->execute([$uid]); $pendingRequests = (int)$pendingRequests->fetchColumn();

$bookmarkCount = $db->prepare("SELECT COUNT(*) FROM bookmarks WHERE user_id = ?");
$bookmarkCount->execute([$uid]); $bookmarkCount = (int)$bookmarkCount->fetchColumn();

$teamCount = $db->prepare("SELECT COUNT(DISTINCT idea_id) FROM teams WHERE user_id = ?");
$teamCount->execute([$uid]); $teamCount = (int)$teamCount->fetchColumn();

$myIdeas = $db->prepare("
  SELECT si.*, (SELECT COUNT(*) FROM likes l WHERE l.idea_id = si.id) AS like_count,
         (SELECT COUNT(*) FROM comments cm WHERE cm.idea_id = si.id AND cm.status='active') AS comment_count
  FROM startup_ideas si WHERE si.user_id = ? AND si.status='active'
  ORDER BY si.created_at DESC LIMIT 5
");
$myIdeas->execute([$uid]);
$myIdeas = $myIdeas->fetchAll();

$requests = $db->prepare("
  SELECT cr.*, u.name AS sender_name, si.title AS idea_title
  FROM collaboration_requests cr
  JOIN users u ON u.id = cr.sender_id
  JOIN startup_ideas si ON si.id = cr.idea_id
  WHERE cr.receiver_id = ? AND cr.status = 'pending'
  ORDER BY cr.created_at DESC LIMIT 5
");
$requests->execute([$uid]);
$requests = $requests->fetchAll();

$notifs = $db->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$notifs->execute([$uid]);
$notifs = $notifs->fetchAll();

$savedIdeas = $db->prepare("
  SELECT si.* FROM bookmarks b JOIN startup_ideas si ON si.id = b.idea_id
  WHERE b.user_id = ? AND si.status='active' ORDER BY b.created_at DESC LIMIT 4
");
$savedIdeas->execute([$uid]);
$savedIdeas = $savedIdeas->fetchAll();

$pageTitle = 'Dashboard';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>
<div class="app-body">
  <?php include __DIR__ . '/includes/sidebar.php'; ?>
  <main class="app-main">
    <?php include __DIR__ . '/includes/flash.php'; ?>
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
      <div>
        <h3 class="fw-bold mb-0">Welcome back, <?= e($_SESSION['name']) ?> 👋</h3>
        <p class="text-muted mb-0">Here's what's happening with your startup journey.</p>
      </div>
      <a href="<?= BASE_URL ?>/create_idea.php" class="btn btn-primary"><i class="bi bi-plus-circle me-1"></i>New Idea</a>
    </div>

    <div class="row g-3 mb-4">
      <div class="col-6 col-lg-3">
        <div class="stat-card c1">
          <div class="d-flex justify-content-between align-items-start">
            <div><h3 class="fw-bold mb-0"><?= $myIdeasCount ?></h3><small>My Ideas</small></div>
            <i class="bi bi-lightbulb fs-3 opacity-75"></i>
          </div>
        </div>
      </div>
      <div class="col-6 col-lg-3">
        <div class="stat-card c2">
          <div class="d-flex justify-content-between align-items-start">
            <div><h3 class="fw-bold mb-0"><?= $pendingRequests ?></h3><small>Pending Requests</small></div>
            <i class="bi bi-person-plus fs-3 opacity-75"></i>
          </div>
        </div>
      </div>
      <div class="col-6 col-lg-3">
        <div class="stat-card c3">
          <div class="d-flex justify-content-between align-items-start">
            <div><h3 class="fw-bold mb-0"><?= $bookmarkCount ?></h3><small>Saved Ideas</small></div>
            <i class="bi bi-bookmark-star fs-3 opacity-75"></i>
          </div>
        </div>
      </div>
      <div class="col-6 col-lg-3">
        <div class="stat-card c4">
          <div class="d-flex justify-content-between align-items-start">
            <div><h3 class="fw-bold mb-0"><?= $teamCount ?></h3><small>Teams Joined</small></div>
            <i class="bi bi-people fs-3 opacity-75"></i>
          </div>
        </div>
      </div>
    </div>

    <div class="row g-4">
      <div class="col-lg-7">
        <div class="card p-4 mb-4">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0">My Startup Ideas</h5>
            <a href="<?= BASE_URL ?>/my_ideas.php" class="small">View all</a>
          </div>
          <?php if (empty($myIdeas)): ?>
            <p class="text-muted small mb-0">You haven't posted any ideas yet. <a href="<?= BASE_URL ?>/create_idea.php">Create one now</a>.</p>
          <?php endif; ?>
          <?php foreach ($myIdeas as $idea): ?>
            <div class="d-flex justify-content-between align-items-center border-bottom py-2">
              <div>
                <a href="<?= BASE_URL ?>/idea_details.php?id=<?= (int)$idea['id'] ?>" class="fw-semibold text-body"><?= e($idea['title']) ?></a>
                <div class="small text-muted">
                  <i class="bi bi-heart me-1"></i><?= (int)$idea['like_count'] ?>
                  <i class="bi bi-chat ms-2 me-1"></i><?= (int)$idea['comment_count'] ?>
                  <span class="badge badge-stage ms-2"><?= e($idea['current_stage']) ?></span>
                </div>
              </div>
              <a href="<?= BASE_URL ?>/edit_idea.php?id=<?= (int)$idea['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
            </div>
          <?php endforeach; ?>
        </div>

        <div class="card p-4">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0">Collaboration Requests</h5>
            <a href="<?= BASE_URL ?>/collaboration.php" class="small">View all</a>
          </div>
          <?php if (empty($requests)): ?>
            <p class="text-muted small mb-0">No pending collaboration requests.</p>
          <?php endif; ?>
          <?php foreach ($requests as $r): ?>
            <div id="collab-row-<?= (int)$r['id'] ?>" class="d-flex justify-content-between align-items-center border-bottom py-2">
              <div>
                <span class="fw-semibold"><?= e($r['sender_name']) ?></span> wants to join
                <span class="fw-semibold"><?= e($r['idea_title']) ?></span>
              </div>
              <div class="d-flex gap-1">
                <button class="btn btn-sm btn-success collab-action-btn" data-request-id="<?= (int)$r['id'] ?>" data-action="accept"><i class="bi bi-check2"></i></button>
                <button class="btn btn-sm btn-outline-danger collab-action-btn" data-request-id="<?= (int)$r['id'] ?>" data-action="reject"><i class="bi bi-x"></i></button>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="col-lg-5">
        <div class="card p-4 mb-4">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0">Recent Notifications</h5>
            <a href="<?= BASE_URL ?>/notifications.php" class="small">View all</a>
          </div>
          <?php if (empty($notifs)): ?>
            <p class="text-muted small mb-0">No notifications yet.</p>
          <?php endif; ?>
          <?php foreach ($notifs as $n): ?>
            <div class="d-flex gap-2 border-bottom py-2">
              <i class="bi bi-bell-fill text-primary"></i>
              <div>
                <div class="small"><?= e($n['message']) ?></div>
                <div class="text-muted" style="font-size:.75rem"><?= timeAgo($n['created_at']) ?></div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <div class="card p-4">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0">Saved Ideas</h5>
            <a href="<?= BASE_URL ?>/bookmarks.php" class="small">View all</a>
          </div>
          <?php if (empty($savedIdeas)): ?>
            <p class="text-muted small mb-0">You haven't saved any ideas yet.</p>
          <?php endif; ?>
          <?php foreach ($savedIdeas as $idea): ?>
            <a href="<?= BASE_URL ?>/idea_details.php?id=<?= (int)$idea['id'] ?>" class="d-block text-body border-bottom py-2 text-decoration-none">
              <span class="fw-semibold"><?= e($idea['title']) ?></span>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </main>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
