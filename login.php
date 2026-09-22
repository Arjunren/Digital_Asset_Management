<?php
require_once __DIR__ . '/includes/auth.php';
if (current_user()) redirect('dashboard.php');
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    if (attempt_login((string)($_POST['email'] ?? ''), (string)($_POST['password'] ?? ''))) {
        $target = $_SESSION['intended_url'] ?? url('dashboard.php');
        unset($_SESSION['intended_url']);
        header('Location: ' . $target); exit;
    }
    $error = 'Sign-in failed. Check your credentials or account status.';
    usleep(300000);
}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Sign in · Digital Asset Management</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet"><link href="<?= e(url('assets/css/app.css')) ?>" rel="stylesheet"></head>
<body class="auth-page"><div class="card auth-card"><div class="card-body p-4 p-md-5"><div class="d-flex align-items-center gap-3 mb-4"><span class="brand-mark text-white"><i class="bi bi-layers-fill"></i></span><div><h1 class="h4 mb-0">Welcome back</h1><small class="text-muted">Sign in to your asset workspace</small></div></div>
<?php foreach (pull_flashes() as $message): ?><div class="alert alert-<?= e($message['type']) ?>"><?= e($message['message']) ?></div><?php endforeach; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
<form method="post"><?= csrf_field() ?><div class="mb-3"><label class="form-label">Email address</label><input class="form-control" type="email" name="email" required autofocus autocomplete="username" value="<?= e($_POST['email'] ?? '') ?>"></div><div class="mb-3"><div class="d-flex justify-content-between"><label class="form-label">Password</label><a href="<?= e(url('forgot-password.php')) ?>" class="small">Forgot password?</a></div><input class="form-control" type="password" name="password" required autocomplete="current-password"></div><button class="btn btn-primary w-100 py-2">Sign in <i class="bi bi-arrow-right ms-1"></i></button></form>
<div class="alert alert-light border mt-4 mb-0 small"><strong>Development login</strong><br>admin@dam.local / Admin123!</div></div></div></body></html>

