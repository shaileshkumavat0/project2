<?php
require_once __DIR__ . '/includes/functions.php';
$db = getDB();
$categories = $db->query("SELECT * FROM categories ORDER BY name")->fetchAll();

$q = trim($_GET['q'] ?? '');
$categoryId = (int)($_GET['category'] ?? 0);
$stage = $_GET['stage'] ?? '';
$skill = trim($_GET['skill'] ?? '');
$sort = $_GET['sort'] ?? 'newest';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 9;
$offset = ($page - 1) * $perPage;

$where = ["si.status = 'active'"];
$params = [];

if ($q !== '') {
    $where[] = "(si.title LIKE ? OR si.problem_statement LIKE ? OR si.description LIKE ? OR si.required_skills LIKE ?)";
    $like = "%$q%";
    array_push($params, $like, $like, $like, $like);
}
if ($categoryId > 0) {
    $where[] = "si.category_id = ?";
    $params[] = $categoryId;
}
if ($stage !== '' && in_array($stage, ['Idea','Prototype','MVP','Launched','Scaling'], true)) {
    $where[] = "si.current_stage = ?";
    $params[] = $stage;
}
if ($skill !== '') {
    $where[] = "si.required_skills LIKE ?";
    $params[] = "%$skill%";
}

$whereSql = implode(' AND ', $where);

$orderSql = match ($sort) {
    'oldest' => 'si.created_at ASC',
    'most_liked' => 'like_count DESC',
    default => 'si.created_at DESC',
};

$countStmt = $db->prepare("SELECT COUNT(*) FROM startup_ideas si WHERE $whereSql");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();

$sql = "
  SELECT si.*, u.name AS author_name, c.name AS category_name,
    (SELECT COUNT(*) FROM likes l WHERE l.idea_id = si.id) AS like_count,
    (SELECT COUNT(*) FROM comments cm WHERE cm.idea_id = si.id AND cm.status='active') AS comment_count
    " . (isLoggedIn() ? ", (SELECT COUNT(*) FROM likes l2 WHERE l2.idea_id = si.id AND l2.user_id = " . (int)currentUserId() . ") AS liked_by_me,
    (SELECT COUNT(*) FROM bookmarks b2 WHERE b2.idea_id = si.id AND b2.user_id = " . (int)currentUserId() . ") AS bookmarked_by_me" : ", 0 AS liked_by_me, 0 AS bookmarked_by_me") . "
  FROM startup_ideas si
  JOIN users u ON u.id = si.user_id
  LEFT JOIN categories c ON c.id = si.category_id
  WHERE $whereSql
  ORDER BY $orderSql
  LIMIT $perPage OFFSET $offset
";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$ideas = $stmt->fetchAll();
$totalPages = (int)ceil($total / $perPage);

$pageTitle = 'Idea Feed';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>
<div class="app-body">
  <?php include __DIR__ . '/includes/sidebar.php'; ?>
  <main class="app-main">
    <?php include __DIR__ . '/includes/flash.php'; ?>
    <h3 class="fw-bold mb-4">Explore Startup Ideas</h3>

    <form method="GET" class="card p-3 mb-4">
      <div class="row g-2 align-items-end">
        <div class="col-md-3">
          <label class="form-label small mb-1">Keyword</label>
          <input type="text" name="q" class="form-control" placeholder="Search ideas..." value="<?= e($q) ?>">
        </div>
        <div class="col-md-2">
          <label class="form-label small mb-1">Industry</label>
          <select name="category" class="form-select">
            <option value="0">All</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?= (int)$cat['id'] ?>" <?= $categoryId == $cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label small mb-1">Stage</label>
          <select name="stage" class="form-select">
            <option value="">All</option>
            <?php foreach (['Idea','Prototype','MVP','Launched','Scaling'] as $s): ?>
              <option value="<?= $s ?>" <?= $stage === $s ? 'selected' : '' ?>><?= $s ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label small mb-1">Skill</label>
          <input type="text" name="skill" class="form-control" placeholder="e.g. Figma" value="<?= e($skill) ?>">
        </div>
        <div class="col-md-2">
          <label class="form-label small mb-1">Sort By</label>
          <select name="sort" class="form-select">
            <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest</option>
            <option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>>Oldest</option>
            <option value="most_liked" <?= $sort === 'most_liked' ? 'selected' : '' ?>>Most Liked</option>
          </select>
        </div>
        <div class="col-md-1">
          <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search"></i></button>
        </div>
      </div>
    </form>

    <p class="text-muted small"><?= $total ?> idea<?= $total === 1 ? '' : 's' ?> found</p>

    <div class="row g-4">
      <?php foreach ($ideas as $idea): ?>
        <div class="col-md-6 col-lg-4">
          <div class="card idea-card h-100 shadow-sm">
            <a href="<?= BASE_URL ?>/idea_details.php?id=<?= (int)$idea['id'] ?>">
              <?php if ($idea['image']): ?>
                <img src="<?= BASE_URL ?>/uploads/ideas/<?= e($idea['image']) ?>" class="idea-thumb" alt="">
              <?php else: ?>
                <div class="idea-thumb-placeholder d-flex align-items-center justify-content-center">
                  <i class="bi bi-lightbulb fs-1 text-white-50"></i>
                </div>
              <?php endif; ?>
            </a>
            <div class="card-body d-flex flex-column">
              <div class="d-flex justify-content-between mb-2">
                <span class="badge badge-stage"><?= e($idea['current_stage']) ?></span>
                <?php if ($idea['category_name']): ?><span class="badge text-bg-light border"><?= e($idea['category_name']) ?></span><?php endif; ?>
              </div>
              <a href="<?= BASE_URL ?>/idea_details.php?id=<?= (int)$idea['id'] ?>" class="text-body text-decoration-none">
                <h6 class="fw-bold mb-1"><?= e($idea['title']) ?></h6>
              </a>
              <p class="small text-muted mb-2 flex-grow-1"><?= e(mb_strimwidth($idea['problem_statement'], 0, 100, '...')) ?></p>
              <div class="mb-2">
                <?php foreach (array_slice(array_filter(array_map('trim', explode(',', (string)$idea['required_skills']))), 0, 3) as $sk): ?>
                  <span class="skill-chip"><?= e($sk) ?></span>
                <?php endforeach; ?>
              </div>
              <div class="d-flex justify-content-between align-items-center small text-muted mb-2">
                <span>by <?= e($idea['author_name']) ?></span>
                <span><?= timeAgo($idea['created_at']) ?></span>
              </div>
              <div class="d-flex justify-content-between align-items-center border-top pt-2">
                <button class="btn btn-sm btn-link text-decoration-none like-btn <?= $idea['liked_by_me'] ? 'liked' : '' ?>" data-idea-id="<?= (int)$idea['id'] ?>">
                  <i class="bi bi-heart<?= $idea['liked_by_me'] ? '-fill' : '' ?>"></i> <span class="like-count"><?= (int)$idea['like_count'] ?></span>
                </button>
                <a href="<?= BASE_URL ?>/idea_details.php?id=<?= (int)$idea['id'] ?>#comments" class="btn btn-sm btn-link text-decoration-none text-body">
                  <i class="bi bi-chat"></i> <?= (int)$idea['comment_count'] ?>
                </a>
                <button class="btn btn-sm btn-link text-decoration-none bookmark-btn <?= $idea['bookmarked_by_me'] ? 'saved' : '' ?>" data-idea-id="<?= (int)$idea['id'] ?>">
                  <i class="bi bi-bookmark<?= $idea['bookmarked_by_me'] ? '-fill' : '' ?>"></i>
                </button>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
      <?php if (empty($ideas)): ?>
        <div class="col-12 text-center text-muted py-5">
          <i class="bi bi-search fs-1 d-block mb-2"></i>
          No ideas match your filters. Try adjusting your search.
        </div>
      <?php endif; ?>
    </div>

    <?php if ($totalPages > 1): ?>
      <nav class="mt-4">
        <ul class="pagination justify-content-center">
          <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
              <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
            </li>
          <?php endfor; ?>
        </ul>
      </nav>
    <?php endif; ?>
  </main>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
