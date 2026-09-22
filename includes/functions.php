<?php
/**
 * includes/functions.php
 * Shared helper functions used across the application.
 */

require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    // Harden session cookies
    ini_set('session.use_strict_mode', 1);
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

/* ------------------------- Sanitization ------------------------- */

function clean(string $data): string
{
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function e(?string $data): string
{
    return htmlspecialchars($data ?? '', ENT_QUOTES, 'UTF-8');
}

/* ------------------------- CSRF Protection ------------------------- */

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

function verify_csrf(): void
{
    $token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
    if (empty($token) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        die('Invalid CSRF token. Please refresh the page and try again.');
    }
}

/* ------------------------- Auth Helpers ------------------------- */

function isLoggedIn(): bool
{
    return !empty($_SESSION['user_id']);
}

function isAdmin(): bool
{
    return isLoggedIn() && ($_SESSION['role'] ?? '') === 'admin';
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

function requireAdmin(): void
{
    if (!isAdmin()) {
        header('Location: ' . BASE_URL . '/index.php');
        exit;
    }
}

function currentUserId(): ?int
{
    return $_SESSION['user_id'] ?? null;
}

/* ------------------------- Flash Messages ------------------------- */

function setFlash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function getFlashes(): array
{
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flashes;
}

/* ------------------------- Notifications ------------------------- */

function createNotification(int $userId, ?int $actorId, string $type, string $message, ?int $ideaId = null, ?string $link = null): void
{
    // Don't notify yourself
    if ($actorId !== null && $actorId === $userId) {
        return;
    }
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO notifications (user_id, actor_id, type, idea_id, message, link) VALUES (?,?,?,?,?,?)");
    $stmt->execute([$userId, $actorId, $type, $ideaId, $message, $link]);
}

function unreadNotificationCount(int $userId): int
{
    $db = getDB();
    $stmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
}

/* ------------------------- File Upload Validation ------------------------- */

function uploadFile(array $file, string $destDir, array $allowedExt, int $maxSizeBytes): ?string
{
    if (empty($file['name'])) {
        return null;
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('File upload error.');
    }
    if ($file['size'] > $maxSizeBytes) {
        throw new RuntimeException('File is too large.');
    }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExt, true)) {
        throw new RuntimeException('Invalid file type: .' . $ext);
    }
    if (!is_dir($destDir)) {
        mkdir($destDir, 0755, true);
    }
    $newName = bin2hex(random_bytes(16)) . '.' . $ext;
    $target = $destDir . $newName;
    if (!move_uploaded_file($file['tmp_name'], $target)) {
        throw new RuntimeException('Failed to save uploaded file.');
    }
    return $newName;
}

/* ------------------------- Misc ------------------------- */

function timeAgo(string $datetime): string
{
    $diff = time() - strtotime($datetime);
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    if ($diff < 2592000) return floor($diff / 86400) . 'd ago';
    return date('M j, Y', strtotime($datetime));
}

function redirect(string $path): void
{
    header('Location: ' . BASE_URL . '/' . ltrim($path, '/'));
    exit;
}

function jsonResponse(array $data, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}
