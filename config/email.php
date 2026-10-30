<?php

// The private file contains the Gmail App Password and must not be shared.
$localSettingsFile = __DIR__ . '/email.local.php';
$localSettings = file_exists($localSettingsFile) ? require $localSettingsFile : [];

if (!is_array($localSettings)) {
    throw new RuntimeException('config/email.local.php must return an array of email settings.');
}

$getSetting = static function (
    string $environmentName,
    string $settingName,
    $default = ''
) use ($localSettings) {
    $environmentValue = getenv($environmentName);
    if ($environmentValue !== false && $environmentValue !== '') {
        return $environmentValue;
    }

    return $localSettings[$settingName] ?? $default;
};

// Use email.local.php by default; environment variables can override each setting.
$mode = $getSetting('EMAIL_MODE', 'mode', 'smtp');
if (!in_array($mode, ['smtp', 'development'], true)) {
    throw new RuntimeException('Email mode must be smtp or development.');
}

$username = $getSetting('SMTP_USERNAME', 'username');

return [
    'mode' => $mode,
    'host' => $getSetting('SMTP_HOST', 'host', 'smtp.gmail.com'),
    'port' => (int) $getSetting('SMTP_PORT', 'port', 587),
    'username' => $username,
    'password' => $getSetting('SMTP_PASSWORD', 'password'),
    'from' => $getSetting('EMAIL_FROM', 'from', $username),
    'app_url' => $getSetting('APP_URL', 'app_url'),
];
