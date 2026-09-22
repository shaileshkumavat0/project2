<?php
require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json');

if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'login_required' => true]);
}
verify_csrf();

$ideaId = (int)($_POST['idea_id'] ?? 0);
$uid = currentUserId();
$db = getDB();

$stmt = $db->prepare("SELECT id FROM startup_ideas WHERE id = ? AND status='active'");
$stmt->execute([$ideaId]);
if (!$stmt->fetch()) jsonResponse(['success' => false, 'message' => 'Idea not found'], 404);

$stmt = $db->prepare("SELECT id FROM bookmarks WHERE idea_id = ? AND user_id = ?");
$stmt->execute([$ideaId, $uid]);
$existing = $stmt->fetch();

if ($existing) {
    $db->prepare("DELETE FROM bookmarks WHERE id = ?")->execute([$existing['id']]);
    $bookmarked = false;
} else {
    $db->prepare("INSERT INTO bookmarks (idea_id, user_id) VALUES (?,?)")->execute([$ideaId, $uid]);
    $bookmarked = true;
}

jsonResponse(['success' => true, 'bookmarked' => $bookmarked]);
