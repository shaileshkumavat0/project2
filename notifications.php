<?php
require_once __DIR__ . '/includes/functions.php';
requireLogin();
$db = getDB();
$uid = currentUserId();

$stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
$stmt->execute([$uid]);
$notifs = $stmt->fetchAll();

$icons = [
    'collab_request'   => 'bi-person-plus text-primary',
    'collab_accepted'  => 'bi-check-circle text-success',
    'collab_rejected'  => 'bi-x-circle text-danger',
    'comment'          => 'bi-chat-dots text-info',
    'like'             => 'bi-heart-fill text-danger',
    'new_team_member'  => 'bi-people-fill text-primary',
    'report'           => 'bi-flag-fill text-warning',
    'system'           => 'bi-info-circle text-secondary',
];

$pageTitle = 'Notifications';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>
<div class="app-body">
  <?php include __DIR__ . '/includes/sidebar.php'; ?>
  <main class="app-main">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h3 class="fw-bold mb-0">Notifications</h3>
      <button id="markAllReadBtn" class="btn btn-outline-secondary btn-sm">Mark all as read</button>
    </div>

    <div class="card">
      <?php foreach ($notifs as $n): ?>
        <a href="<?= $n['link'] ? BASE_URL . '/' . e($n['link']) : '#' ?>" class="text-decoration-none text-body">
          <div class="notif-item <?= $n['is_read'] ? '' : 'unread' ?> d-flex gap-3 p-3 border-bottom">
            <i class="bi <?= $icons[$n['type']] ?? 'bi-bell' ?> fs-5"></i>
            <div>
              <div><?= e($n['message']) ?></div>
              <div class="small text-muted"><?= timeAgo($n['created_at']) ?></div>
            </div>
          </div>
        </a>
      <?php endforeach; ?>
      <?php if (empty($notifs)): ?>
        <div class="text-center text-muted py-5">
          <i class="bi bi-bell-slash fs-1 d-block mb-2"></i>
          No notifications yet.
        </div>
      <?php endif; ?>
    </div>
  </main>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
