<?php
require_once __DIR__ . '/includes/auth.php';
if (current_user()) redirect('dashboard.php');
$resetUrl = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $stmt = db()->prepare("SELECT id FROM users WHERE email=? AND status='active' AND deleted_at IS NULL");
    $stmt->execute([mb_strtolower(trim((string)($_POST['email'] ?? '')))]);
    if ($userId = $stmt->fetchColumn()) {
        $token = bin2hex(random_bytes(32));
        db()->prepare('DELETE FROM password_resets WHERE user_id=? OR expires_at<NOW()')->execute([$userId]);
        db()->prepare('INSERT INTO password_resets (user_id,token_hash,expires_at) VALUES (?,?,DATE_ADD(NOW(),INTERVAL 1 HOUR))')->execute([$userId, hash('sha256',$token)]);
        $resetUrl = url('reset-password.php?token=' . urlencode($token));
    }
    $notice = 'If an active account matched, a reset link was created. In this local development build, it appears below.';
}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Reset password</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link href="<?= e(url('assets/css/app.css')) ?>" rel="stylesheet"></head><body class="auth-page"><div class="card auth-card"><div class="card-body p-4 p-md-5"><h1 class="h4">Forgot your password?</h1><p class="text-muted">Enter your account email to create a one-hour reset link.</p><?php if (!empty($notice)): ?><div class="alert alert-info small"><?= e($notice) ?></div><?php endif; ?><?php if ($resetUrl): ?><a class="btn btn-success w-100 mb-3" href="<?= e($resetUrl) ?>">Open secure reset link</a><?php endif; ?><form method="post"><?= csrf_field() ?><label class="form-label">Email</label><input class="form-control mb-3" type="email" name="email" required><button class="btn btn-primary w-100">Create reset link</button></form><a class="btn btn-link w-100 mt-2" href="<?= e(url('login.php')) ?>">Back to sign in</a></div></div></body></html>

