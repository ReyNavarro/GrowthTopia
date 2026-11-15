<?php

require_once __DIR__ . '/db.php';

function sendSecurityHeaders(): void
{
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
}

function requireLogin()
{
    sendSecurityHeaders();

    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . getLoginUrl());
        exit;
    }

    header('Cache-Control: no-store, private');

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST'
        && !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        flashError('Your form expired. Please review the page and try again.');
        $requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
        $safePath = is_string($requestPath)
            && str_starts_with($requestPath, '/')
            && !str_starts_with($requestPath, '//')
            && !str_contains($requestPath, '\\');
        header('Location: ' . ($safePath ? $requestPath : getBaseUrl() . '/index.php'));
        exit;
    }
}

function requireAdmin()
{
    requireLogin();
    if ($_SESSION['role_id'] != 1) {
        header('Location: ' . getBaseUrl() . '/index.php?error=unauthorized');
        exit;
    }
}

function isAdmin()
{
    static $adminStatusByUser = [];
    if (!isset($_SESSION['user_id'])) {
        return false;
    }

    $userId = (int)$_SESSION['user_id'];
    if (array_key_exists($userId, $adminStatusByUser)) {
        return $adminStatusByUser[$userId];
    }

    $stmt = getDB()->prepare('SELECT role_id FROM users WHERE user_id = ?');
    $stmt->execute([$userId]);
    $roleId = $stmt->fetchColumn();
    if ($roleId === false) {
        return $adminStatusByUser[$userId] = false;
    }

    $_SESSION['role_id'] = (int)$roleId;
    return $adminStatusByUser[$userId] = ((int)$roleId === 1);
}

function currentUserActionPermissions(): array
{
    static $permissions = null;
    if ($permissions !== null) {
        return $permissions;
    }

    $permissions = ['add' => false, 'edit' => false, 'delete' => false];
    if (!isset($_SESSION['user_id']) || isAdmin()) {
        return $permissions;
    }

    $stmt = getDB()->prepare(
        'SELECT CASE WHEN can_add THEN 1 ELSE 0 END AS can_add,
                CASE WHEN can_edit THEN 1 ELSE 0 END AS can_edit,
                CASE WHEN can_delete THEN 1 ELSE 0 END AS can_delete
         FROM user_action_permissions
         WHERE user_id = ?'
    );
    $stmt->execute([(int)$_SESSION['user_id']]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        error_log('Action permissions are missing for user #' . (int)$_SESSION['user_id']);
        return $permissions;
    }

    return $permissions = [
        'add' => (bool)$row['can_add'],
        'edit' => (bool)$row['can_edit'],
        'delete' => (bool)$row['can_delete'],
    ];
}

function canPerformAction(string $action): bool
{
    if (!in_array($action, ['add', 'edit', 'delete'], true)) {
        throw new InvalidArgumentException('Unknown user action permission.');
    }

    return isAdmin() || currentUserActionPermissions()[$action];
}

function requireActionPermission(string $action): void
{
    requireLogin();
    if (!canPerformAction($action)) {
        flashError('Your account does not have permission to ' . $action . ' records.');
        header('Location: ' . getBaseUrl() . '/index.php');
        exit;
    }
}

function formatTimestampInManila(string $timestamp, string $format): string
{
    // Supabase stores these timezone-less timestamps in UTC.
    $utcTimestamp = new DateTimeImmutable($timestamp, new DateTimeZone('UTC'));
    return $utcTimestamp->setTimezone(new DateTimeZone('Asia/Manila'))->format($format);
}

function getLoginUrl()
{
    $depth = getScriptDepth();
    return str_repeat('../', $depth) . 'login.php';
}

function getBaseUrl()
{
    $depth = getScriptDepth();
    return rtrim(str_repeat('../', $depth), '/');
}

function getScriptDepth()
{
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_FILENAME']);
    $base   = str_replace('\\', '/', realpath(__DIR__ . '/..'));
    $rel    = ltrim(str_replace($base, '', $script), '/');
    $parts  = explode('/', $rel);
    return max(0, count($parts) - 1);
}

function logActivity($action)
{
    if (!isset($_SESSION['user_id'])) {
        return;
    }
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, log_action, created_at) VALUES (?, ?, NOW())");
        $stmt->execute([$_SESSION['user_id'], $action]);
    } catch (Throwable $exception) {
        error_log('Activity log insert failed: ' . $exception->getMessage());
    }
}

/** Return field names that changed, without copying private field values into the audit log. */
function describeActivityChanges(array $before, array $after, array $labels)
{
    $changed = [];

    foreach ($labels as $field => $label) {
        $oldValue = $before[$field] ?? null;
        $newValue = $after[$field] ?? null;

        if (is_numeric($oldValue) && is_numeric($newValue)) {
            $hasChanged = (float)$oldValue !== (float)$newValue;
        } else {
            $hasChanged = (string)$oldValue !== (string)$newValue;
        }

        if ($hasChanged) {
            $changed[] = $label;
        }
    }

    return $changed ? implode(', ', $changed) : 'no field values changed';
}

function flashSuccess($msg)
{
    $_SESSION['success'] = $msg;
}
function flashError($msg)
{
    $_SESSION['error']   = $msg;
}

function displayFlash()
{
    $html = '';
    if (!empty($_SESSION['success'])) {
        $html .= '<div class="alert alert-success alert-dismissible fade show" role="alert">'
               . htmlspecialchars($_SESSION['success'])
               . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
        unset($_SESSION['success']);
    }
    if (!empty($_SESSION['error'])) {
        $html .= '<div class="alert alert-danger alert-dismissible fade show" role="alert">'
               . htmlspecialchars($_SESSION['error'])
               . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
        unset($_SESSION['error']);
    }
    return $html;
}
