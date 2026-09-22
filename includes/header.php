<?php
require_once __DIR__ . '/functions.php';
$pageTitle = $pageTitle ?? 'Startup Idea Collaboration Portal';
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle) ?> | Startup Portal</title>
<link rel="icon" href="<?= BASE_URL ?>/assets/img/favicon.png">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
<meta name="csrf-token" content="<?= csrf_token() ?>">
</head>
<body>
<script>
  window.APP_BASE_URL = "<?= BASE_URL ?>";
  // Apply saved theme before paint to avoid flash
  (function() {
    const theme = localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-bs-theme', theme);
  })();
</script>
