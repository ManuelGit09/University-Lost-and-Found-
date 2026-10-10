<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$next = $_GET['next'] ?? 'index.php?intro=done';
$mode = $_GET['mode'] ?? 'default';

if (preg_match('/^(https?:)?\/\//i', $next) || str_contains($next, '..')) {
    $next = 'index.php?intro=done';
}

$allowedModes = [
    'default',
    'guest',
    'student',
    'admin',
    'admin_shutdown',
    'logout',
    'admin_logout',
    'student_logout',
    'guest_logout'
];

if (!in_array($mode, $allowedModes, true)) {
    $mode = 'default';
}

if ($mode === 'admin' && ($_SESSION['role'] ?? '') === 'admin' && !empty($_SESSION['admin_privileges_booted'])) {
    header('Location: ' . $next);
    exit;
}

if ($mode === 'admin' && ($_SESSION['role'] ?? '') === 'admin') {
    $_SESSION['admin_privileges_booted'] = true;
}

if ($mode === 'admin_shutdown') {
    unset($_SESSION['admin_privileges_booted']);
}

$isAdminShutdown = $mode === 'admin_shutdown';
$isLogoutMode = str_ends_with($mode, '_logout') || $mode === 'logout';

$loadingText = match ($mode) {
    'guest' => 'Opening guest account access',
    'student' => 'Opening student access',
    'admin' => 'Booting admin privileges',
    'admin_shutdown' => 'Shutting down admin privileges',
    'admin_logout' => 'Logging out administrator',
    'student_logout' => 'Logging out student',
    'guest_logout' => 'Logging out guest',
    'logout' => 'Logging out',
    default => 'Preparing UB FIND'
};
$tagline = match ($mode) {
    'guest' => "Guest account workspace loading.<br>Your reports and claims are ready.",
    'student' => "Student workspace loading.<br>Your reports and claims are ready.",
    'admin' => "Secure console loading.<br>Administrator access is starting.",
    'admin_shutdown' => "Closing administrator controls.<br>Admin privileges are shutting down.",
    'admin_logout' => "Closing administrator tools.<br>Your secure session is ending.",
    'student_logout' => "Saving your workspace state.<br>Your session is ending.",
    'guest_logout' => "Closing guest access.<br>Thanks for browsing UB FIND.",
    'logout' => "Closing UB FIND.<br>Your session is ending.",
    default => "Lost something?<br>Let's help you find it."
};
$delay = match ($mode) {
    'guest' => 2100,
    'student' => 2400,
    'admin' => 3400,
    'admin_shutdown' => 2300,
    'admin_logout' => 2500,
    'student_logout' => 2300,
    'guest_logout' => 2100,
    'logout' => 2100,
    default => 3200
};
$pageClasses = [];
if ($mode === 'admin') {
    $pageClasses[] = 'intro-page-admin';
}
if ($isLogoutMode || $isAdminShutdown) {
    $pageClasses[] = 'intro-page-logout';
}
if ($mode === 'student') {
    $pageClasses[] = 'intro-page-student';
}
$pageClass = $pageClasses ? ' ' . implode(' ', $pageClasses) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UB FIND</title>
    <link rel="icon" href="favicon.ico" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/favicon-32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/favicon-16.png">
    <link rel="apple-touch-icon" href="assets/apple-touch-icon.png">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --ub-red: #890016;
            --ub-red-dark: #660010;
            --ub-gold: #f2ad00;
            --ub-gold-soft: #ffd665;
            --white: #ffffff;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            width: 100%;
            min-height: 100%;
            margin: 0;
        }

        body {
            min-height: 100vh;
            font-family: 'Montserrat', 'Segoe UI', sans-serif;
            color: var(--white);
            background: var(--ub-red);
            overflow: hidden;
        }

        .intro-page {
            position: relative;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: clamp(1.5rem, 4vw, 3rem);
            isolation: isolate;
        }

        .intro-page-admin {
            background:
                linear-gradient(rgba(255, 214, 101, 0.07) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 214, 101, 0.07) 1px, transparent 1px),
                radial-gradient(circle at 50% 18%, rgba(242, 173, 0, 0.24), transparent 34%),
                linear-gradient(135deg, #350008, var(--ub-red-dark) 55%, #180003);
            background-size: 44px 44px, 44px 44px, auto, auto;
            animation: adminGridDrift 5.5s linear infinite;
        }

        .intro-page-student {
            background:
                radial-gradient(circle at 24% 18%, rgba(255, 214, 101, 0.18), transparent 30%),
                linear-gradient(135deg, var(--ub-red), #9d172a 52%, var(--ub-red-dark));
        }

        .intro-page-logout {
            background:
                radial-gradient(circle at 50% 20%, rgba(255, 255, 255, 0.12), transparent 34%),
                linear-gradient(135deg, #4a000b, var(--ub-red-dark));
        }

        .intro-page-admin::before {
            width: min(74vw, 880px);
            height: min(74vw, 880px);
            right: 50%;
            top: 50%;
            background: rgba(242, 173, 0, 0.08);
            border: 1px solid rgba(255, 214, 101, 0.18);
            transform: translate(50%, -50%);
            animation: adminRadar 2.1s ease-in-out infinite;
        }

        .intro-page-admin::after {
            width: 100%;
            height: 180px;
            left: 0;
            bottom: auto;
            top: -180px;
            border-radius: 0;
            background: linear-gradient(180deg, transparent, rgba(255, 214, 101, 0.17), transparent);
            animation: adminScanLine 2.4s ease-in-out infinite;
        }

        .intro-page::before,
        .intro-page::after {
            content: "";
            position: absolute;
            border-radius: 50%;
            z-index: -1;
        }

        .intro-page::before {
            width: min(68vw, 820px);
            height: min(68vw, 820px);
            right: -22vw;
            top: -24vw;
            background: rgba(242, 173, 0, 0.13);
            animation: floatGlow 4s ease-in-out infinite;
        }

        .intro-page::after {
            width: min(54vw, 640px);
            height: min(54vw, 640px);
            left: -22vw;
            bottom: -26vw;
            background: rgba(255, 255, 255, 0.08);
            animation: floatGlow 4.8s ease-in-out infinite reverse;
        }

        .intro-card {
            width: min(720px, 100%);
            min-height: 520px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: clamp(2rem, 5vw, 4rem);
            position: relative;
        }

        .seal-wrap {
            width: 116px;
            height: 116px;
            margin-bottom: 2rem;
            position: relative;
            animation: sealEnter 900ms cubic-bezier(.2,.8,.2,1) both;
        }

        .seal-wrap::before {
            content: "";
            position: absolute;
            inset: -10px;
            border: 3px solid rgba(255, 214, 101, 0.38);
            border-top-color: var(--ub-gold);
            border-radius: 50%;
            animation: spin 1.7s linear infinite;
        }

        .seal {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: var(--ub-gold);
            color: var(--ub-red);
            border: 6px solid #c98200;
            box-shadow: inset 0 0 0 6px rgba(255, 255, 255, 0.32), 0 22px 44px rgba(0, 0, 0, 0.18);
            font-size: 2rem;
            font-weight: 900;
        }

        .seal img {
            width: 100%;
            height: 100%;
            display: block;
            border-radius: 50%;
            object-fit: contain;
        }

        .intro-page-admin .seal {
            font-size: 1.55rem;
            letter-spacing: 0.04em;
            background: #fff2bf;
            box-shadow: inset 0 0 0 6px rgba(137, 0, 22, 0.12), 0 0 38px rgba(242, 173, 0, 0.36);
            animation: adminLockPulse 1.35s ease-in-out infinite;
        }

        .intro-page-admin .finder-mark {
            color: rgba(255, 214, 101, 0.88);
            filter: drop-shadow(0 0 18px rgba(242, 173, 0, 0.34));
        }

        .intro-page-admin .loading-bar {
            width: 55%;
            animation-duration: 1.35s;
        }

        .intro-page-student .seal {
            background: #ffffff;
            color: var(--ub-red);
        }

        .intro-page-student .finder-mark {
            color: rgba(255, 255, 255, 0.84);
            animation-name: studentFloat;
        }

        .intro-page-logout .seal-wrap::before {
            border-color: rgba(255, 255, 255, 0.2);
            border-bottom-color: var(--ub-gold);
            border-top-color: rgba(255, 255, 255, 0.2);
            animation-duration: 1.1s;
            animation-direction: reverse;
        }

        .intro-page-logout .seal {
            background: transparent;
            color: var(--ub-gold-soft);
            border-color: rgba(255, 214, 101, 0.72);
            box-shadow: inset 0 0 0 6px rgba(255, 255, 255, 0.08), 0 0 34px rgba(242, 173, 0, 0.24);
        }

        .intro-page-logout .finder-mark {
            color: rgba(255, 214, 101, 0.74);
            animation: logoutShrink 1.6s ease-in-out infinite;
        }

        .intro-page-logout .loading-bar {
            animation-name: unloadAcross;
        }

        .brand-title {
            margin: 0;
            font-family: Georgia, 'Times New Roman', serif;
            font-size: clamp(3.8rem, 10vw, 7.5rem);
            line-height: 0.95;
            font-weight: 700;
            color: var(--ub-gold);
            white-space: nowrap;
            animation: titleRise 850ms 300ms cubic-bezier(.2,.8,.2,1) both;
        }

        .brand-title span {
            color: var(--white);
        }

        .tagline {
            margin: 1.3rem 0 2.2rem;
            color: rgba(255, 255, 255, 0.86);
            font-size: clamp(1rem, 2vw, 1.3rem);
            font-weight: 700;
            line-height: 1.45;
            animation: fadeUp 700ms 650ms ease both;
        }

        .finder-mark {
            width: 154px;
            height: 154px;
            margin-bottom: 2.2rem;
            color: rgba(255, 255, 255, 0.7);
            animation: markScan 1.8s 750ms ease-in-out infinite;
        }

        .finder-mark svg {
            width: 100%;
            height: 100%;
            display: block;
        }

        .loading-track {
            width: min(360px, 78vw);
            height: 8px;
            overflow: hidden;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.2);
            animation: fadeUp 700ms 900ms ease both;
        }

        .loading-bar {
            width: 42%;
            height: 100%;
            border-radius: inherit;
            background: linear-gradient(90deg, var(--ub-gold), var(--ub-gold-soft));
            animation: loadAcross 1.65s ease-in-out infinite;
        }

        .loading-text {
            margin: 1rem 0 0;
            color: rgba(255, 255, 255, 0.74);
            font-size: 0.82rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.16em;
            animation: fadeUp 700ms 1s ease both;
        }

        .admin-boot-status {
            width: min(440px, 86vw);
            display: grid;
            gap: 0.55rem;
            margin: 1.2rem auto 0;
            text-align: left;
        }

        .admin-boot-status span {
            display: flex;
            align-items: center;
            justify-content: space-between;
            min-height: 36px;
            padding: 0 0.85rem;
            border: 1px solid rgba(255, 214, 101, 0.28);
            border-radius: 8px;
            color: rgba(255, 255, 255, 0.86);
            background: rgba(255, 255, 255, 0.06);
            font-size: 0.72rem;
            font-weight: 900;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            animation: adminStatusIn 520ms ease both;
        }

        .admin-boot-status span:nth-child(1) {
            animation-delay: 900ms;
        }

        .admin-boot-status span:nth-child(2) {
            animation-delay: 1250ms;
        }

        .admin-boot-status span:nth-child(3) {
            animation-delay: 1600ms;
        }

        .admin-boot-status strong {
            color: var(--ub-gold-soft);
            font-size: 0.68rem;
        }

        .skip-link {
            position: fixed;
            right: clamp(1rem, 3vw, 2rem);
            bottom: clamp(1rem, 3vw, 2rem);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 42px;
            padding: 0 1.1rem;
            border: 1px solid rgba(255, 255, 255, 0.34);
            border-radius: 999px;
            color: var(--white);
            background: rgba(255, 255, 255, 0.1);
            text-decoration: none;
            font-size: 0.82rem;
            font-weight: 800;
            backdrop-filter: blur(8px);
            transition: background 0.2s ease, border-color 0.2s ease, transform 0.2s ease;
        }

        .skip-link:hover,
        .skip-link:focus {
            color: var(--white);
            background: rgba(255, 255, 255, 0.18);
            border-color: rgba(255, 255, 255, 0.56);
            transform: translateY(-1px);
            outline: none;
        }

        .intro-page.is-leaving {
            animation: pageLeave 450ms ease forwards;
        }

        @keyframes sealEnter {
            from {
                opacity: 0;
                transform: scale(0.7) translateY(18px);
            }
            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        @keyframes titleRise {
            from {
                opacity: 0;
                transform: translateY(22px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(14px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes markScan {
            0%, 100% {
                transform: translateY(0);
                opacity: 0.68;
            }
            50% {
                transform: translateY(-7px);
                opacity: 0.94;
            }
        }

        @keyframes studentFloat {
            0%, 100% {
                transform: translateY(0) rotate(-2deg);
                opacity: 0.7;
            }
            50% {
                transform: translateY(-8px) rotate(2deg);
                opacity: 0.96;
            }
        }

        @keyframes logoutShrink {
            0%, 100% {
                transform: scale(1);
                opacity: 0.84;
            }
            50% {
                transform: scale(0.92);
                opacity: 0.58;
            }
        }

        @keyframes loadAcross {
            0% {
                transform: translateX(-110%);
            }
            55%, 100% {
                transform: translateX(250%);
            }
        }

        @keyframes unloadAcross {
            0% {
                transform: translateX(250%);
            }
            55%, 100% {
                transform: translateX(-110%);
            }
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        @keyframes floatGlow {
            0%, 100% {
                transform: translate3d(0, 0, 0) scale(1);
            }
            50% {
                transform: translate3d(0, 18px, 0) scale(1.03);
            }
        }

        @keyframes pageLeave {
            to {
                opacity: 0;
                transform: scale(1.025);
            }
        }

        @keyframes adminGridDrift {
            to {
                background-position: 44px 44px, 44px 44px, 0 0, 0 0;
            }
        }

        @keyframes adminRadar {
            0%, 100% {
                opacity: 0.4;
                transform: translate(50%, -50%) scale(0.92);
            }
            50% {
                opacity: 0.88;
                transform: translate(50%, -50%) scale(1.05);
            }
        }

        @keyframes adminScanLine {
            0% {
                transform: translateY(0);
                opacity: 0;
            }
            20%, 65% {
                opacity: 1;
            }
            100% {
                transform: translateY(calc(100vh + 180px));
                opacity: 0;
            }
        }

        @keyframes adminLockPulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
        }

        @keyframes adminStatusIn {
            from {
                opacity: 0;
                transform: translateX(-18px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @media (max-width: 640px) {
            .intro-card {
                min-height: auto;
                padding: 2rem 0.5rem;
            }

            .seal-wrap {
                width: 92px;
                height: 92px;
                margin-bottom: 1.5rem;
            }

            .seal {
                font-size: 1.5rem;
            }

            .brand-title {
                font-size: clamp(3rem, 15vw, 4.6rem);
            }

            .finder-mark {
                width: 122px;
                height: 122px;
            }

            .admin-boot-status span {
                align-items: flex-start;
                flex-direction: column;
                justify-content: center;
                gap: 0.15rem;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                animation-duration: 1ms !important;
                animation-iteration-count: 1 !important;
                scroll-behavior: auto !important;
            }
        }
    </style>
</head>
<body>
    <main class="intro-page<?= $pageClass ?>" id="introPage">
        <section class="intro-card" aria-label="UB FIND loading intro">
            <div class="seal-wrap">
                <div class="seal"><img src="background/ub.png" alt="University of Batangas seal"></div>
            </div>

            <h1 class="brand-title"><span>UB</span> FIND</h1>
            <p class="tagline"><?= $tagline ?></p>

            <div class="finder-mark" aria-hidden="true">
                <?php if ($mode === 'admin'): ?>
                <svg viewBox="0 0 128 128" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M64 13L24 29V57C24 82 40 104 64 115C88 104 104 82 104 57V29L64 13Z" stroke="currentColor" stroke-width="7" stroke-linejoin="round"/>
                    <path d="M48 64L59 75L82 50" stroke="currentColor" stroke-width="8" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M64 30V40M64 88V98M35 57H45M83 57H93" stroke="currentColor" stroke-width="6" stroke-linecap="round"/>
                </svg>
                <?php elseif ($mode === 'student'): ?>
                <svg viewBox="0 0 128 128" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M24 36H104V100H24V36Z" stroke="currentColor" stroke-width="7" stroke-linejoin="round"/>
                    <path d="M38 52H90M38 68H74M38 84H84" stroke="currentColor" stroke-width="6" stroke-linecap="round"/>
                    <path d="M48 28H80" stroke="currentColor" stroke-width="7" stroke-linecap="round"/>
                    <circle cx="96" cy="34" r="14" stroke="currentColor" stroke-width="6"/>
                    <path d="M89 34L94 39L103 29" stroke="currentColor" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <?php elseif ($isLogoutMode || $isAdminShutdown): ?>
                <svg viewBox="0 0 128 128" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M32 22H80C87.7 22 94 28.3 94 36V92C94 99.7 87.7 106 80 106H32V22Z" stroke="currentColor" stroke-width="7" stroke-linejoin="round"/>
                    <path d="M58 64H112M112 64L94 46M112 64L94 82" stroke="currentColor" stroke-width="8" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M48 64H49" stroke="currentColor" stroke-width="9" stroke-linecap="round"/>
                </svg>
                <?php else: ?>
                <svg viewBox="0 0 128 128" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M25 70L64 54L103 70V101L64 116L25 101V70Z" stroke="currentColor" stroke-width="6" stroke-linejoin="round"/>
                    <path d="M25 70L64 86L103 70" stroke="currentColor" stroke-width="6" stroke-linejoin="round"/>
                    <path d="M64 86V116" stroke="currentColor" stroke-width="6" stroke-linecap="round"/>
                    <circle cx="75" cy="47" r="31" stroke="currentColor" stroke-width="7"/>
                    <path d="M98 70L116 88" stroke="currentColor" stroke-width="9" stroke-linecap="round"/>
                </svg>
                <?php endif; ?>
            </div>

            <div class="loading-track" aria-hidden="true">
                <div class="loading-bar"></div>
            </div>
            <p class="loading-text"><?= htmlspecialchars($loadingText, ENT_QUOTES, 'UTF-8') ?></p>
            <?php if ($mode === 'admin'): ?>
            <div class="admin-boot-status" aria-hidden="true">
                <span>Identity verified <strong>OK</strong></span>
                <span>Admin route unlocked <strong>READY</strong></span>
                <span>Sidebar panel enabled <strong>LIVE</strong></span>
            </div>
            <?php endif; ?>
        </section>

        <a class="skip-link" href="<?= htmlspecialchars($next, ENT_QUOTES, 'UTF-8') ?>">Skip Intro</a>
    </main>

    <script>
        const nextPage = <?= json_encode($next) ?>;
        const introPage = document.getElementById('introPage');
        const delay = window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 700 : <?= json_encode($delay) ?>;

        window.setTimeout(() => {
            introPage.classList.add('is-leaving');
            window.setTimeout(() => {
                window.location.href = nextPage;
            }, 430);
        }, delay);
    </script>
</body>
</html>
