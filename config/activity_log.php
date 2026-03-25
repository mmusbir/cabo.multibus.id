<?php

if (!function_exists('activity_log_ensure_table')) {
    function activity_log_ensure_table(PDO $conn): void
    {
        static $ready = false;
        if ($ready) {
            return;
        }

        $conn->exec("
            CREATE TABLE IF NOT EXISTS activity_logs (
                id BIGSERIAL PRIMARY KEY,
                category VARCHAR(50) NOT NULL,
                entity_type VARCHAR(80) NOT NULL,
                entity_id VARCHAR(120) NULL,
                action VARCHAR(80) NOT NULL,
                summary TEXT NOT NULL,
                details TEXT NULL,
                actor VARCHAR(150) NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            )
        ");
        $conn->exec("CREATE INDEX IF NOT EXISTS idx_activity_logs_created_at ON activity_logs (created_at DESC)");
        $conn->exec("CREATE INDEX IF NOT EXISTS idx_activity_logs_category ON activity_logs (category)");
        $ready = true;
    }
}

if (!function_exists('activity_log_current_actor')) {
    function activity_log_current_actor(?array $auth = null): string
    {
        $auth = $auth ?? (function_exists('getAuthenticatedUser') ? (getAuthenticatedUser() ?: []) : []);
        $candidates = [
            $auth['fullname'] ?? null,
            $auth['user'] ?? null,
            $_SESSION['admin_user'] ?? null,
            $_SESSION['username'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            $value = trim((string) $candidate);
            if ($value !== '') {
                return $value;
            }
        }

        return 'system';
    }
}

if (!function_exists('activity_log_write')) {
    function activity_log_write(
        PDO $conn,
        string $category,
        string $entityType,
        $entityId,
        string $action,
        string $summary,
        string $details = '',
        ?string $actor = null
    ): bool {
        try {
            activity_log_ensure_table($conn);
            $stmt = $conn->prepare("
                INSERT INTO activity_logs (category, entity_type, entity_id, action, summary, details, actor)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            return $stmt->execute([
                trim($category),
                trim($entityType),
                $entityId === null ? null : (string) $entityId,
                trim($action),
                trim($summary),
                trim($details),
                trim((string) ($actor ?: activity_log_current_actor())),
            ]);
        } catch (Throwable $e) {
            return false;
        }
    }
}

if (!function_exists('activity_log_relative_time')) {
    function activity_log_relative_time($datetime): string
    {
        if (empty($datetime)) {
            return '-';
        }

        $timestamp = strtotime((string) $datetime);
        if (!$timestamp) {
            return '-';
        }

        $diff = time() - $timestamp;
        if ($diff < 60) {
            return 'Baru saja';
        }
        if ($diff < 3600) {
            return floor($diff / 60) . ' menit lalu';
        }
        if ($diff < 86400) {
            return floor($diff / 3600) . ' jam lalu';
        }
        if ($diff < 172800) {
            return 'Kemarin';
        }
        if ($diff < 2592000) {
            return floor($diff / 86400) . ' hari lalu';
        }

        return date('d M Y - H:i', $timestamp) . ' WITA';
    }
}

if (!function_exists('activity_log_tone')) {
    function activity_log_tone(string $category, string $action): string
    {
        $category = strtolower(trim($category));
        $action = strtolower(trim($action));

        if (in_array($action, ['delete', 'cancel', 'mark_canceled'], true)) {
            return 'danger';
        }
        if (in_array($action, ['mark_paid', 'mark_all_paid', 'bop_done', 'activate', 'restore'], true)) {
            return 'success';
        }
        if ($category === 'settings') {
            return 'info';
        }
        if ($category === 'charter') {
            return 'primary';
        }
        if ($category === 'luggage') {
            return 'warning';
        }

        return 'info';
    }
}
