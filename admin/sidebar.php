<?php $current = basename($_SERVER['PHP_SELF']); ?>
<aside class="app-sidebar" id="appSidebar">
  <div class="p-3">
    <ul class="nav nav-pills flex-column gap-1">
      <li class="nav-item"><a class="nav-link <?= $current==='index.php'?'active':'' ?>" href="<?= BASE_URL ?>/admin/index.php"><i class="bi bi-speedometer2 me-2"></i>Overview</a></li>
      <li class="nav-item"><a class="nav-link <?= $current==='users.php'?'active':'' ?>" href="<?= BASE_URL ?>/admin/users.php"><i class="bi bi-people me-2"></i>Manage Users</a></li>
      <li class="nav-item"><a class="nav-link <?= $current==='ideas.php'?'active':'' ?>" href="<?= BASE_URL ?>/admin/ideas.php"><i class="bi bi-lightbulb me-2"></i>Manage Ideas</a></li>
      <li class="nav-item"><a class="nav-link <?= $current==='categories.php'?'active':'' ?>" href="<?= BASE_URL ?>/admin/categories.php"><i class="bi bi-tags me-2"></i>Categories</a></li>
      <li class="nav-item"><a class="nav-link <?= $current==='reports.php'?'active':'' ?>" href="<?= BASE_URL ?>/admin/reports.php"><i class="bi bi-flag me-2"></i>Reports</a></li>
      <li class="nav-item mt-2 pt-2 border-top"><a class="nav-link" href="<?= BASE_URL ?>/index.php"><i class="bi bi-box-arrow-left me-2"></i>Back to Site</a></li>
    </ul>
  </div>
</aside>
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>
