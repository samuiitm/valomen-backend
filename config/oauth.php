<?php
// config/oauth.php
// -----------------------------------------------------------------------------
// Config OAuth2 (Google / GitHub)
// -----------------------------------------------------------------------------

$localPath = __DIR__ . '/oauth.local.php';

$cfg = [
    'GOOGLE_CLIENT_ID'     => '',
    'GOOGLE_CLIENT_SECRET' => '',
    'GITHUB_CLIENT_ID'     => '',
    'GITHUB_CLIENT_SECRET' => '',
];

if (is_file($localPath)) {
    $local = require $localPath;
    if (is_array($local)) {
        $cfg = array_merge($cfg, $local);
    }
}

define('GOOGLE_CLIENT_ID',     $cfg['GOOGLE_CLIENT_ID']);
define('GOOGLE_CLIENT_SECRET', $cfg['GOOGLE_CLIENT_SECRET']);

define('GITHUB_CLIENT_ID',     $cfg['GITHUB_CLIENT_ID']);
define('GITHUB_CLIENT_SECRET', $cfg['GITHUB_CLIENT_SECRET']);