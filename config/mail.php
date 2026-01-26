<?php
// config/mail.php
// -----------------------------------------------------------------------------
// Configuració bàsica per enviar correus amb PHPMailer.
// Llegeix credencials des de config/mail.local.php (ignorat per git) si existeix.
// -----------------------------------------------------------------------------


// 1) Carrego credencials locals si existeixen (aquest fitxer està a .gitignore)
$localFile = __DIR__ . '/mail.local.php';
$local = [];

if (is_file($localFile)) {
    $loaded = require $localFile;
    if (is_array($loaded)) {
        $local = $loaded;
    }
}

// 2) Agafo valors (local si existeix, sinó placeholders)
$gmail    = $local['GMAIL']    ?? 'YOUR_EMAIL@gmail.com';
$password = $local['PASSWORD'] ?? 'YOUR_APP_PASSWORD';

// 3) Constants de configuració SMTP
define('SMTP_HOST', 'smtp.gmail.com');

// Port (587 per TLS / 465 per SSL)
define('SMTP_PORT', 587);

// Usuari i contrasenya (App Password)
define('SMTP_USERNAME', $gmail);
define('SMTP_PASSWORD', $password);

// "tls" o "ssl"
define('SMTP_ENCRYPTION', 'tls');

// Remitent (si uses Gmail, normalment ha de ser el mateix que SMTP_USERNAME)
define('MAIL_FROM_EMAIL', SMTP_USERNAME);
define('MAIL_FROM_NAME', 'Valomen.gg');

// Temps màxim del token de reset (en minuts)
define('PASSWORD_RESET_TTL_MINUTES', 60);