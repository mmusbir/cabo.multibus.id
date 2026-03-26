<?php

function perf_log_enabled(): bool
{
    $value = $_ENV['APP_PERF_LOG'] ?? $_SERVER['APP_PERF_LOG'] ?? getenv('APP_PERF_LOG');
    if ($value === false || $value === null || $value === '') {
        return false;
    }

    $normalized = strtolower(trim((string) $value));
    return in_array($normalized, ['1', 'true', 'yes', 'on'], true);
}

function perf_timer_start(): float
{
    return microtime(true);
}

function perf_finish(string $name, float $startedAt, array $context = [], float $slowMs = 150.0): float
{
    $durationMs = round((microtime(true) - $startedAt) * 1000, 2);

    if (!headers_sent()) {
        $token = preg_replace('/[^a-z0-9_\-]/i', '-', strtolower($name));
        header('Server-Timing: ' . $token . ';dur=' . $durationMs, false);
    }

    if ($durationMs >= $slowMs || perf_log_enabled()) {
        $payload = [
            'event' => $name,
            'ms' => $durationMs,
            'context' => $context,
        ];
        error_log('[perf] ' . json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    return $durationMs;
}
