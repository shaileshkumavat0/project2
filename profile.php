<?php
require_once __DIR__ . '/includes/functions.php';
$db = getDB();

$viewId = (int)($_GET['id'] ?? currentUserId() ?? 0);
if (!$viewId) redirect('login.php');
$isSelf = isLoggedIn() && $viewId === currentUserId();

if ($isSelf && $_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name = clean($_POST['name'] ?? '');
    $college = clean($_POST['college'] ?? '');
    $department = clean($_POST['department'] ?? '');
    $skills = clean($_POST['skills'] ?? '');
    $bio = clean($_POST['bio'] ?? '');
    $linkedin = clean($_POST['linkedin'] ?? '');
    $github = clean($_POST['github'] ?? '');
    $twitter = clean($_POST['twitter'] ?? '');
    $portfolio = clean($_POST['portfolio_link'] ?? '');

    $errors = [];
    if ($name === '') $errors[] = 'Name is required.';

    $photoName = null;
    try {
        if (!empty($_FILES['photo']['name'])) {
            $photoName = uploadFile($_FILES['photo'], UPLOAD_DIR_PROFILES, ['jpg','jpeg','png','webp'], 2 * 1024 * 1024);
        }
    } catch (RuntimeException $e) {
        $errors[] = $e->getMessage();
    }

    if (empty($errors)) {
        $db->prepare("UPDATE users SET name = ? WHERE id = ?")->execute([$name, $viewId]);

        if ($photoName) {
            $db->prepare("UPDATE profiles SET college=?, department=?, skills=?, bio=?, linkedin=?, github=?, twitter=?, portfolio_link=?, photo=? WHERE user_id=?")
               ->execute([$college, $department, $skills, $bio, $linkedin, $github, $twitter, $portfolio, $photoName, $viewId]);
            $_SESSION['photo'] = $photoName;
        } else {
            $db->prepare("UPDATE profiles SET college=?, department=?, skills=?, bio=?, linkedin=?, github=?, twitter=?, portfolio_link=? WHERE user_id=?")
               ->execute([$college, $department, $skills, $bio, $linkedin, $github, $twitter, $portfolio, $viewId]);
        }
        $_SESSION['name'] = $name;
        setFlash('success', 'Profile updated successfully.');
        redirect('profile.php');
    }
}

$stmt = $db->prepare("
  SELECT u.id, u.name, u.email, u.created_at, p.*
  FROM users u LEFT JOIN profiles p ON p.user_id = u.id
  WHERE u.id = ?
");
$stmt->execute([$viewId]);
$profile = $stmt->fetch();
if (!$profile) { setFlash('danger', 'User not found.'); redirect('ideas.php'); }

$ideaCount = $db->prepare("SELECT COUNT(*) FROM startup_ideas WHERE user_id = ? AND status='active'");
$ideaCount->execute([$viewId]); $ideaCount = (int)$ideaCount->fetchColumn();

$userIdeas = $db->prepare("SELECT id, title, current_stage, created_at FROM startup_ideas WHERE user_id = ? AND status='active' ORDER BY created_at DESC LIMIT 6");
$userIdeas->execute([$viewId]);
$userIdeas = $userIdeas->fetchAll();

$pageTitle = $isSelf ? 'My Profile' : $profile['name'] . "'s Profile";
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>
<div class="app-body">
  <?php include __DIR__ . '/includes/sidebar.php'; ?>
  <main class="app-main">
    <?php include __DIR__ . '/includes/flash.php'; ?>
    <?php foreach ($errors ?? [] as $err): ?><div class="alert alert-danger py-2"><?= e($err) ?></div><?php endforeach; ?>

    <div class="row g-4">
      <div class="col-lg-4">
        <div class="card p-4 text-center">
          <img src="<?= BASE_URL ?>/uploads/profiles/<?= e($profile['photo'] ?: 'default.png') ?>" onerror="this.src='<?= BASE_URL ?>/assets/img/default-avatar.png'" class="avatar-lg mx-auto mb-3" alt="">
          <h5 class="fw-bold mb-0"><?= e($profile['name']) ?></h5>
          <p class="text-muted small mb-2"><?= e($profile['department'] ?: '') ?><?= $profile['department'] && $profile['college'] ? ' &middot; ' : '' ?><?= e($profile['college'] ?: '') ?></p>
          <p class="small"><?= nl2br(e($profile['bio'] ?: '')) ?></p>

          <?php if ($profile['skills']): ?>
            <div class="mb-3">
              <?php foreach (array_filter(array_map('trim', explode(',', $profile['skills']))) as $sk): ?>
                <span class="skill-chip"><?= e($sk) ?></span>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

          <div class="d-flex justify-content-center gap-3">
            <?php if ($profile['linkedin']): ?><a href="<?= e($profile['linkedin']) ?>" target="_blank"><i class="bi bi-linkedin fs-5"></i></a><?php endif; ?>
            <?php if ($profile['github']): ?><a href="<?= e($profile['github']) ?>" target="_blank"><i class="bi bi-github fs-5"></i></a><?php endif; ?>
            <?php if ($profile['twitter']): ?><a href="<?= e($profile['twitter']) ?>" target="_blank"><i class="bi bi-twitter-x fs-5"></i></a><?php endif; ?>
            <?php if ($profile['portfolio_link']): ?><a href="<?= e($profile['portfolio_link']) ?>" target="_blank"><i class="bi bi-globe fs-5"></i></a><?php endif; ?>
          </div>

          <div class="mt-3 pt-3 border-top small text-muted">
            <?= $ideaCount ?> idea<?= $ideaCount === 1 ? '' : 's' ?> shared &bull; Joined <?= date('M Y', strtotime($profile['created_at'])) ?>
          </div>
        </div>
      </div>

      <div class="col-lg-8">
        <?php if ($isSelf): ?>
        <div class="card p-4 mb-4">
          <h5 class="fw-bold mb-3">Edit Profile</h5>
          <form method="POST" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label">Full Name</label>
                <input type="text" name="name" class="form-control" value="<?= e($profile['name']) ?>" required>
              </div>
              <div class="col-md-6">
                <label class="form-label">Profile Photo</label>
                <input type="file" name="photo" class="form-control" accept=".jpg,.jpeg,.png,.webp">
              </div>
              <div class="col-md-6">
                <label class="form-label">College</label>
                <input type="text" name="college" class="form-control" value="<?= e($profile['college']) ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label">Department</label>
                <input type="text" name="department" class="form-control" value="<?= e($profile['department']) ?>">
              </div>
              <div class="col-12">
                <label class="form-label">Skills (comma separated)</label>
                <input type="text" name="skills" class="form-control" value="<?= e($profile['skills']) ?>">
              </div>
              <div class="col-12">
                <label class="form-label">Bio</label>
                <textarea name="bio" class="form-control" rows="3"><?= e($profile['bio']) ?></textarea>
              </div>
              <div class="col-md-6">
                <label class="form-label">LinkedIn URL</label>
                <input type="url" name="linkedin" class="form-control" value="<?= e($profile['linkedin']) ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label">GitHub URL</label>
                <input type="url" name="github" class="form-control" value="<?= e($profile['github']) ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label">Twitter / X URL</label>
                <input type="url" name="twitter" class="form-control" value="<?= e($profile['twitter']) ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label">Portfolio Link</label>
                <input type="url" name="portfolio_link" class="form-control" value="<?= e($profile['portfolio_link']) ?>">
              </div>
            </div>
            <button type="submit" class="btn btn-primary mt-4">Save Changes</button>
          </form>
        </div>
        <?php endif; ?>

        <div class="card p-4">
          <h5 class="fw-bold mb-3">Startup Ideas</h5>
          <?php foreach ($userIdeas as $idea): ?>
            <div class="d-flex justify-content-between border-bottom py-2">
              <a href="<?= BASE_URL ?>/idea_details.php?id=<?= (int)$idea['id'] ?>" class="text-body fw-semibold"><?= e($idea['title']) ?></a>
              <span class="badge badge-stage"><?= e($idea['current_stage']) ?></span>
            </div>
          <?php endforeach; ?>
          <?php if (empty($userIdeas)): ?><p class="text-muted small mb-0">No ideas shared yet.</p><?php endif; ?>
        </div>
      </div>
    </div>
  </main>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
