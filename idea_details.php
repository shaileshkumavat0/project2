<?php
require_once __DIR__ . '/includes/functions.php';
$db = getDB();
$id = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare("
  SELECT si.*, u.name AS author_name, u.id AS author_id, c.name AS category_name,
    p.photo AS author_photo, p.college AS author_college
  FROM startup_ideas si
  JOIN users u ON u.id = si.user_id
  LEFT JOIN categories c ON c.id = si.category_id
  LEFT JOIN profiles p ON p.user_id = u.id
  WHERE si.id = ? AND si.status = 'active'
");
$stmt->execute([$id]);
$idea = $stmt->fetch();

if (!$idea) {
    setFlash('danger', 'Startup idea not found.');
    redirect('ideas.php');
}

$uid = currentUserId();

$likeCountStmt = $db->prepare("SELECT COUNT(*) FROM likes WHERE idea_id = ?");
$likeCountStmt->execute([$id]);
$likeCount = (int)$likeCountStmt->fetchColumn();

$likedByMe = false;
$bookmarkedByMe = false;
$alreadyRequested = false;
$isOwner = $uid && $uid == $idea['user_id'];
$isTeamMember = false;

if ($uid) {
    $stmt = $db->prepare("SELECT 1 FROM likes WHERE idea_id = ? AND user_id = ?");
    $stmt->execute([$id, $uid]); $likedByMe = (bool)$stmt->fetchColumn();

    $stmt = $db->prepare("SELECT 1 FROM bookmarks WHERE idea_id = ? AND user_id = ?");
    $stmt->execute([$id, $uid]); $bookmarkedByMe = (bool)$stmt->fetchColumn();

    $stmt = $db->prepare("SELECT status FROM collaboration_requests WHERE idea_id = ? AND sender_id = ?");
    $stmt->execute([$id, $uid]); $reqStatus = $stmt->fetchColumn();
    $alreadyRequested = $reqStatus ?: false;

    $stmt = $db->prepare("SELECT 1 FROM teams WHERE idea_id = ? AND user_id = ?");
    $stmt->execute([$id, $uid]); $isTeamMember = (bool)$stmt->fetchColumn();
}

$team = $db->prepare("
  SELECT t.*, u.name, p.photo FROM teams t
  JOIN users u ON u.id = t.user_id
  LEFT JOIN profiles p ON p.user_id = u.id
  WHERE t.idea_id = ? ORDER BY t.joined_at
");
$team->execute([$id]);
$team = $team->fetchAll();

// Top-level comments with replies
$comments = $db->prepare("
  SELECT cm.*, u.name AS author_name, p.photo AS author_photo,
    (SELECT COUNT(*) FROM comment_likes cl WHERE cl.comment_id = cm.id) AS like_count
  FROM comments cm
  JOIN users u ON u.id = cm.user_id
  LEFT JOIN profiles p ON p.user_id = u.id
  WHERE cm.idea_id = ? AND cm.parent_id IS NULL AND cm.status = 'active'
  ORDER BY cm.created_at DESC
");
$comments->execute([$id]);
$comments = $comments->fetchAll();

$repliesStmt = $db->prepare("
  SELECT cm.*, u.name AS author_name, p.photo AS author_photo
  FROM comments cm
  JOIN users u ON u.id = cm.user_id
  LEFT JOIN profiles p ON p.user_id = u.id
  WHERE cm.parent_id = ? AND cm.status = 'active'
  ORDER BY cm.created_at ASC
");

// Handle collaboration request submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_request'])) {
    requireLogin();
    verify_csrf();
    if (!$isOwner && !$alreadyRequested) {
        $msg = clean($_POST['message'] ?? '');
        $stmt = $db->prepare("INSERT INTO collaboration_requests (idea_id, sender_id, receiver_id, message) VALUES (?,?,?,?)");
        $stmt->execute([$id, $uid, $idea['user_id'], $msg]);
        createNotification((int)$idea['user_id'], $uid, 'collab_request', $_SESSION['name'] . ' wants to join "' . $idea['title'] . '"', $id, 'collaboration.php');
        setFlash('success', 'Collaboration request sent!');
        redirect('idea_details.php?id=' . $id);
    }
}

$pageTitle = $idea['title'];
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>
<div class="app-body">
  <?php include __DIR__ . '/includes/sidebar.php'; ?>
  <main class="app-main">
    <?php include __DIR__ . '/includes/flash.php'; ?>

    <div class="row g-4">
      <div class="col-lg-8">
        <div class="card mb-4 overflow-hidden">
          <?php if ($idea['image']): ?>
            <img src="<?= BASE_URL ?>/uploads/ideas/<?= e($idea['image']) ?>" class="w-100" style="max-height:320px;object-fit:cover" alt="">
          <?php endif; ?>
          <div class="p-4">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
              <div>
                <span class="badge badge-stage mb-2"><?= e($idea['current_stage']) ?></span>
                <?php if ($idea['category_name']): ?><span class="badge text-bg-light border mb-2"><?= e($idea['category_name']) ?></span><?php endif; ?>
                <h2 class="fw-bold mb-1"><?= e($idea['title']) ?></h2>
                <p class="text-muted small mb-0">Posted <?= timeAgo($idea['created_at']) ?> by
                  <a href="<?= BASE_URL ?>/profile.php?id=<?= (int)$idea['author_id'] ?>" class="fw-semibold"><?= e($idea['author_name']) ?></a>
                </p>
              </div>
              <?php if ($isOwner): ?>
                <a href="<?= BASE_URL ?>/edit_idea.php?id=<?= (int)$idea['id'] ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-pencil me-1"></i>Edit</a>
              <?php endif; ?>
            </div>

            <div class="d-flex gap-3 my-3 border-top border-bottom py-2">
              <button class="btn btn-link text-decoration-none like-btn <?= $likedByMe ? 'liked' : '' ?>" data-idea-id="<?= (int)$idea['id'] ?>">
                <i class="bi bi-heart<?= $likedByMe ? '-fill' : '' ?>"></i> <span class="like-count"><?= $likeCount ?></span> Likes
              </button>
              <a href="#comments" class="btn btn-link text-decoration-none text-body"><i class="bi bi-chat"></i> <?= count($comments) ?> Comments</a>
              <button class="btn btn-link text-decoration-none share-btn" data-url="<?= e(BASE_URL . '/idea_details.php?id=' . $idea['id']) ?>"><i class="bi bi-share"></i> Share</button>
              <button class="btn btn-link text-decoration-none bookmark-btn <?= $bookmarkedByMe ? 'saved' : '' ?>" data-idea-id="<?= (int)$idea['id'] ?>">
                <i class="bi bi-bookmark<?= $bookmarkedByMe ? '-fill' : '' ?>"></i> <span class="bookmark-label"><?= $bookmarkedByMe ? 'Saved' : 'Save' ?></span>
              </button>
            </div>

            <h6 class="fw-bold">Problem Statement</h6>
            <p><?= nl2br(e($idea['problem_statement'])) ?></p>
            <h6 class="fw-bold">Proposed Solution</h6>
            <p><?= nl2br(e($idea['proposed_solution'])) ?></p>
            <h6 class="fw-bold">Description</h6>
            <p><?= nl2br(e($idea['description'])) ?></p>

            <?php if ($idea['required_skills']): ?>
              <h6 class="fw-bold">Required Skills</h6>
              <div class="mb-3">
                <?php foreach (array_filter(array_map('trim', explode(',', $idea['required_skills']))) as $sk): ?>
                  <span class="skill-chip"><?= e($sk) ?></span>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>

            <?php if ($idea['document']): ?>
              <a href="<?= BASE_URL ?>/uploads/documents/<?= e($idea['document']) ?>" target="_blank" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-file-earmark-arrow-down me-1"></i> Download Supporting Document
              </a>
            <?php endif; ?>
          </div>
        </div>

        <!-- Comments -->
        <div class="card p-4" id="comments">
          <h5 class="fw-bold mb-3"><i class="bi bi-chat-dots me-1"></i>Discussion (<?= count($comments) ?>)</h5>

          <?php if (isLoggedIn()): ?>
          <form id="commentForm" class="mb-4">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="idea_id" value="<?= (int)$idea['id'] ?>">
            <div class="input-group">
              <textarea name="comment" class="form-control" placeholder="Share your thoughts or feedback..." rows="2" required></textarea>
              <button class="btn btn-primary" type="submit"><i class="bi bi-send"></i></button>
            </div>
          </form>
          <?php else: ?>
            <p class="text-muted small">Please <a href="<?= BASE_URL ?>/login.php">log in</a> to join the discussion.</p>
          <?php endif; ?>

          <?php foreach ($comments as $c): ?>
            <div class="d-flex gap-2 mb-3">
              <img src="<?= BASE_URL ?>/uploads/profiles/<?= e($c['author_photo'] ?: 'default.png') ?>" onerror="this.src='<?= BASE_URL ?>/assets/img/default-avatar.png'" class="avatar-sm flex-shrink-0" alt="">
              <div class="flex-grow-1">
                <div class="bg-body-tertiary rounded-14 p-2 px-3">
                  <span class="fw-semibold small"><?= e($c['author_name']) ?></span>
                  <p class="mb-0 small"><?= nl2br(e($c['comment'])) ?></p>
                </div>
                <div class="small text-muted mt-1 d-flex gap-3">
                  <span><?= timeAgo($c['created_at']) ?></span>
                  <span><?= (int)$c['like_count'] ?> likes</span>
                  <?php if (isLoggedIn()): ?>
                    <a href="#" class="reply-toggle" data-comment-id="<?= (int)$c['id'] ?>">Reply</a>
                    <a href="<?= BASE_URL ?>/report.php?type=comment&id=<?= (int)$c['id'] ?>" class="text-danger">Report</a>
                  <?php endif; ?>
                </div>

                <?php if (isLoggedIn()): ?>
                <form id="reply-form-<?= (int)$c['id'] ?>" class="d-none mt-2 comment-reply-form" method="POST" action="<?= BASE_URL ?>/ajax/add_comment.php">
                  <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                  <input type="hidden" name="idea_id" value="<?= (int)$idea['id'] ?>">
                  <input type="hidden" name="parent_id" value="<?= (int)$c['id'] ?>">
                  <div class="input-group input-group-sm">
                    <input type="text" name="comment" class="form-control" placeholder="Write a reply..." required>
                    <button class="btn btn-outline-primary" type="submit">Reply</button>
                  </div>
                </form>
                <?php endif; ?>

                <div class="comment-thread mt-2">
                  <?php
                  $repliesStmt->execute([$c['id']]);
                  foreach ($repliesStmt->fetchAll() as $reply): ?>
                    <div class="d-flex gap-2 mb-2">
                      <img src="<?= BASE_URL ?>/uploads/profiles/<?= e($reply['author_photo'] ?: 'default.png') ?>" onerror="this.src='<?= BASE_URL ?>/assets/img/default-avatar.png'" class="avatar-sm flex-shrink-0" alt="">
                      <div>
                        <div class="bg-body-tertiary rounded-14 p-2 px-3">
                          <span class="fw-semibold small"><?= e($reply['author_name']) ?></span>
                          <p class="mb-0 small"><?= nl2br(e($reply['comment'])) ?></p>
                        </div>
                        <div class="small text-muted mt-1"><?= timeAgo($reply['created_at']) ?></div>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
          <?php if (empty($comments)): ?>
            <p class="text-muted small">No comments yet. Be the first to share feedback!</p>
          <?php endif; ?>
        </div>
      </div>

      <div class="col-lg-4">
        <div class="card p-4 mb-4">
          <h6 class="fw-bold mb-3">Idea Owner</h6>
          <div class="d-flex align-items-center gap-2 mb-3">
            <img src="<?= BASE_URL ?>/uploads/profiles/<?= e($idea['author_photo'] ?: 'default.png') ?>" onerror="this.src='<?= BASE_URL ?>/assets/img/default-avatar.png'" class="avatar-md" alt="">
            <div>
              <div class="fw-semibold"><?= e($idea['author_name']) ?></div>
              <div class="small text-muted"><?= e($idea['author_college'] ?: '') ?></div>
            </div>
          </div>

          <?php if (!$isOwner): ?>
            <?php if (!isLoggedIn()): ?>
              <a href="<?= BASE_URL ?>/login.php" class="btn btn-primary w-100">Log in to Collaborate</a>
            <?php elseif ($isTeamMember): ?>
              <button class="btn btn-success w-100" disabled><i class="bi bi-check-circle me-1"></i>You're on the team</button>
            <?php elseif ($alreadyRequested === 'pending'): ?>
              <button class="btn btn-outline-secondary w-100" disabled>Request Pending</button>
            <?php elseif ($alreadyRequested === 'rejected'): ?>
              <button class="btn btn-outline-secondary w-100" disabled>Request Declined</button>
            <?php else: ?>
              <button class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#collabModal">
                <i class="bi bi-person-plus me-1"></i>Request to Collaborate
              </button>
            <?php endif; ?>
          <?php endif; ?>
        </div>

        <div class="card p-4">
          <h6 class="fw-bold mb-3">Team (<?= count($team) ?>/<?= (int)$idea['team_size_needed'] ?>)</h6>
          <?php foreach ($team as $member): ?>
            <div class="d-flex align-items-center gap-2 mb-2">
              <img src="<?= BASE_URL ?>/uploads/profiles/<?= e($member['photo'] ?: 'default.png') ?>" onerror="this.src='<?= BASE_URL ?>/assets/img/default-avatar.png'" class="avatar-sm" alt="">
              <div>
                <div class="small fw-semibold"><?= e($member['name']) ?></div>
                <div class="text-muted" style="font-size:.75rem"><?= e($member['role_in_team']) ?></div>
              </div>
            </div>
          <?php endforeach; ?>
          <?php if (empty($team)): ?>
            <p class="text-muted small mb-0">No team members yet.</p>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </main>
</div>

<!-- Collaboration Request Modal -->
<div class="modal fade" id="collabModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="POST" class="modal-content">
      <?= csrf_field() ?>
      <input type="hidden" name="send_request" value="1">
      <div class="modal-header">
        <h5 class="modal-title">Request to Collaborate</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <label class="form-label">Message to <?= e($idea['author_name']) ?></label>
        <textarea name="message" class="form-control" rows="4" placeholder="Introduce yourself and explain how you'd like to contribute..." required></textarea>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">Send Request</button>
      </div>
    </form>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
