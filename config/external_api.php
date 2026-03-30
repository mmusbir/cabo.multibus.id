<?php

if (!function_exists('external_api_ensure_table')) {
    function external_api_ensure_table(PDO $conn): void
    {
        static $ready = false;
        if ($ready) {
            return;
        }

        $conn->exec("
            CREATE TABLE IF NOT EXISTS external_api_keys (
                id BIGSERIAL PRIMARY KEY,
                name VARCHAR(120) NOT NULL,
                api_key_hash VARCHAR(255) NOT NULL UNIQUE,
                api_key_prefix VARCHAR(40) NOT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'active',
                notes TEXT NULL,
                created_by_user_id INT NULL,
                created_by_username VARCHAR(150) NULL,
                last_used_at TIMESTAMP NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            )
        ");
        $conn->exec("CREATE INDEX IF NOT EXISTS idx_external_api_keys_status ON external_api_keys (status)");
        $conn->exec("CREATE INDEX IF NOT EXISTS idx_external_api_keys_created_at ON external_api_keys (created_at DESC)");
        $ready = true;
    }
}

if (!function_exists('external_api_generate_key')) {
    function external_api_generate_key(): string
    {
        return 'cab_live_' . bin2hex(random_bytes(20));
    }
}

if (!function_exists('external_api_hash_key')) {
    function external_api_hash_key(string $plainKey): string
    {
        return hash('sha256', trim($plainKey));
    }
}

if (!function_exists('external_api_key_prefix')) {
    function external_api_key_prefix(string $plainKey): string
    {
        $plainKey = trim($plainKey);
        return substr($plainKey, 0, min(strlen($plainKey), 18));
    }
}

if (!function_exists('external_api_extract_key')) {
    function external_api_extract_key(): string
    {
        $candidates = [];

        if (!empty($_SERVER['HTTP_X_API_KEY'])) {
            $candidates[] = (string) $_SERVER['HTTP_X_API_KEY'];
        }

        if (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
            $authHeader = trim((string) $_SERVER['HTTP_AUTHORIZATION']);
            if (stripos($authHeader, 'Bearer ') === 0) {
                $candidates[] = trim(substr($authHeader, 7));
            }
        }

        if (!empty($_REQUEST['api_key'])) {
            $candidates[] = (string) $_REQUEST['api_key'];
        }

        foreach ($candidates as $candidate) {
            $candidate = trim($candidate);
            if ($candidate !== '') {
                return $candidate;
            }
        }

        return '';
    }
}

if (!function_exists('external_api_authenticate')) {
    function external_api_authenticate(PDO $conn): ?array
    {
        external_api_ensure_table($conn);

        $plainKey = external_api_extract_key();
        if ($plainKey === '') {
            return null;
        }

        $stmt = $conn->prepare("
            SELECT id, name, status, created_by_user_id, created_by_username
            FROM external_api_keys
            WHERE api_key_hash = ?
            LIMIT 1
        ");
        $stmt->execute([external_api_hash_key($plainKey)]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row || strtolower((string) ($row['status'] ?? '')) !== 'active') {
            return null;
        }

        $conn->prepare("UPDATE external_api_keys SET last_used_at = NOW(), updated_at = NOW() WHERE id = ?")
            ->execute([(int) $row['id']]);

        $username = trim((string) ($row['created_by_username'] ?? ''));
        if ($username === '') {
            $username = 'External API';
        }

        return [
            'sub' => (int) ($row['created_by_user_id'] ?? 0),
            'user' => $username,
            'fullname' => $username,
            'source' => 'external_api',
            'api_key_id' => (int) $row['id'],
            'api_key_name' => trim((string) ($row['name'] ?? 'External API')),
        ];
    }
}
