<?php
declare(strict_types=1);

/**
 * Minimal environment loader for .env values.
 */
function loadEnvFile(): array {
    static $values = null;

    if ($values !== null) {
        return $values;
    }

    $values = [];
    $envPath = dirname(__DIR__, 2) . '/.env';

    if (!is_file($envPath) || !is_readable($envPath)) {
        return $values;
    }

    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return $values;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        $delimiterPos = strpos($line, '=');
        if ($delimiterPos === false) {
            continue;
        }

        $name = trim(substr($line, 0, $delimiterPos));
        $value = trim(substr($line, $delimiterPos + 1));

        if ($name === '') {
            continue;
        }

        if (strlen($value) >= 2) {
            $firstChar = $value[0];
            $lastChar = $value[strlen($value) - 1];
            if (($firstChar === '"' && $lastChar === '"') || ($firstChar === "'" && $lastChar === "'")) {
                $value = substr($value, 1, -1);
            }
        }

        $values[$name] = $value;
    }

    return $values;
}

/**
 * Read an environment value with fallback support.
 *
 * @param string $key
 * @param mixed $default
 * @return mixed
 */
function env(string $key, $default = null) {
    if (array_key_exists($key, $_ENV)) {
        return $_ENV[$key];
    }

    if (array_key_exists($key, $_SERVER)) {
        return $_SERVER[$key];
    }

    $systemValue = getenv($key);
    if ($systemValue !== false) {
        return $systemValue;
    }

    $fileValues = loadEnvFile();
    if (array_key_exists($key, $fileValues)) {
        return $fileValues[$key];
    }

    return $default;
}
