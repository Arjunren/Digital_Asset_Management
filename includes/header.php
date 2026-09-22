<?php
declare(strict_types=1);
$pageTitle = $pageTitle ?? 'Dashboard';
$user = current_user();
$unreadCount = 0;
if ($user) {
    $stmt = db()->prepare('SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0');
    $stmt->execute([$user['id']]);
    $unreadCount = (int)$stmt->fetchColumn();
}
$systemName = setting('system_name', APP_NAME);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
  <title><?= e($pageTitle) ?> · <?= e($systemName) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="<?= e(url('assets/css/app.css')) ?>" rel="stylesheet">
  <link href="<?= e(url('assets/css/brand.css')) ?>" rel="stylesheet">
</head>
<body>
<?php require __DIR__ . '/sidebar.php'; ?>
<div class="app-shell">
  <header class="topbar navbar navbar-expand bg-white sticky-top">
    <button class="btn btn-light d-lg-none me-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebar"><i class="bi bi-list fs-5"></i></button>
    <form class="top-search d-none d-md-flex" action="<?= e(url('assets.php')) ?>" method="get">
      <i class="bi bi-search"></i><input name="q" class="form-control" placeholder="Search assets…" value="<?= e($_GET['q'] ?? '') ?>">
    </form>
    <div class="ms-auto d-flex align-items-center gap-2">
      <a class="btn btn-light position-relative" href="<?= e(url('notifications.php')) ?>" aria-label="Notifications">
        <i class="bi bi-bell"></i><?php if ($unreadCount): ?><span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"><?= $unreadCount > 99 ? '99+' : $unreadCount ?></span><?php endif; ?>
      </a>
      <div class="dropdown">
        <button class="btn d-flex align-items-center gap-2 border-0" data-bs-toggle="dropdown">
          <span class="avatar"><?php if (!empty($user['profile_picture'])): ?><img src="<?= e(url('profile-image.php?id='.$user['id'])) ?>" alt=""><?php else: ?><?= e(mb_strtoupper(mb_substr($user['name'] ?? 'U', 0, 1))) ?><?php endif; ?></span>
          <span class="text-start d-none d-sm-block"><strong class="d-block small"><?= e($user['name'] ?? '') ?></strong><small class="text-muted"><?= e($user['role_name'] ?? '') ?></small></span>
        </button>
        <ul class="dropdown-menu dropdown-menu-end shadow border-0">
          <li><a class="dropdown-item" href="<?= e(url('profile.php')) ?>"><i class="bi bi-person me-2"></i>Profile</a></li>
          <li><a class="dropdown-item" href="<?= e(url('change-password.php')) ?>"><i class="bi bi-key me-2"></i>Change password</a></li>
          <li><hr class="dropdown-divider"></li>
          <li><a class="dropdown-item text-danger" href="<?= e(url('logout.php')) ?>"><i class="bi bi-box-arrow-right me-2"></i>Sign out</a></li>
        </ul>
      </div>
    </div>
  </header>
  <main class="content-wrap">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
      <div><h1 class="page-title mb-1"><?= e($pageTitle) ?></h1><?php if (!empty($pageSubtitle)): ?><p class="text-muted mb-0"><?= e($pageSubtitle) ?></p><?php endif; ?></div>
      <?= $pageActions ?? '' ?>
    </div>
    <?php foreach (pull_flashes() as $message): ?>
      <div class="alert alert-<?= e($message['type']) ?> alert-dismissible fade show" role="alert"><?= e($message['message']) ?><button class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endforeach; ?>
