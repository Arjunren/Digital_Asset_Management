<?php
require_once __DIR__ . '/includes/auth.php'; require_auth();
$pageTitle='Change password'; $error='';
if ($_SERVER['REQUEST_METHOD']==='POST') { verify_csrf(); $u=current_user(); $current=(string)($_POST['current_password']??''); $new=(string)($_POST['new_password']??'');
 if(!password_verify($current,$u['password_hash'])) $error='Current password is incorrect.';
 elseif(strlen($new)<10 || !preg_match('/[A-Z]/',$new) || !preg_match('/[0-9]/',$new)) $error='Use at least 10 characters, one uppercase letter and one number.';
 elseif($new!==($_POST['confirm_password']??'')) $error='New passwords do not match.';
 else {db()->prepare('UPDATE users SET password_hash=? WHERE id=?')->execute([password_hash($new,PASSWORD_DEFAULT),$u['id']]); audit('password_change','User changed their password.'); flash('success','Your password was changed.'); redirect('profile.php');}}
require __DIR__.'/includes/header.php'; ?>
<div class="row"><div class="col-lg-6"><div class="card"><div class="card-body p-4"><?php if($error):?><div class="alert alert-danger"><?=e($error)?></div><?php endif;?><form method="post"><?=csrf_field()?><div class="mb-3"><label class="form-label">Current password</label><input class="form-control" type="password" name="current_password" required></div><div class="mb-3"><label class="form-label">New password</label><input class="form-control" type="password" name="new_password" required><div class="form-text">At least 10 characters, one uppercase letter and one number.</div></div><div class="mb-3"><label class="form-label">Confirm new password</label><input class="form-control" type="password" name="confirm_password" required></div><button class="btn btn-primary">Change password</button></form></div></div></div></div>
<?php require __DIR__.'/includes/footer.php'; ?>

