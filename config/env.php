<?php
/**
 * ENVIRONMENT VARIABLE LOADER (.env Lokal & Cloud Hosting Render)
 * File: config/env.php
 * Fungsi: Memuat pasangan KEY=VALUE dari file .env di root proyek (jika ada)
 *         tanpa menimpa environment variable yang sudah diset oleh server hosting.
 */

if (!function_exists('load_env_file')) {
    function load_env_file($env_path) {
        if (!is_file($env_path) || !is_readable($env_path)) {
            return;
        }

        $lines = file($env_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $trimmed = trim($line);

            // Lewati baris kosong atau komentar (#)
            if ($trimmed === '' || strpos($trimmed, '#') === 0) {
                continue;
            }

            // Pastikan format KEY=VALUE
            $eq_pos = strpos($trimmed, '=');
            if ($eq_pos === false) {
                continue;
            }

            $key   = trim(substr($trimmed, 0, $eq_pos));
            $value = trim(substr($trimmed, $eq_pos + 1));

            if ($key === '') {
                continue;
            }

            // Hapus tanda kutip pembungkus ("..." atau '...') bila ada
            $len = strlen($value);
            if ($len >= 2) {
                $first = $value[0];
                $last  = $value[$len - 1];
                if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                    $value = substr($value, 1, -1);
                }
            }

            // Prioritaskan environment variable bawaan server hosting (Render)
            if (getenv($key) === false && !array_key_exists($key, $_ENV) && !array_key_exists($key, $_SERVER)) {
                putenv($key . '=' . $value);
                $_ENV[$key]    = $value;
                $_SERVER[$key] = $value;
            }
        }
    }
}

if (!function_exists('env_get')) {
    /**
     * Membaca environment variable secara aman dari $_ENV, $_SERVER, atau getenv().
     */
    function env_get($key, $default = null) {
        if (array_key_exists($key, $_ENV) && $_ENV[$key] !== '') {
            return $_ENV[$key];
        }
        if (array_key_exists($key, $_SERVER) && $_SERVER[$key] !== '') {
            return $_SERVER[$key];
        }
        $val = getenv($key);
        if ($val !== false && $val !== '') {
            return $val;
        }
        return $default;
    }
}

// Muat file .env di root proyek secara otomatis saat file ini di-require
load_env_file(dirname(__DIR__) . '/.env');
