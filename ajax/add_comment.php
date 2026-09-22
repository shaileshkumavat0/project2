<?php
require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json');

if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'login_required' => true]);
}
verify_csrf();

$ideaId = (int)($_POST['idea_id'] ?? 0);
$parentId = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
$comment = trim($_POST['comment'] ?? '');
$uid = currentUserId();
$db = getDB();

if ($comment === '' || strlen($comment) > 2000) {
    jsonResponse(['success' => false, 'message' => 'Comment cannot be empty.']);
}

$stmt = $db->prepare("SELECT id, user_id, title FROM startup_ideas WHERE id = ? AND status='active'");
$stmt->execute([$ideaId]);
$idea = $stmt->fetch();
if (!$idea) jsonResponse(['success' => false, 'message' => 'Idea not found'], 404);

$stmt = $db->prepare("INSERT INTO comments (idea_id, user_id, parent_id, comment) VALUES (?,?,?,?)");
$stmt->execute([$ideaId, $uid, $parentId, $comment]);

// Notify idea owner (and parent comment author if a reply)
createNotification((int)$idea['user_id'], $uid, 'comment', $_SESSION['name'] . ' commented on "' . $idea['title'] . '"', $ideaId, 'idea_details.php?id=' . $ideaId . '#comments');

if ($parentId) {
    $stmt = $db->prepare("SELECT user_id FROM comments WHERE id = ?");
    $stmt->execute([$parentId]);
    $parentAuthor = $stmt->fetchColumn();
    if ($parentAuthor && $parentAuthor != $idea['user_id']) {
        createNotification((int)$parentAuthor, $uid, 'comment', $_SESSION['name'] . ' replied to your comment', $ideaId, 'idea_details.php?id=' . $ideaId . '#comments');
    }
}

jsonResponse(['success' => true]);
