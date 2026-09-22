<?php
require_once __DIR__.'/includes/auth.php';require_auth();$id=(int)($_GET['id']??current_user()['id']);
$stmt=db()->prepare('SELECT profile_picture FROM users WHERE id=? AND deleted_at IS NULL');$stmt->execute([$id]);$relative=$stmt->fetchColumn();$path=$relative?resolve_stored_file($relative):null;if(!$path){http_response_code(404);exit;}$finfo=new finfo(FILEINFO_MIME_TYPE);$mime=$finfo->file($path);if(!in_array($mime,['image/jpeg','image/png','image/webp'],true)){http_response_code(415);exit;}header('Content-Type: '.$mime);header('Content-Length: '.filesize($path));header('Cache-Control: private,max-age=600');header('X-Content-Type-Options: nosniff');readfile($path);

