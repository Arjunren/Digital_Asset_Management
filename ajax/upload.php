<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
header('Content-Type: application/json; charset=utf-8');
try {
    require_role(['Administrator','Asset Manager']);
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new RuntimeException('Method not allowed.');
    verify_csrf();
    $upload = safe_upload($_FILES['file'] ?? []);
    try {
        $title = trim((string)($_POST['title'] ?? pathinfo($upload['original'], PATHINFO_FILENAME)));
        if ($title === '') $title = pathinfo($upload['original'], PATHINFO_FILENAME);
        $categoryId = filter_var($_POST['category_id'] ?? null, FILTER_VALIDATE_INT) ?: null;
        $visibility = in_array($_POST['visibility'] ?? '', ['private','organization','public'], true) ? $_POST['visibility'] : 'organization';
        $width=$height=null;
        if ($upload['group']==='images' && $upload['extension']!=='svg') { $info=@getimagesize($upload['destination']); if($info){$width=$info[0];$height=$info[1];} }
        $assetCode='AST-'.strtoupper(bin2hex(random_bytes(5)));
        db()->beginTransaction();
        $stmt=db()->prepare('INSERT INTO assets (asset_code,original_filename,stored_filename,title,description,file_extension,mime_type,file_size,file_path,width,height,category_id,uploaded_by,visibility) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $stmt->execute([$assetCode,$upload['original'],$upload['stored'],mb_substr($title,0,190),trim((string)($_POST['description']??'')),$upload['extension'],$upload['mime'],$upload['size'],$upload['relative'],$width,$height,$categoryId,current_user()['id'],$visibility]);
        $assetId=(int)db()->lastInsertId();
        db()->prepare('INSERT INTO asset_versions (asset_id,version_number,original_filename,stored_filename,file_path,file_extension,mime_type,file_size,uploaded_by,version_notes) VALUES (?,1,?,?,?,?,?,?,?,?)')->execute([$assetId,$upload['original'],$upload['stored'],$upload['relative'],$upload['extension'],$upload['mime'],$upload['size'],current_user()['id'],'Initial upload']);
        sync_asset_tags($assetId,(string)($_POST['tags']??''));
        db()->commit();
        audit('asset_uploaded','Uploaded asset “'.$title.'” ('.$assetCode.').');
        notification((int)current_user()['id'],'Upload complete','“'.$title.'” is now in the asset library.','asset-details.php?id='.$assetId);
        echo json_encode(['success'=>true,'asset_id'=>$assetId,'message'=>'Upload complete.']);
    } catch (Throwable $e) { if(db()->inTransaction())db()->rollBack(); delete_stored_file($upload['relative']); throw $e; }
} catch (Throwable $e) {
    http_response_code($e instanceof PDOException ? 500 : 422);
    echo json_encode(['success'=>false,'message'=>$e instanceof PDOException?'The database could not save this asset.':$e->getMessage()]);
}

