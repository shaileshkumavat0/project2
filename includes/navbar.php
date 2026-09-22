<?php
$unread = isLoggedIn() ? unreadNotificationCount(currentUserId()) : 0;
?>
<nav class="navbar navbar-expand-lg sticky-top app-navbar">
  <div class="container-fluid px-3 px-lg-4">
    <button class="btn btn-icon d-lg-none me-2" id="sidebarToggle" type="button" aria-label="Toggle sidebar">
      <i class="bi bi-list fs-4"></i>
    </button>
    <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="<?= BASE_URL ?>/index.php">
      <span class="brand-badge"><i class="bi bi-rocket-takeoff-fill"></i></span>
      <span class="d-none d-sm-inline">Startup Portal</span>
    </a>

    <form class="d-none d-md-flex mx-auto search-form" action="<?= BASE_URL ?>/ideas.php" method="GET">
      <div class="input-group">
        <span class="input-group-text bg-transparent border-end-0"><i class="bi bi-search"></i></span>
        <input type="text" name="q" class="form-control border-start-0" placeholder="Search startup ideas..." value="<?= e($_GET['q'] ?? '') ?>">
      </div>
    </form>

    <div class="d-flex align-items-center gap-2 ms-auto">
      <button class="btn btn-icon" id="themeToggle" title="Toggle theme">
        <i class="bi bi-moon-stars-fill" id="themeIcon"></i>
      </button>

      <?php if (isLoggedIn()): ?>
        <a href="<?= BASE_URL ?>/notifications.php" class="btn btn-icon position-relative" title="Notifications">
          <i class="bi bi-bell-fill"></i>
          <?php if ($unread > 0): ?>
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
              <?= $unread > 9 ? '9+' : $unread ?>
            </span>
          <?php endif; ?>
        </a>

        <div class="dropdown">
          <a href="#" class="d-flex align-items-center gap-2 text-decoration-none dropdown-toggle" data-bs-toggle="dropdown">
            <img src="<?= BASE_URL ?>/uploads/profiles/<?= e($_SESSION['photo'] ?? 'default.png') ?>" onerror="this.src='<?= BASE_URL ?>/assets/img/default-avatar.png'" class="avatar-sm" alt="Avatar">
            <span class="d-none d-md-inline fw-medium"><?= e($_SESSION['name']) ?></span>
          </a>
          <ul class="dropdown-menu dropdown-menu-end shadow-sm">
            <li><a class="dropdown-item" href="<?= BASE_URL ?>/dashboard.php"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a></li>
            <li><a class="dropdown-item" href="<?= BASE_URL ?>/profile.php"><i class="bi bi-person me-2"></i>My Profile</a></li>
            <?php if (isAdmin()): ?>
              <li><a class="dropdown-item" href="<?= BASE_URL ?>/admin/index.php"><i class="bi bi-shield-lock me-2"></i>Admin Panel</a></li>
            <?php endif; ?>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
          </ul>
        </div>
      <?php else: ?>
        <a href="<?= BASE_URL ?>/login.php" class="btn btn-outline-primary btn-sm">Login</a>
        <a href="<?= BASE_URL ?>/register.php" class="btn btn-primary btn-sm">Sign Up</a>
      <?php endif; ?>
    </div>
  </div>
</nav>
