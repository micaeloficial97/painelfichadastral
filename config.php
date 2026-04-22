<?php
declare(strict_types=1);

function load_env_file(string $path): void
{
  if (!is_file($path) || !is_readable($path)) {
    return;
  }

  foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    $line = trim($line);

    if ($line === '' || strpos($line, '#') === 0) {
      continue;
    }

    [$key, $value] = array_pad(explode('=', $line, 2), 2, '');
    $key = trim($key);
    $value = trim($value);

    if ($key === '') {
      continue;
    }

    $value = trim($value, "\"'");

    if (getenv($key) === false) {
      putenv($key . '=' . $value);
      $_ENV[$key] = $value;
    }
  }
}

function env_value(string $key, ?string $default = null): ?string
{
  $value = getenv($key);

  if ($value === false) {
    return $default;
  }

  return $value;
}

load_env_file(__DIR__ . '/.env');
