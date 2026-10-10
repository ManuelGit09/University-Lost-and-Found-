<?php
require_once 'config.php';

if (appMaintenanceActive()) {
    header('Location: login.php?error=maintenance');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guest Access - UB FIND</title>
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
            background: linear-gradient(135deg, var(--ub-red-dark), var(--ub-red));
            overflow: hidden;
        }

        .guest-boot {
            min-height: 100vh;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            isolation: isolate;
        }

        .guest-boot::before {
            content: "";
            position: absolute;
            width: min(78vw, 760px);
            height: min(78vw, 760px);
            right: -26vw;
            top: -28vw;
            border-radius: 50%;
            background: rgba(242, 173, 0, 0.18);
            z-index: -1;
            animation: pulseGlow 2.4s ease-in-out infinite;
        }

        .guest-boot-card {
            width: min(560px, 100%);
            text-align: center;
        }

        .guest-icon {
            width: 112px;
            height: 112px;
            margin: 0 auto 1.6rem;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: var(--ub-gold);
            color: var(--ub-red);
            box-shadow: inset 0 0 0 6px rgba(255, 255, 255, 0.3), 0 20px 40px rgba(0, 0, 0, 0.22);
            animation: iconIn 650ms ease both;
        }

        .guest-icon::before {
            content: "";
            position: absolute;
            inset: -12px;
            border: 3px solid rgba(255, 255, 255, 0.2);
            border-top-color: var(--ub-gold);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        .guest-icon img {
            width: 100%;
            height: 100%;
            display: block;
            border-radius: 50%;
            object-fit: cover;
        }

        h1 {
            margin: 0;
            color: var(--white);
            font-size: clamp(2.2rem, 7vw, 4rem);
            font-weight: 900;
            line-height: 1.05;
            animation: fadeUp 650ms 180ms ease both;
        }

        h1 span {
            color: var(--ub-gold);
        }

        p {
            margin: 1rem auto 2rem;
            max-width: 430px;
            color: rgba(255, 255, 255, 0.82);
            font-size: clamp(1rem, 2vw, 1.2rem);
            font-weight: 700;
            line-height: 1.5;
            animation: fadeUp 650ms 320ms ease both;
        }

        .boot-track {
            width: min(340px, 78vw);
            height: 8px;
            margin: 0 auto;
            overflow: hidden;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.22);
            animation: fadeUp 650ms 460ms ease both;
        }

        .boot-bar {
            width: 46%;
            height: 100%;
            border-radius: inherit;
            background: linear-gradient(90deg, var(--ub-gold), #ffd86e);
            animation: loadAcross 1.25s ease-in-out infinite;
        }

        .boot-label {
            margin-top: 1rem;
            color: rgba(255, 255, 255, 0.74);
            font-size: 0.82rem;
            font-weight: 900;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            animation: fadeUp 650ms 560ms ease both;
        }

        .skip-link {
            position: fixed;
            right: 1.25rem;
            bottom: 1.25rem;
            min-height: 42px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0 1rem;
            border: 1px solid rgba(255, 255, 255, 0.34);
            border-radius: 999px;
            color: var(--white);
            background: rgba(255, 255, 255, 0.1);
            text-decoration: none;
            font-size: 0.82rem;
            font-weight: 800;
        }

        .guest-boot.is-leaving {
            animation: leavePage 420ms ease forwards;
        }

        @keyframes iconIn {
            from {
                opacity: 0;
                transform: scale(0.7);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(16px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes loadAcross {
            from {
                transform: translateX(-115%);
            }
            to {
                transform: translateX(245%);
            }
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        @keyframes pulseGlow {
            0%, 100% {
                transform: scale(1);
                opacity: 0.8;
            }
            50% {
                transform: scale(1.05);
                opacity: 1;
            }
        }

        @keyframes leavePage {
            to {
                opacity: 0;
                transform: scale(1.02);
            }
        }

        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                animation-duration: 1ms !important;
                animation-iteration-count: 1 !important;
            }
        }
    </style>
</head>
<body>
    <main class="guest-boot" id="guestBoot">
        <section class="guest-boot-card" aria-label="Guest access loading">
            <div class="guest-icon" aria-hidden="true">
                <img src="background/ub.png" alt="University of Batangas seal">
            </div>
            <h1>Guest <span>Access</span></h1>
            <p>Opening lost and found browsing without signing in.</p>
            <div class="boot-track" aria-hidden="true">
                <div class="boot-bar"></div>
            </div>
            <div class="boot-label">Starting guest mode</div>
        </section>

        <a class="skip-link" href="Guest.php" id="guestSkipLink">Skip</a>
    </main>

    <script>
        const guestBoot = document.getElementById('guestBoot');
        const delay = window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 500 : 2100;

        document.getElementById('guestSkipLink').addEventListener('click', event => {
            event.preventDefault();
            window.location.replace('Guest.php');
        });

        window.setTimeout(() => {
            guestBoot.classList.add('is-leaving');
            window.setTimeout(() => {
                window.location.replace('Guest.php');
            }, 400);
        }, delay);
    </script>
</body>
</html>
