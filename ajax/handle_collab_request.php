<?php
require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json');

if (!isLoggedIn()) jsonResponse(['success' => false, 'login_required' => true]);
verify_csrf();

$requestId = (int)($_POST['request_id'] ?? 0);
$action = $_POST['action'] ?? '';
$uid = currentUserId();
$db = getDB();

if (!in_array($action, ['accept', 'reject'], true)) {
    jsonResponse(['success' => false, 'message' => 'Invalid action']);
}

$stmt = $db->prepare("SELECT cr.*, si.title, si.team_size_needed FROM collaboration_requests cr
                       JOIN startup_ideas si ON si.id = cr.idea_id WHERE cr.id = ?");
$stmt->execute([$requestId]);
$request = $stmt->fetch();

if (!$request || $request['receiver_id'] != $uid) {
    jsonResponse(['success' => false, 'message' => 'Request not found'], 404);
}
if ($request['status'] !== 'pending') {
    jsonResponse(['success' => false, 'message' => 'Request already handled']);
}

$db->beginTransaction();
try {
    $newStatus = $action === 'accept' ? 'accepted' : 'rejected';
    $db->prepare("UPDATE collaboration_requests SET status = ? WHERE id = ?")->execute([$newStatus, $requestId]);

    if ($action === 'accept') {
        $stmt = $db->prepare("INSERT IGNORE INTO teams (idea_id, user_id) VALUES (?,?)");
        $stmt->execute([$request['idea_id'], $request['sender_id']]);

        createNotification((int)$request['sender_id'], $uid, 'collab_accepted', 'Your request to join "' . $request['title'] . '" was accepted!', (int)$request['idea_id'], 'idea_details.php?id=' . $request['idea_id']);

        // Notify existing team members about the new addition
        $members = $db->prepare("SELECT user_id FROM teams WHERE idea_id = ? AND user_id != ?");
        $members->execute([$request['idea_id'], $request['sender_id']]);
        foreach ($members->fetchAll() as $m) {
            createNotification((int)$m['user_id'], $request['sender_id'], 'new_team_member', 'A new member joined "' . $request['title'] . '"', (int)$request['idea_id'], 'idea_details.php?id=' . $request['idea_id']);
        }
    } else {
        createNotification((int)$request['sender_id'], $uid, 'collab_rejected', 'Your request to join "' . $request['title'] . '" was declined.', (int)$request['idea_id'], 'idea_details.php?id=' . $request['idea_id']);
    }

    $db->commit();
    jsonResponse(['success' => true]);
} catch (Exception $e) {
    $db->rollBack();
    jsonResponse(['success' => false, 'message' => 'Something went wrong.'], 500);
}
