<?php

function base_path(): string
{
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    $dir = str_replace('\\', '/', dirname($script));
    if ($dir === '/' || $dir === '\\') return '';
    return rtrim($dir, '/');
}

function url(string $path = ''): string
{
    $base = base_path();
    $path = '/' . ltrim($path, '/');
    return $base . $path;
}

function redirect_to(string $path): void
{
    header('Location: ' . url($path));
    exit;
}