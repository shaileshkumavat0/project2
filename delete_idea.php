<?php
require_once __DIR__ . '/includes/functions.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('dashboard.php');
verify_csrf();

$id = (int)($_POST['id'] ?? 0);
$db = getDB();
$stmt = $db->prepare("SELECT * FROM startup_ideas WHERE id = ?");
$stmt->execute([$id]);
$idea = $stmt->fetch();

if (!$idea) {
    setFlash('danger', 'Idea not found.');
    redirect('dashboard.php');
}
if ($idea['user_id'] != currentUserId() && !isAdmin()) {
    setFlash('danger', 'You do not have permission to delete this idea.');
    redirect('dashboard.php');
}

$stmt = $db->prepare("DELETE FROM startup_ideas WHERE id = ?");
$stmt->execute([$id]);

// Clean up uploaded files
foreach ([UPLOAD_DIR_IDEAS . $idea['image'], UPLOAD_DIR_DOCS . $idea['document']] as $file) {
    if ($idea['image'] || $idea['document']) {
        if (is_file($file)) @unlink($file);
    }
}

setFlash('success', 'Idea deleted successfully.');
redirect(isAdmin() && $idea['user_id'] != currentUserId() ? 'admin/ideas.php' : 'dashboard.php');
