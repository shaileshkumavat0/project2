<?php
require_once __DIR__ . '/includes/functions.php';
requireLogin();
$db = getDB();
$uid = currentUserId();

$incoming = $db->prepare("
  SELECT cr.*, u.name AS sender_name, p.photo AS sender_photo, si.title AS idea_title
  FROM collaboration_requests cr
  JOIN users u ON u.id = cr.sender_id
  LEFT JOIN profiles p ON p.user_id = u.id
  JOIN startup_ideas si ON si.id = cr.idea_id
  WHERE cr.receiver_id = ? AND cr.status = 'pending'
  ORDER BY cr.created_at DESC
");
$incoming->execute([$uid]);
$incoming = $incoming->fetchAll();

$sent = $db->prepare("
  SELECT cr.*, si.title AS idea_title, u.name AS owner_name
  FROM collaboration_requests cr
  JOIN startup_ideas si ON si.id = cr.idea_id
  JOIN users u ON u.id = si.user_id
  WHERE cr.sender_id = ?
  ORDER BY cr.created_at DESC
");
$sent->execute([$uid]);
$sent = $sent->fetchAll();

$myTeams = $db->prepare("
  SELECT si.id, si.title, si.current_stage, t.role_in_team, t.joined_at
  FROM teams t JOIN startup_ideas si ON si.id = t.idea_id
  WHERE t.user_id = ? ORDER BY t.joined_at DESC
");
$myTeams->execute([$uid]);
$myTeams = $myTeams->fetchAll();

$pageTitle = 'Collaboration';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>
<div class="app-body">
  <?php include __DIR__ . '/includes/sidebar.php'; ?>
  <main class="app-main">
    <h3 class="fw-bold mb-4">Collaboration Hub</h3>

    <ul class="nav nav-tabs mb-4" role="tablist">
      <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#incoming">Incoming Requests (<?= count($incoming) ?>)</button></li>
      <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#sent">Sent Requests (<?= count($sent) ?>)</button></li>
      <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#teams">My Teams (<?= count($myTeams) ?>)</button></li>
    </ul>

    <div class="tab-content">
      <div class="tab-pane fade show active" id="incoming">
        <div class="card p-4">
          <?php foreach ($incoming as $r): ?>
            <div id="collab-row-<?= (int)$r['id'] ?>" class="d-flex justify-content-between align-items-center border-bottom py-3">
              <div class="d-flex gap-2 align-items-center">
                <img src="<?= BASE_URL ?>/uploads/profiles/<?= e($r['sender_photo'] ?: 'default.png') ?>" onerror="this.src='<?= BASE_URL ?>/assets/img/default-avatar.png'" class="avatar-sm" alt="">
                <div>
                  <div><span class="fw-semibold"><?= e($r['sender_name']) ?></span> wants to join <span class="fw-semibold"><?= e($r['idea_title']) ?></span></div>
                  <?php if ($r['message']): ?><div class="small text-muted">"<?= e($r['message']) ?>"</div><?php endif; ?>
                  <div class="small text-muted"><?= timeAgo($r['created_at']) ?></div>
                </div>
              </div>
              <div class="d-flex gap-1">
                <button class="btn btn-sm btn-success collab-action-btn" data-request-id="<?= (int)$r['id'] ?>" data-action="accept">Accept</button>
                <button class="btn btn-sm btn-outline-danger collab-action-btn" data-request-id="<?= (int)$r['id'] ?>" data-action="reject">Reject</button>
              </div>
            </div>
          <?php endforeach; ?>
          <?php if (empty($incoming)): ?><p class="text-muted small mb-0">No incoming requests.</p><?php endif; ?>
        </div>
      </div>

      <div class="tab-pane fade" id="sent">
        <div class="card p-4">
          <?php foreach ($sent as $r): ?>
            <div class="d-flex justify-content-between align-items-center border-bottom py-3">
              <div>
                Request to join <a href="<?= BASE_URL ?>/idea_details.php?id=<?= (int)$r['idea_id'] ?>" class="fw-semibold"><?= e($r['idea_title']) ?></a>
                by <?= e($r['owner_name']) ?>
              </div>
              <span class="badge <?= $r['status'] === 'accepted' ? 'text-bg-success' : ($r['status'] === 'rejected' ? 'text-bg-danger' : 'text-bg-secondary') ?>">
                <?= ucfirst($r['status']) ?>
              </span>
            </div>
          <?php endforeach; ?>
          <?php if (empty($sent)): ?><p class="text-muted small mb-0">You haven't sent any collaboration requests.</p><?php endif; ?>
        </div>
      </div>

      <div class="tab-pane fade" id="teams">
        <div class="row g-3">
          <?php foreach ($myTeams as $t): ?>
            <div class="col-md-6">
              <div class="card p-3">
                <div class="d-flex justify-content-between">
                  <a href="<?= BASE_URL ?>/idea_details.php?id=<?= (int)$t['id'] ?>" class="fw-semibold text-body"><?= e($t['title']) ?></a>
                  <span class="badge badge-stage"><?= e($t['current_stage']) ?></span>
                </div>
                <div class="small text-muted">Role: <?= e($t['role_in_team']) ?> &bull; Joined <?= timeAgo($t['joined_at']) ?></div>
              </div>
            </div>
          <?php endforeach; ?>
          <?php if (empty($myTeams)): ?><p class="text-muted small">You're not part of any teams yet.</p><?php endif; ?>
        </div>
      </div>
    </div>
  </main>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
