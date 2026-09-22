<?php
declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));
define('APP_NAME', 'Digital Asset Management');
define('UPLOAD_ROOT', APP_ROOT . DIRECTORY_SEPARATOR . 'uploads');

$scriptDirectory = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
if (str_ends_with($scriptDirectory, '/ajax') || str_ends_with($scriptDirectory, '/admin')) {
    $scriptDirectory = dirname($scriptDirectory);
}
define('BASE_URL', rtrim($scriptDirectory, '/'));

if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        ini_set('session.cookie_secure', '1');
    }
    session_name('DAMSSESSID');
    session_start();
}

date_default_timezone_set('Asia/Taipei');

const FILE_TYPE_GROUPS = [
    'images' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'],
    'documents' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv'],
    'videos' => ['mp4', 'webm'],
    'audio' => ['mp3', 'wav'],
    'archives' => ['zip'],
];

const ALLOWED_MIME_TYPES = [
    'jpg' => ['image/jpeg'], 'jpeg' => ['image/jpeg'], 'png' => ['image/png'],
    'gif' => ['image/gif'], 'webp' => ['image/webp'], 'svg' => ['image/svg+xml', 'text/plain'],
    'pdf' => ['application/pdf'], 'doc' => ['application/msword', 'application/octet-stream'],
    'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
    'xls' => ['application/vnd.ms-excel', 'application/octet-stream'],
    'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip'],
    'ppt' => ['application/vnd.ms-powerpoint', 'application/octet-stream'],
    'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation', 'application/zip'],
    'txt' => ['text/plain'], 'csv' => ['text/plain', 'text/csv', 'application/csv'],
    'mp3' => ['audio/mpeg', 'audio/mp3'], 'wav' => ['audio/wav', 'audio/x-wav'],
    'mp4' => ['video/mp4'], 'webm' => ['video/webm'], 'zip' => ['application/zip', 'application/x-zip-compressed'],
];

