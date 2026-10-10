<?php
require 'config.php';
require_once 'authentication_mail.php';

$error = '';
$notice = $_SESSION['pending_login_notice'] ?? '';
unset($_SESSION['pending_login_notice']);
$mode = $_GET['mode'] ?? 'signup';
$isLoginVerification = $mode === 'login';
$pendingLogin = $_SESSION['pending_login'] ?? null;
$pending = $_SESSION['pending_signup'] ?? null;

function sendVerificationEmail($email, $code) {
    $result = ubfindSendAuthenticationCode($email, $code, '');
    return $result['success'];
}

if ($isLoginVerification && !$pendingLogin) {
    header('Location: login.php');
    exit;
}

if (!$isLoginVerification && !$pending) {
    header('Location: login.php?signup=1');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'verify';

    if ($isLoginVerification) {
        if ($action === 'resend') {
            $user = [
                'id' => $pendingLogin['user_id'],
                'role' => $pendingLogin['role'],
                'email' => $pendingLogin['email'],
                'name' => $pendingLogin['name'] ?? ''
            ];
            $sendResult = ubfindCreatePendingLogin($user);
            $pendingLogin = $_SESSION['pending_login'];
            if ($sendResult['success']) {
                $notice = 'A new authentication code has been sent to your email.';
            } else {
                $notice = 'Email sender is not configured. Use the displayed development code to continue.';
            }
        } else {
            $code = preg_replace('/\D/', '', $_POST['code'] ?? '');
            $pendingLogin = $_SESSION['pending_login'];

            if (time() > (int)$pendingLogin['expires_at']) {
                $error = 'Authentication code expired. Please request a new code.';
            } elseif (strlen($code) !== 6) {
                $error = 'Enter the 6-digit authentication code.';
            } elseif (($pendingLogin['attempts'] ?? 0) >= 5) {
                $error = 'Too many incorrect attempts. Please request a new code.';
            } elseif (!password_verify($code, $pendingLogin['code_hash'])) {
                $_SESSION['pending_login']['attempts'] = ($pendingLogin['attempts'] ?? 0) + 1;
                $error = 'Invalid authentication code.';
            } else {
                $_SESSION['user_id'] = $pendingLogin['user_id'];
                $_SESSION['role'] = $pendingLogin['role'];
                $_SESSION['email'] = $pendingLogin['email'];
                $_SESSION['name'] = $pendingLogin['name'] ?? '';
                unset($_SESSION['pending_login']);

                $loginMode = in_array($_SESSION['role'], [ROLE_STUDENT, ROLE_GUEST], true) ? $_SESSION['role'] : 'default';
                $nextPage = 'MainMenu.php';
                header('Location: loading_page.php?mode=' . rawurlencode($loginMode) . '&next=' . rawurlencode($nextPage));
                exit;
            }
        }
    } elseif ($action === 'resend') {
        $code = (string)random_int(100000, 999999);
        $_SESSION['pending_signup']['code_hash'] = password_hash($code, PASSWORD_DEFAULT);
        $_SESSION['pending_signup']['expires_at'] = time() + 600;
        $_SESSION['pending_signup']['attempts'] = 0;
        $pending = $_SESSION['pending_signup'];

        if (sendVerificationEmail($pending['email'], $code)) {
            $notice = 'A new verification code has been sent to your email.';
        } else {
            $error = 'Could not resend the verification code. Please check the server mail setup.';
        }
    } else {
        $code = preg_replace('/\D/', '', $_POST['code'] ?? '');
        $pending = $_SESSION['pending_signup'];

        if (time() > (int)$pending['expires_at']) {
            $error = 'Verification code expired. Please request a new code.';
        } elseif (strlen($code) !== 6) {
            $error = 'Enter the 6-digit verification code.';
        } elseif (($pending['attempts'] ?? 0) >= 5) {
            $error = 'Too many incorrect attempts. Please request a new code.';
        } elseif (!password_verify($code, $pending['code_hash'])) {
            $_SESSION['pending_signup']['attempts'] = ($pending['attempts'] ?? 0) + 1;
            $error = 'Invalid verification code.';
        } else {
            $pdo = getDB();
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$pending['email']]);

            if ($stmt->fetch()) {
                unset($_SESSION['pending_signup']);
                $error = 'This email is already registered. Please log in.';
            } else {
                $stmt = $pdo->prepare("INSERT INTO users (email, password, role) VALUES (?, ?, ?)");
                if ($stmt->execute([$pending['email'], $pending['password_hash'], $pending['role']])) {
                    $_SESSION['user_id'] = $pdo->lastInsertId();
                    $_SESSION['role'] = $pending['role'];
                    unset($_SESSION['pending_signup']);
                    logActivity('signup_verified');
                    $nextPage = 'MainMenu.php';
                    header('Location: ' . $nextPage);
                    exit;
                }

                $error = 'Failed to create account.';
            }
        }
    }
}

$pendingLogin = $_SESSION['pending_login'] ?? $pendingLogin;
$pending = $_SESSION['pending_signup'] ?? $pending;
$targetEmail = $isLoginVerification ? $pendingLogin['email'] : $pending['email'];
$maskedEmail = preg_replace('/(^.).*(@.*$)/', '$1***$2', $targetEmail);
$devCode = $isLoginVerification ? ($pendingLogin['dev_code'] ?? '') : '';
$pageTitle = $isLoginVerification ? 'Authentication Code - UB Find' : 'Email Verification - UB Find';
$heading = $isLoginVerification ? 'Enter authentication code' : 'Verify your email';
$copy = $isLoginVerification
    ? 'Enter the 6-digit authentication code sent to'
    : 'Enter the 6-digit code sent to';
$authRole = strtolower((string)($isLoginVerification ? ($pendingLogin['role'] ?? '') : ($pending['role'] ?? '')));
$roleLabel = $authRole === ROLE_GUEST ? 'Guest Account' : ($authRole === ROLE_STUDENT ? 'Student Access' : 'Secure Access');
$introTitle = $authRole === ROLE_GUEST ? 'Continue to your UB FIND guest account' : ($authRole === ROLE_STUDENT ? 'Continue to your UB FIND student workspace' : 'Continue to UB FIND');
$introCopy = $authRole === ROLE_GUEST
    ? 'Confirm your email to open the main UB FIND workspace with guest account access.'
    : ($authRole === ROLE_STUDENT
        ? 'Confirm your email to open your student dashboard, reports, claims, and item updates.'
        : 'Confirm your email to protect your UB FIND account.');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="icon" href="favicon.ico" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/favicon-32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/favicon-16.png">
    <link rel="apple-touch-icon" href="assets/apple-touch-icon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700;800;900&display=swap" rel="stylesheet">
    <link href="assets/style.css" rel="stylesheet">
</head>
<body class="auth-page verification-page">
    <button type="button" class="auth-back-btn" id="verifyBackBtn" aria-label="Go back">
        <span aria-hidden="true">&larr;</span>
        Back
    </button>

    <main class="verification-shell">
        <section class="verification-intro" aria-label="UB FIND authentication introduction">
            <img src="background/ub.png" alt="University of Batangas seal" class="verification-intro-mark">
            <span><?= htmlspecialchars($roleLabel) ?></span>
            <h2><?= htmlspecialchars($introTitle) ?></h2>
            <p><?= htmlspecialchars($introCopy) ?></p>
            <ul>
                <li>Email code authentication keeps account access private.</li>
                <li>The code expires in 5 minutes for login authentication.</li>
                <li>Only the verified account can continue to UB FIND.</li>
            </ul>
        </section>

        <section class="verification-card">
            <img src="background/ub.png" alt="University of Batangas seal" class="verification-mark">
            <h1><?= htmlspecialchars($heading) ?></h1>
            <p class="verification-copy"><?= htmlspecialchars($copy) ?> <strong><?= htmlspecialchars($maskedEmail) ?></strong>.</p>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($notice): ?>
                <div class="alert alert-success"><?= htmlspecialchars($notice) ?></div>
            <?php endif; ?>
            <?php if ($devCode): ?>
                <div class="alert alert-warning">Development code: <strong><?= htmlspecialchars($devCode) ?></strong></div>
            <?php endif; ?>

            <form method="POST" action="" class="verification-form">
                <input type="hidden" name="action" value="verify">
                <label class="form-label" for="code"><?= $isLoginVerification ? 'Authentication Code' : 'Verification Code' ?></label>
                <input
                    type="text"
                    inputmode="numeric"
                    pattern="[0-9]{6}"
                    maxlength="6"
                    class="form-control verification-code-input"
                    id="code"
                    name="code"
                    placeholder="000000"
                    autocomplete="one-time-code"
                    required
                >
                <button type="submit" class="btn btn-primary w-100 mt-3"><?= $isLoginVerification ? 'Authenticate and Continue' : 'Verify and Continue' ?></button>
            </form>

            <form method="POST" action="" class="mt-3">
                <input type="hidden" name="action" value="resend">
                <button type="submit" class="verification-link-btn">Resend code</button>
            </form>
        </section>
    </main>

    <script>
        document.getElementById('verifyBackBtn').addEventListener('click', () => {
            window.location.href = <?= json_encode($isLoginVerification ? 'login.php' : 'login.php?signup=1') ?>;
        });
    </script>
</body>
</html>
