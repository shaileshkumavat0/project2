<?php $current = basename($_SERVER['PHP_SELF']); ?>
<aside class="app-sidebar" id="appSidebar">
  <div class="p-3">
    <a href="<?= BASE_URL ?>/create_idea.php" class="btn btn-primary w-100 mb-3">
      <i class="bi bi-plus-circle me-1"></i> New Idea
    </a>
    <ul class="nav nav-pills flex-column gap-1">
      <li class="nav-item">
        <a class="nav-link <?= $current === 'dashboard.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/dashboard.php">
          <i class="bi bi-speedometer2 me-2"></i>Dashboard
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?= $current === 'ideas.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/ideas.php">
          <i class="bi bi-lightbulb me-2"></i>Idea Feed
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?= $current === 'my_ideas.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/my_ideas.php">
          <i class="bi bi-folder2-open me-2"></i>My Ideas
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?= $current === 'bookmarks.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/bookmarks.php">
          <i class="bi bi-bookmark-star me-2"></i>Saved Ideas
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?= $current === 'collaboration.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/collaboration.php">
          <i class="bi bi-people me-2"></i>Collaboration
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?= $current === 'notifications.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/notifications.php">
          <i class="bi bi-bell me-2"></i>Notifications
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?= $current === 'profile.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/profile.php">
          <i class="bi bi-person-circle me-2"></i>My Profile
        </a>
      </li>
      <?php if (isAdmin()): ?>
      <li class="nav-item mt-2 pt-2 border-top">
        <a class="nav-link" href="<?= BASE_URL ?>/admin/index.php">
          <i class="bi bi-shield-lock me-2"></i>Admin Panel
        </a>
      </li>
      <?php endif; ?>
    </ul>
  </div>
</aside>
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>
