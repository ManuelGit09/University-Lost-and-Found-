<?php
require 'config.php';

$mode = $_GET['mode'] ?? 'login';
$pendingLogin = $_SESSION['pending_login'] ?? null;

if ($mode !== 'login' || !$pendingLogin) {
    header('Location: login.php');
    exit;
}

$role = strtolower((string)($pendingLogin['role'] ?? ''));
$isGuest = $role === ROLE_GUEST;
$isStudent = $role === ROLE_STUDENT;
$roleLabel = $isGuest ? 'Guest Account' : ($isStudent ? 'Student Access' : 'Secure Access');
$heading = $isGuest ? 'Authenticate guest account' : ($isStudent ? 'Authenticate student access' : 'Authenticate your account');
$copy = $isGuest
    ? 'Before opening the main UB FIND workspace, we will confirm your guest account email with a short authentication code.'
    : ($isStudent
        ? 'Before opening your student workspace, UB FIND will confirm your email with a short authentication code.'
        : 'UB FIND will confirm your email with a short authentication code before continuing.');
$email = (string)($pendingLogin['email'] ?? '');
$maskedEmail = $email ? preg_replace('/(^.).*(@.*$)/', '$1***$2', $email) : 'your email';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Authentication Intro - UB FIND</title>
    <link rel="icon" href="favicon.ico" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/favicon-32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/favicon-16.png">
    <link rel="apple-touch-icon" href="assets/apple-touch-icon.png">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700;800;900&display=swap" rel="stylesheet">
    <link href="assets/style.css" rel="stylesheet">
</head>
<body class="auth-page auth-intro-page">
    <button type="button" class="auth-back-btn auth-back-btn-strong" id="authIntroBackBtn" aria-label="Go back">
        <span aria-hidden="true">&larr;</span>
        Back
    </button>

    <main class="auth-intro-shell">
        <section class="auth-intro-panel" aria-label="UB FIND authentication introduction">
            <div class="auth-intro-brand">
                <img src="background/ub.png" alt="University of Batangas seal">
                <div>
                    <strong>UB FIND</strong>
                    <span>Lost and Found System</span>
                </div>
            </div>

            <span class="auth-intro-pill"><?= htmlspecialchars($roleLabel) ?></span>
            <h1><?= htmlspecialchars($heading) ?></h1>
            <p><?= htmlspecialchars($copy) ?></p>

            <div class="auth-intro-code-card">
                <small>Authentication code will be checked for</small>
                <strong><?= htmlspecialchars($maskedEmail) ?></strong>
            </div>

            <ul class="auth-intro-list">
                <li>Open your email inbox and find the UB FIND authentication code.</li>
                <li>Enter the 6-digit code on the next screen.</li>
                <li>The code expires in 5 minutes for account security.</li>
            </ul>

            <div class="auth-intro-actions">
                <a href="verification.php?mode=login">Authenticate and Continue</a>
                <button type="button" id="authIntroCancelBtn">Cancel login</button>
            </div>
        </section>
    </main>

    <script>
        const goBack = () => {
            window.location.href = 'login.php';
        };

        document.getElementById('authIntroBackBtn').addEventListener('click', goBack);
        document.getElementById('authIntroCancelBtn').addEventListener('click', goBack);
    </script>
</body>
</html>
