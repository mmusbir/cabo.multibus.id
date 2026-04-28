<?php
// Simple file-based cache helper
// Usage: cache_get($key), cache_set($key, $value, $ttlSeconds)

function cache_dir_path(): string
{
    $dir = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'cache';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    return $dir;
}

function cache_key_to_file(string $key): string
{
    $hash = hash('sha256', $key);
    return rtrim(cache_dir_path(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $hash . '.cache';
}

function cache_set(string $key, $value, int $ttl = 300): bool
{
    $file = cache_key_to_file($key);
    $payload = [
        'key' => $key,
        'expires_at' => time() + $ttl,
        'data' => $value,
    ];
    $tmp = $file . '.' . uniqid('', true) . '.tmp';
    $data = json_encode($payload, JSON_UNESCAPED_UNICODE);
    if (@file_put_contents($tmp, $data) === false) {
        return false;
    }
    @rename($tmp, $file);
    return true;
}

function cache_get(string $key)
{
    $file = cache_key_to_file($key);
    if (!is_file($file)) return null;
    $raw = @file_get_contents($file);
    if ($raw === false) return null;
    $payload = json_decode($raw, true);
    if (!is_array($payload) || !isset($payload['expires_at'])) return null;
    if (time() > (int) $payload['expires_at']) {
        @unlink($file);
        return null;
    }
    return $payload['data'];
}

function cache_delete(string $key): bool
{
    $file = cache_key_to_file($key);
    if (is_file($file)) {
        $ok = @unlink($file);
        cache_log_invalidation('delete', $key, ['ok' => $ok]);
        return $ok;
    }
    return false;
}

function cache_delete_prefix(string $prefix): int
{
    $dir = cache_dir_path();
    $files = glob($dir . DIRECTORY_SEPARATOR . '*.cache');
    $deleted = 0;
    if (!is_array($files)) return 0;
    foreach ($files as $f) {
        $raw = @file_get_contents($f);
        if ($raw === false) continue;
        $payload = json_decode($raw, true);
        if (!is_array($payload) || !isset($payload['key'])) continue;
        if (strpos($payload['key'], $prefix) === 0) {
            if (@unlink($f)) {
                $deleted++;
                cache_log_invalidation('delete_prefix', $payload['key'], ['prefix' => $prefix]);
            }
        }
    }
    return $deleted;
}

function cache_log_invalidation(string $action, string $key, array $meta = []): void
{
    $dir = cache_dir_path();
    $logFile = $dir . DIRECTORY_SEPARATOR . 'invalidate.log';
    $entry = [
        'ts' => date('c'),
        'action' => $action,
        'key' => $key,
        'meta' => $meta,
        'pid' => getmypid(),
    ];
    @file_put_contents($logFile, json_encode($entry, JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND | LOCK_EX);
}

/**
 * Set response headers suitable for client and edge/CDN caching.
 * - $ttl: client max-age
 * - $smaxage: surrogate max-age for CDN (defaults to $ttl)
 * - $surrogateKey: optional key to set in `Surrogate-Key` header for CDN tagging
 */
function set_edge_cache_headers(int $ttl = 300, ?int $smaxage = null, ?string $surrogateKey = null): void
{
    $smax = $smaxage ?? $ttl;
    header('Cache-Control: public, max-age=' . (int) $ttl);
    // In production, emit surrogate headers to allow CDNs to cache and be purged by key
    if (getenv('APP_ENV') === 'production') {
        header('Surrogate-Control: max-age=' . (int) $smax);
        if ($surrogateKey !== null) {
            header('Surrogate-Key: ' . $surrogateKey);
        }
    }
}

function cache_clear_all(): void
{
    $dir = cache_dir_path();
    $files = glob($dir . DIRECTORY_SEPARATOR . '*.cache');
    if (!is_array($files)) return;
    foreach ($files as $f) {
        @unlink($f);
    }
}

?>
