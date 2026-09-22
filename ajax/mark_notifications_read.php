<?php
require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json');

if (!isLoggedIn()) jsonResponse(['success' => false, 'login_required' => true]);
verify_csrf();

$db = getDB();
$stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
$stmt->execute([currentUserId()]);

jsonResponse(['success' => true]);
