<?php
require_once __DIR__ . '/includes/functions.php';
$db = getDB();
$ideaCount = $db->query("SELECT COUNT(*) FROM startup_ideas WHERE status='active'")->fetchColumn();
$userCount = $db->query("SELECT COUNT(*) FROM users WHERE role='student'")->fetchColumn();
$teamCount = $db->query("SELECT COUNT(DISTINCT idea_id) FROM teams")->fetchColumn();

$trending = $db->query("
  SELECT si.*, u.name AS author_name, c.name AS category_name,
    (SELECT COUNT(*) FROM likes l WHERE l.idea_id = si.id) AS like_count
  FROM startup_ideas si
  JOIN users u ON u.id = si.user_id
  LEFT JOIN categories c ON c.id = si.category_id
  WHERE si.status = 'active'
  ORDER BY si.created_at DESC LIMIT 6
")->fetchAll();

$pageTitle = 'Home';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>

<section class="hero-section">
  <div class="container">
    <div class="row align-items-center g-5">
      <div class="col-lg-6">
        <span class="badge rounded-pill text-bg-light border mb-3 px-3 py-2">🚀 Built by students, for students</span>
        <h1 class="hero-title mb-3">Turn your <span class="gradient-text">startup idea</span> into a real project</h1>
        <p class="lead text-muted mb-4">Share your idea, get honest feedback, find co-founders and teammates, and build something real — together with fellow students and aspiring entrepreneurs.</p>
        <div class="d-flex gap-3 flex-wrap">
          <a href="<?= BASE_URL ?>/<?= isLoggedIn() ? 'create_idea.php' : 'register.php' ?>" class="btn btn-primary btn-lg px-4">
            <i class="bi bi-lightbulb me-1"></i> Share an Idea
          </a>
          <a href="<?= BASE_URL ?>/ideas.php" class="btn btn-outline-primary btn-lg px-4">Explore Ideas</a>
        </div>
        <div class="row mt-5 g-3">
          <div class="col-4"><h3 class="fw-bold mb-0"><?= (int)$ideaCount ?>+</h3><span class="text-muted small">Ideas Shared</span></div>
          <div class="col-4"><h3 class="fw-bold mb-0"><?= (int)$userCount ?>+</h3><span class="text-muted small">Students</span></div>
          <div class="col-4"><h3 class="fw-bold mb-0"><?= (int)$teamCount ?>+</h3><span class="text-muted small">Teams Formed</span></div>
        </div>
      </div>
      <div class="col-lg-6">
        <div class="card p-4 shadow-lg rounded-14 border-0">
          <div class="d-flex align-items-center gap-2 mb-3">
            <span class="brand-badge"><i class="bi bi-cpu"></i></span>
            <div>
              <h6 class="mb-0 fw-bold">AI-Powered Study Buddy</h6>
              <small class="text-muted">Technology &bull; MVP stage</small>
            </div>
          </div>
          <p class="text-muted small mb-3">An adaptive learning assistant that builds custom revision plans for college students based on their syllabus and weak spots.</p>
          <div class="mb-3">
            <span class="skill-chip">React</span><span class="skill-chip">Node.js</span><span class="skill-chip">ML</span>
          </div>
          <div class="d-flex justify-content-between text-muted small">
            <span><i class="bi bi-heart-fill text-danger me-1"></i>128 likes</span>
            <span><i class="bi bi-people-fill me-1"></i>4/6 team</span>
            <span><i class="bi bi-chat-fill me-1"></i>32 comments</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="py-5">
  <div class="container">
    <div class="text-center mb-5">
      <h2 class="fw-bold">Everything you need to launch</h2>
      <p class="text-muted">From idea validation to team building — all in one place.</p>
    </div>
    <div class="row g-4">
      <div class="col-md-4">
        <div class="feature-icon mb-3"><i class="bi bi-lightbulb"></i></div>
        <h5 class="fw-bold">Share &amp; Validate Ideas</h5>
        <p class="text-muted small">Post your startup concept and get real feedback from peers before you build.</p>
      </div>
      <div class="col-md-4">
        <div class="feature-icon mb-3"><i class="bi bi-people"></i></div>
        <h5 class="fw-bold">Find Collaborators</h5>
        <p class="text-muted small">Send and receive collaboration requests, and build a team around your idea.</p>
      </div>
      <div class="col-md-4">
        <div class="feature-icon mb-3"><i class="bi bi-chat-dots"></i></div>
        <h5 class="fw-bold">Discuss &amp; Improve</h5>
        <p class="text-muted small">Threaded comments and replies let your community help refine your concept.</p>
      </div>
    </div>
  </div>
</section>

<section class="py-5 bg-body-tertiary">
  <div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2 class="fw-bold mb-0">Latest Startup Ideas</h2>
      <a href="<?= BASE_URL ?>/ideas.php" class="btn btn-sm btn-outline-primary">View All <i class="bi bi-arrow-right"></i></a>
    </div>
    <div class="row g-4">
      <?php foreach ($trending as $idea): ?>
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
            <p class="small text-muted mb-2"><?= e(mb_strimwidth($idea['problem_statement'], 0, 90, '...')) ?></p>
            <div class="d-flex justify-content-between align-items-center">
              <small class="text-muted">by <?= e($idea['author_name']) ?></small>
              <a href="<?= BASE_URL ?>/idea_details.php?id=<?= (int)$idea['id'] ?>" class="btn btn-sm btn-outline-primary">View</a>
            </div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
      <?php if (empty($trending)): ?>
        <p class="text-muted">No ideas posted yet. Be the first to share one!</p>
      <?php endif; ?>
    </div>
  </div>
</section>

<section class="py-5">
  <div class="container text-center">
    <h2 class="fw-bold mb-3">Ready to build something great?</h2>
    <p class="text-muted mb-4">Join hundreds of student founders already collaborating on the portal.</p>
    <a href="<?= BASE_URL ?>/<?= isLoggedIn() ? 'dashboard.php' : 'register.php' ?>" class="btn btn-primary btn-lg px-5">Get Started Free</a>
  </div>
</section>

<?php include __DIR__ . '/includes/footer_simple.php'; ?>
