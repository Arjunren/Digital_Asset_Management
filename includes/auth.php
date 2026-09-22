<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';

function current_user(): ?array
{
    static $user = false;
    if ($user === false) {
        if (empty($_SESSION['user_id'])) return null;
        $stmt = db()->prepare('SELECT u.*, r.name role_name FROM users u JOIN roles r ON r.id=u.role_id WHERE u.id=? AND u.deleted_at IS NULL');
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch() ?: null;
        if (!$user || $user['status'] !== 'active') {
            unset($_SESSION['user_id']);
            return null;
        }
    }
    return is_array($user) ? $user : null;
}

function require_auth(): void
{
    if (!current_user()) {
        $_SESSION['intended_url'] = $_SERVER['REQUEST_URI'] ?? url('dashboard.php');
        flash('warning', 'Please sign in to continue.');
        redirect('login.php');
    }
}

function require_role(string|array $roles): void
{
    require_auth();
    $roles = (array)$roles;
    if (!in_array(current_user()['role_name'], $roles, true)) {
        http_response_code(403);
        $pageTitle = 'Access denied';
        require __DIR__ . '/header.php';
        echo '<div class="alert alert-danger"><h4>Access denied</h4><p>You do not have permission to access this page.</p></div>';
        require __DIR__ . '/footer.php';
        exit;
    }
}

function attempt_login(string $email, string $password): bool
{
    $stmt = db()->prepare('SELECT u.*, r.name role_name FROM users u JOIN roles r ON r.id=u.role_id WHERE u.email=? AND u.deleted_at IS NULL LIMIT 1');
    $stmt->execute([mb_strtolower(trim($email))]);
    $user = $stmt->fetch();
    if (!$user || $user['status'] !== 'active') {
        if ($user) db()->prepare('UPDATE users SET failed_login_attempts=failed_login_attempts+1 WHERE id=?')->execute([$user['id']]);
        return false;
    }
    if ($user['locked_until'] && strtotime($user['locked_until']) > time()) return false;
    if (!password_verify($password, $user['password_hash'])) {
        db()->prepare('UPDATE users SET failed_login_attempts=failed_login_attempts+1, locked_until=IF(failed_login_attempts>=4, DATE_ADD(NOW(), INTERVAL 15 MINUTE), locked_until) WHERE id=?')->execute([$user['id']]);
        return false;
    }
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int)$user['id'];
    db()->prepare('UPDATE users SET last_login=NOW(), failed_login_attempts=0, locked_until=NULL WHERE id=?')->execute([$user['id']]);
    audit('login', 'User signed in.', (int)$user['id']);
    return true;
}

function logout_user(): void
{
    if (!empty($_SESSION['user_id'])) audit('logout', 'User signed out.', (int)$_SESSION['user_id']);
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

