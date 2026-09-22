<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

function e(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = ''): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

function redirect(string $path): never
{
    header('Location: ' . (str_starts_with($path, 'http') ? $path : url($path)));
    exit;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $token = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
        http_response_code(419);
        exit('Invalid or expired CSRF token. Refresh the page and try again.');
    }
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function pull_flashes(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

function human_file_size(int|float $bytes, int $precision = 1): string
{
    if ($bytes <= 0) return '0 B';
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $power = min((int)floor(log($bytes, 1024)), count($units) - 1);
    return round($bytes / (1024 ** $power), $precision) . ' ' . $units[$power];
}

function setting(string $key, mixed $default = null): mixed
{
    static $settings = null;
    if ($settings === null) {
        try {
            $settings = db()->query('SELECT setting_key, setting_value FROM settings')->fetchAll(PDO::FETCH_KEY_PAIR);
        } catch (Throwable) {
            $settings = [];
        }
    }
    return $settings[$key] ?? $default;
}

function client_ip(): string
{
    return substr($_SERVER['REMOTE_ADDR'] ?? 'unknown', 0, 45);
}

function audit(string $action, string $description, ?int $userId = null): void
{
    try {
        $stmt = db()->prepare('INSERT INTO activity_logs (user_id, action, description, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$userId ?? ($_SESSION['user_id'] ?? null), $action, $description, client_ip(), substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255)]);
    } catch (Throwable) {
        // Logging must not break the primary action.
    }
}

function notification(int $userId, string $title, string $message, ?string $link = null): void
{
    $stmt = db()->prepare('INSERT INTO notifications (user_id, title, message, link) VALUES (?, ?, ?, ?)');
    $stmt->execute([$userId, $title, $message, $link]);
}

function file_group(string $extension): ?string
{
    foreach (FILE_TYPE_GROUPS as $group => $extensions) {
        if (in_array(strtolower($extension), $extensions, true)) return $group;
    }
    return null;
}

function allowed_extensions(): array
{
    $configured = array_filter(array_map('trim', explode(',', (string)setting('allowed_file_formats', implode(',', array_keys(ALLOWED_MIME_TYPES))))));
    return array_values(array_intersect(array_keys(ALLOWED_MIME_TYPES), array_map('strtolower', $configured)));
}

function max_upload_bytes(): int
{
    return max(1, (int)setting('max_upload_size_mb', '50')) * 1024 * 1024;
}

function safe_upload(array $file): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('The upload failed with error code ' . ($file['error'] ?? 'unknown') . '.');
    }
    $original = basename((string)$file['name']);
    if ($original === '' || str_contains($original, "\0")) throw new RuntimeException('Invalid filename.');
    if (substr_count($original, '.') > 1) {
        $parts = explode('.', strtolower($original));
        $dangerous = ['php', 'php3', 'php4', 'php5', 'phtml', 'exe', 'bat', 'cmd', 'sh', 'js', 'htaccess'];
        foreach (array_slice($parts, 0, -1) as $part) {
            if (in_array($part, $dangerous, true)) throw new RuntimeException('Dangerous double-extension filename rejected.');
        }
    }
    $extension = strtolower(pathinfo($original, PATHINFO_EXTENSION));
    if (!in_array($extension, allowed_extensions(), true) || !isset(ALLOWED_MIME_TYPES[$extension])) {
        throw new RuntimeException('This file extension is not allowed.');
    }
    $size = (int)($file['size'] ?? 0);
    if ($size < 1 || $size > max_upload_bytes()) throw new RuntimeException('The file is empty or exceeds the upload limit.');
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file((string)$file['tmp_name']) ?: 'application/octet-stream';
    if (!in_array($mime, ALLOWED_MIME_TYPES[$extension], true)) throw new RuntimeException('The detected file type does not match its extension.');
    $group = file_group($extension);
    if ($group === null) throw new RuntimeException('Unsupported file type.');
    $stored = date('Ymd_His') . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
    $relative = 'uploads/' . $group . '/' . $stored;
    $destination = APP_ROOT . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
    if (!is_dir(dirname($destination)) && !mkdir(dirname($destination), 0755, true)) throw new RuntimeException('Upload storage is unavailable.');
    if (!move_uploaded_file((string)$file['tmp_name'], $destination)) throw new RuntimeException('Could not store the uploaded file.');
    return compact('original', 'stored', 'relative', 'extension', 'mime', 'size', 'group', 'destination');
}

function delete_stored_file(string $relativePath): void
{
    $realRoot = realpath(UPLOAD_ROOT);
    $realFile = realpath(APP_ROOT . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath));
    if ($realRoot && $realFile && str_starts_with($realFile, $realRoot . DIRECTORY_SEPARATOR) && is_file($realFile)) {
        unlink($realFile);
    }
}

function resolve_stored_file(string $relativePath): ?string
{
    $root = realpath(UPLOAD_ROOT);
    $path = realpath(APP_ROOT . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath));
    return ($root && $path && str_starts_with($path, $root . DIRECTORY_SEPARATOR) && is_file($path)) ? $path : null;
}

function valid_share(string $token, bool $requireDownload = false): ?array
{
    if (!preg_match('/^[a-f0-9]{64}$/', $token)) return null;
    $stmt = db()->prepare('SELECT s.*,a.deleted_at FROM share_links s JOIN assets a ON a.id=s.asset_id WHERE s.token=? AND s.is_active=1 AND (s.expires_at IS NULL OR s.expires_at>NOW())');
    $stmt->execute([$token]);
    $share = $stmt->fetch() ?: null;
    if (!$share || $share['deleted_at'] || ($requireDownload && !(bool)$share['download_allowed'])) return null;
    if ($share['password_hash'] && empty($_SESSION['share_access'][$token])) return null;
    return $share;
}

function asset_icon(string $extension): string
{
    return match (file_group($extension)) {
        'images' => 'bi-file-image', 'videos' => 'bi-file-play', 'audio' => 'bi-file-music',
        'archives' => 'bi-file-zip', default => $extension === 'pdf' ? 'bi-file-pdf' : 'bi-file-earmark-text',
    };
}

function asset_can_manage(array $asset, ?array $user = null): bool
{
    $user ??= current_user();
    return $user && ($user['role_name'] === 'Administrator' || ($user['role_name'] === 'Asset Manager' && (int)$asset['uploaded_by'] === (int)$user['id']));
}

function asset_can_view(array $asset, ?array $user = null): bool
{
    $user ??= current_user();
    if (!$user) return false;
    if ($asset['visibility'] === 'public' || $user['role_name'] === 'Administrator' || (int)$asset['uploaded_by'] === (int)$user['id'] || $asset['visibility'] === 'organization') return true;
    $stmt = db()->prepare("SELECT 1 FROM collection_assets ca JOIN collections c ON c.id=ca.collection_id LEFT JOIN collection_shares cs ON cs.collection_id=c.id AND cs.user_id=? WHERE ca.asset_id=? AND (c.owner_id=? OR c.visibility='organization' OR cs.user_id IS NOT NULL) LIMIT 1");
    $stmt->execute([$user['id'], $asset['id'], $user['id']]);
    return (bool)$stmt->fetchColumn();
}

function asset_can_download(array $asset, ?array $user = null): bool
{
    $user ??= current_user();
    if (!$user) return false;
    if ($asset['visibility'] !== 'private' || $user['role_name'] === 'Administrator' || (int)$asset['uploaded_by'] === (int)$user['id']) return true;
    $stmt = db()->prepare("SELECT 1 FROM collection_assets ca JOIN collections c ON c.id=ca.collection_id LEFT JOIN collection_shares cs ON cs.collection_id=c.id AND cs.user_id=? WHERE ca.asset_id=? AND (c.owner_id=? OR c.visibility='organization' OR cs.permission='download') LIMIT 1");
    $stmt->execute([$user['id'], $asset['id'], $user['id']]);
    return (bool)$stmt->fetchColumn();
}

function get_asset(int $id, bool $includeDeleted = false): ?array
{
    $sql = 'SELECT a.*, c.name category_name, u.name uploader_name FROM assets a LEFT JOIN categories c ON c.id=a.category_id JOIN users u ON u.id=a.uploaded_by WHERE a.id=?';
    if (!$includeDeleted) $sql .= ' AND a.deleted_at IS NULL';
    $stmt = db()->prepare($sql);
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function tags_for_asset(int $assetId): array
{
    $stmt = db()->prepare('SELECT t.* FROM tags t JOIN asset_tags at ON at.tag_id=t.id WHERE at.asset_id=? ORDER BY t.name');
    $stmt->execute([$assetId]);
    return $stmt->fetchAll();
}

function sync_asset_tags(int $assetId, string $tagList): void
{
    $names = array_unique(array_filter(array_map(fn($v) => trim(mb_strtolower($v)), explode(',', $tagList))));
    db()->prepare('DELETE FROM asset_tags WHERE asset_id=?')->execute([$assetId]);
    foreach (array_slice($names, 0, 20) as $name) {
        $stmt = db()->prepare('INSERT INTO tags (name, created_by) VALUES (?, ?) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)');
        $stmt->execute([mb_substr($name, 0, 80), $_SESSION['user_id']]);
        $tagId = (int)db()->lastInsertId();
        db()->prepare('INSERT IGNORE INTO asset_tags (asset_id, tag_id) VALUES (?, ?)')->execute([$assetId, $tagId]);
    }
}

function page_param(string $key, string $default = ''): string
{
    return trim((string)($_GET[$key] ?? $default));
}
