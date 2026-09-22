<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
$id=(int)($_GET['id']??0);$asset=get_asset($id);$allowed=false;
if($asset && current_user() && asset_can_view($asset))$allowed=true;
if($asset && !$allowed && !empty($_GET['token'])){$share=valid_share((string)$_GET['token']);$allowed=$share && (int)$share['asset_id']===$id;}
if(!$asset||!$allowed){http_response_code(403);exit('Preview unavailable.');}
$path=resolve_stored_file($asset['file_path']);if(!$path){http_response_code(404);exit('File not found.');}
header('Content-Type: '.$asset['mime_type']);header('Content-Length: '.filesize($path));header('Content-Disposition: inline; filename*=UTF-8\'\''.rawurlencode($asset['original_filename']));header('X-Content-Type-Options: nosniff');header('Cache-Control: private, max-age=300');
if($asset['file_extension']==='svg')header("Content-Security-Policy: sandbox; default-src 'none'; style-src 'unsafe-inline'");
readfile($path);exit;

