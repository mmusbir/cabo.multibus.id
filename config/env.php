<?php
// config/env.php - lightweight .env loader for local development

if (!function_exists('loadProjectEnv')) {
    function loadProjectEnv(): void
    {
        static $loaded = false;
        if ($loaded) {
            return;
        }
        $loaded = true;

        $root = dirname(__DIR__);
        $envFiles = [
            $root . DIRECTORY_SEPARATOR . '.env',
            $root . DIRECTORY_SEPARATOR . '.env.local',
        ];

        foreach ($envFiles as $envFile) {
            if (!is_file($envFile) || !is_readable($envFile)) {
                continue;
            }

            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if ($lines === false) {
                continue;
            }

            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#')) {
                    continue;
                }

                if (!str_contains($line, '=')) {
                    continue;
                }

                [$name, $value] = explode('=', $line, 2);
                $name = trim($name);
                $value = trim($value);

                if ($name === '') {
                    continue;
                }

                if (
                    (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                    (str_starts_with($value, "'") && str_ends_with($value, "'"))
                ) {
                    $value = substr($value, 1, -1);
                }

                if (getenv($name) === false) {
                    putenv($name . '=' . $value);
                }
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }
}

loadProjectEnv();
