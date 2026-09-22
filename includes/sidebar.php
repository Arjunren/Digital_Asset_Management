<?php
$current = basename($_SERVER['PHP_SELF'] ?? '');
$role = $user['role_name'] ?? '';
$nav = [
  ['dashboard.php','bi-grid','Dashboard',true],
  ['assets.php','bi-folder2-open','Assets',true],
  ['upload.php','bi-cloud-arrow-up','Upload Asset',in_array($role,['Administrator','Asset Manager'],true)],
  ['collections.php','bi-collection','Collections',true],
  ['favorites.php','bi-star','Favorites',true],
  ['categories.php','bi-tags','Categories',in_array($role,['Administrator','Asset Manager'],true)],
  ['users.php','bi-people','Users',$role==='Administrator'],
  ['reports.php','bi-bar-chart','Reports',$role==='Administrator'],
  ['activity-logs.php','bi-clock-history','Activity Logs',$role==='Administrator'],
  ['recycle-bin.php','bi-trash3','Recycle Bin',in_array($role,['Administrator','Asset Manager'],true)],
  ['settings.php','bi-gear','Settings',$role==='Administrator'],
];
?>
<aside class="sidebar offcanvas-lg offcanvas-start" tabindex="-1" id="sidebar">
  <div class="sidebar-brand"><span class="brand-mark"><?php if(setting('system_logo')):?><img src="<?=e(url('brand-image.php'))?>" alt=""><?php else:?><i class="bi bi-layers-fill"></i><?php endif;?></span><span><strong><?= e(setting('system_name','DAMS')) ?></strong><small>Asset workspace</small></span><button class="btn-close btn-close-white d-lg-none ms-auto" data-bs-dismiss="offcanvas"></button></div>
  <nav class="sidebar-nav">
    <span class="nav-heading">Workspace</span>
    <?php foreach ($nav as [$file,$icon,$label,$visible]): if (!$visible) continue; ?>
      <a class="nav-link <?= $current===$file ? 'active' : '' ?>" href="<?= e(url($file)) ?>"><i class="bi <?= e($icon) ?>"></i><span><?= e($label) ?></span></a>
    <?php endforeach; ?>
  </nav>
  <div class="sidebar-footer"><div class="small text-white-50 mb-2">Signed in as</div><div class="text-white text-truncate"><?= e($user['email'] ?? '') ?></div></div>
</aside>
