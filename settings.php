<?php
require_once __DIR__.'/includes/auth.php';
require_role('Administrator');
$u=current_user();
if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf();
    try{
        $logo=(string)setting('system_logo','');
        if(!empty($_FILES['system_logo']['name'])){
            $uploaded=safe_upload($_FILES['system_logo']);
            if(!in_array($uploaded['extension'],['jpg','jpeg','png','webp'],true)){
                delete_stored_file($uploaded['relative']);
                throw new RuntimeException('The system logo must be JPG, PNG, or WEBP.');
            }
            $oldLogo=$logo;
            $logo=$uploaded['relative'];
            if($oldLogo) delete_stored_file($oldLogo);
        }
        $requested=array_map('strtolower',array_filter(array_map('trim',explode(',',(string)$_POST['allowed_file_formats']))));
        $values=[
            'system_name'=>trim((string)$_POST['system_name']),
            'system_logo'=>$logo,
            'max_upload_size_mb'=>(string)max(1,min(2048,(int)$_POST['max_upload_size_mb'])),
            'allowed_file_formats'=>implode(',',array_values(array_intersect(array_keys(ALLOWED_MIME_TYPES),$requested))),
            'default_user_role'=>(string)(int)$_POST['default_user_role'],
            'assets_per_page'=>(string)max(6,min(100,(int)$_POST['assets_per_page'])),
            'public_sharing_enabled'=>isset($_POST['public_sharing_enabled'])?'1':'0',
            'default_share_expiry_days'=>(string)max(1,min(365,(int)$_POST['default_share_expiry_days'])),
        ];
        if($values['system_name']===''||$values['allowed_file_formats']==='') throw new RuntimeException('System name and at least one allowed format are required.');
        $stmt=db()->prepare('INSERT INTO settings(setting_key,setting_value,updated_by) VALUES(?,?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value),updated_by=VALUES(updated_by)');
        foreach($values as $key=>$value) $stmt->execute([$key,$value,$u['id']]);
        audit('settings_updated','Updated system settings.');
        flash('success','Settings updated.');
    }catch(Throwable $e){
        flash('danger',$e instanceof PDOException?'Settings could not be saved.':$e->getMessage());
    }
    redirect('settings.php');
}
$roles=db()->query('SELECT id,name FROM roles ORDER BY id')->fetchAll();
$storage=db()->query("SELECT COUNT(*) files,COALESCE(SUM(file_size),0) total,COALESCE(SUM(IF(file_extension IN ('jpg','jpeg','png','gif','webp','svg'),file_size,0)),0) images,COALESCE(SUM(IF(file_extension IN ('mp4','webm'),file_size,0)),0) videos,COALESCE(SUM(IF(file_extension IN ('mp3','wav'),file_size,0)),0) audio,COALESCE(SUM(IF(file_extension IN ('pdf','doc','docx','xls','xlsx','ppt','pptx','txt','csv'),file_size,0)),0) documents FROM assets WHERE deleted_at IS NULL")->fetch();
$pageTitle='Settings';$pageSubtitle='Configure system behavior, uploads and sharing';
require __DIR__.'/includes/header.php';
?>
<div class="row g-4"><div class="col-xl-8"><div class="card"><div class="card-body p-4">
<form method="post" enctype="multipart/form-data"><?=csrf_field()?>
<h2 class="h6 mb-3">General</h2>
<label class="form-label">System name</label><input class="form-control mb-3" name="system_name" value="<?=e(setting('system_name',APP_NAME))?>" required>
<label class="form-label">System logo</label><input class="form-control mb-4" type="file" name="system_logo" accept=".jpg,.jpeg,.png,.webp">
<h2 class="h6 mb-3">Upload policy</h2><div class="row"><div class="col-md-4 mb-3"><label class="form-label">Maximum file size (MB)</label><input class="form-control" type="number" min="1" max="2048" name="max_upload_size_mb" value="<?=e(setting('max_upload_size_mb',50))?>"></div><div class="col-md-8 mb-3"><label class="form-label">Allowed extensions</label><input class="form-control" name="allowed_file_formats" value="<?=e(setting('allowed_file_formats'))?>"><div class="form-text">Comma-separated; only built-in safe types can be enabled.</div></div></div>
<h2 class="h6 my-3">Defaults</h2><div class="row"><div class="col-md-6 mb-3"><label class="form-label">Default user role</label><select class="form-select" name="default_user_role"><?php foreach($roles as $r):?><option value="<?=$r['id']?>" <?=setting('default_user_role','3')==$r['id']?'selected':''?>><?=e($r['name'])?></option><?php endforeach;?></select></div><div class="col-md-6 mb-3"><label class="form-label">Assets per page</label><input class="form-control" type="number" min="6" max="100" name="assets_per_page" value="<?=e(setting('assets_per_page',24))?>"></div></div>
<h2 class="h6 my-3">Public sharing</h2><div class="row align-items-end"><div class="col-md-6 mb-3"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="public_sharing_enabled" id="sharing" <?=setting('public_sharing_enabled','1')==='1'?'checked':''?>><label class="form-check-label" for="sharing">Enable public share links</label></div></div><div class="col-md-6 mb-3"><label class="form-label">Default expiry (days)</label><input class="form-control" type="number" min="1" max="365" name="default_share_expiry_days" value="<?=e(setting('default_share_expiry_days',7))?>"></div></div>
<button class="btn btn-primary">Save settings</button></form></div></div></div>
<div class="col-xl-4"><div class="card"><div class="card-header bg-white py-3"><h2 class="h6 mb-0">Storage summary</h2></div><div class="card-body"><div class="stat-value mb-1"><?=e(human_file_size((int)$storage['total']))?></div><div class="text-muted small mb-4"><?=number_format((int)$storage['files'])?> files</div><?php foreach(['images'=>'Images','videos'=>'Videos','documents'=>'Documents','audio'=>'Audio'] as $key=>$label):?><div class="d-flex justify-content-between py-2 border-bottom"><span><?=e($label)?></span><strong><?=e(human_file_size((int)$storage[$key]))?></strong></div><?php endforeach;?></div></div></div></div>
<?php require __DIR__.'/includes/footer.php';?>
