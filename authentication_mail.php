<?php

function ubfindSendAuthenticationCode($email, $code, $name = '') {
    $python = getenv('UBFIND_PYTHON') ?: 'python';
    $script = __DIR__ . DIRECTORY_SEPARATOR . 'authentication.py';
    $command = escapeshellcmd($python)
        . ' ' . escapeshellarg($script)
        . ' --to ' . escapeshellarg($email)
        . ' --code ' . escapeshellarg($code)
        . ' --name ' . escapeshellarg($name);

    $output = [];
    $exitCode = 0;
    exec($command, $output, $exitCode);
    $decoded = json_decode(implode("\n", $output), true);

    if ($exitCode === 0 && is_array($decoded) && !empty($decoded['success'])) {
        return ['success' => true, 'error' => ''];
    }

    return [
        'success' => false,
        'error' => is_array($decoded) ? ($decoded['error'] ?? 'Email sender failed.') : 'Email sender failed.'
    ];
}

function ubfindCreatePendingLogin($user) {
    $code = (string)random_int(100000, 999999);
    $_SESSION['pending_login'] = [
        'user_id' => $user['id'],
        'role' => $user['role'],
        'email' => $user['email'],
        'name' => $user['name'] ?? '',
        'code_hash' => password_hash($code, PASSWORD_DEFAULT),
        'expires_at' => time() + 300,
        'attempts' => 0,
        'created_at' => time()
    ];

    $result = ubfindSendAuthenticationCode($user['email'], $code, $user['name'] ?? '');
    if (!$result['success']) {
        $_SESSION['pending_login']['dev_code'] = $code;
    }

    return $result;
}
