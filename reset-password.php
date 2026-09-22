<?php
require_once __DIR__ . '/includes/auth.php';
$token = (string)($_GET['token'] ?? $_POST['token'] ?? '');
$stmt = db()->prepare('SELECT pr.*,u.email FROM password_resets pr JOIN users u ON u.id=pr.user_id WHERE pr.token_hash=? AND pr.used_at IS NULL AND pr.expires_at>NOW()');
$stmt->execute([hash('sha256',$token)]);
$reset = $stmt->fetch();
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $reset) {
    verify_csrf();
    $password = (string)($_POST['password'] ?? '');
    if (strlen($password) < 10 || !preg_match('/[A-Z]/',$password) || !preg_match('/[0-9]/',$password)) $error='Use at least 10 characters, one uppercase letter and one number.';
    elseif ($password !== ($_POST['confirm_password'] ?? '')) $error='Passwords do not match.';
    else {
        db()->beginTransaction();
        db()->prepare('UPDATE users SET password_hash=? WHERE id=?')->execute([password_hash($password,PASSWORD_DEFAULT),$reset['user_id']]);
        db()->prepare('UPDATE password_resets SET used_at=NOW() WHERE id=?')->execute([$reset['id']]);
        db()->commit();
        audit('password_reset','Password reset completed.',(int)$reset['user_id']);
        flash('success','Password changed. You can now sign in.'); redirect('login.php');
    }
}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Choose new password</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link href="<?= e(url('assets/css/app.css')) ?>" rel="stylesheet"></head><body class="auth-page"><div class="card auth-card"><div class="card-body p-4 p-md-5"><h1 class="h4">Choose a new password</h1><?php if (!$reset): ?><div class="alert alert-danger">This reset link is invalid, expired, or already used.</div><a href="<?= e(url('forgot-password.php')) ?>" class="btn btn-primary">Request another link</a><?php else: ?><?php if($error):?><div class="alert alert-danger"><?=e($error)?></div><?php endif;?><p class="text-muted small"><?= e($reset['email']) ?></p><form method="post"><?= csrf_field() ?><input type="hidden" name="token" value="<?= e($token) ?>"><label class="form-label">New password</label><input class="form-control mb-3" type="password" name="password" required><label class="form-label">Confirm password</label><input class="form-control mb-3" type="password" name="confirm_password" required><button class="btn btn-primary w-100">Save new password</button></form><?php endif; ?></div></div></body></html>

