<?php
declare(strict_types=1);

const APP_NAME = 'UPTM Smart Library System';
const APP_SHORT_NAME = 'UPTM-SLS';

/**
 * Automatically works when the project folder is placed in:
 * C:\xampp\htdocs\uptm_sls
 *
 * If you rename the folder, the links still work because the base URL is
 * detected from the current script path.
 */
function base_path_url(): string
{
    $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    return $dir === '/' ? '' : rtrim($dir, '/');
}

function url(string $path = ''): string
{
    $base = base_path_url();
    $path = ltrim($path, '/');

    if ($path === '') {
        return $base !== '' ? $base . '/' : '/';
    }

    return ($base !== '' ? $base : '') . '/' . $path;
}
