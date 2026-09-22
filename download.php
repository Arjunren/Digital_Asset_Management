<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
$id=(int)($_GET['id']??0);$versionId=(int)($_GET['version']??0);$token=(string)($_GET['token']??'');$asset=get_asset($id);$user=current_user();$allowed=$asset&&$user&&asset_can_download($asset,$user);$share=null;
if($asset&&!$allowed&&$token!==''){$share=valid_share($token,true);$allowed=$share&&(int)$share['asset_id']===$id;}
if(!$asset||!$allowed){http_response_code(403);exit('Download unavailable.');}
$record=$asset;
if($versionId){$stmt=db()->prepare('SELECT * FROM asset_versions WHERE id=? AND asset_id=?');$stmt->execute([$versionId,$id]);$version=$stmt->fetch();if(!$version){http_response_code(404);exit('Version not found.');}$record=array_merge($record,$version);}
$path=resolve_stored_file($record['file_path']);if(!$path){http_response_code(404);exit('File not found.');}
db()->beginTransaction();db()->prepare('INSERT INTO downloads (asset_id,version_id,user_id,ip_address) VALUES (?,?,?,?)')->execute([$id,$versionId?:null,$user['id']??null,client_ip()]);db()->prepare('UPDATE assets SET download_count=download_count+1 WHERE id=?')->execute([$id]);if($share)db()->prepare('UPDATE share_links SET access_count=access_count+1 WHERE id=?')->execute([$share['id']]);db()->commit();audit('asset_downloaded','Downloaded asset “'.$asset['title'].'”.',$user['id']??null);
header('Content-Type: application/octet-stream');header('Content-Length: '.filesize($path));header('Content-Disposition: attachment; filename*=UTF-8\'\''.rawurlencode($record['original_filename']));header('X-Content-Type-Options: nosniff');header('Cache-Control: no-store');readfile($path);exit;
