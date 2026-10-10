<?php
require 'config.php';
require_once 'authentication_mail.php';

$signup_error = '';
$login_error = '';
$login_notice = '';
$claim_notice = '';
$access_notice = '';
$active_tab = 'login';

function usersJsonPath() {
    return __DIR__ . '/db/json_data/users.json';
}

function loadJsonUsers() {
    $path = usersJsonPath();
    if (!file_exists($path)) {
        return [];
    }

    $users = json_decode(file_get_contents($path), true);
    return is_array($users) ? $users : [];
}

function saveJsonUsers($users) {
    return file_put_contents(usersJsonPath(), json_encode(array_values($users), JSON_PRETTY_PRINT)) !== false;
}

function findJsonUserByEmail($email) {
    foreach (loadJsonUsers() as $user) {
        if (strtolower($user['email'] ?? '') === strtolower($email)) {
            return $user;
        }
    }

    return null;
}

function createJsonUser($name, $email, $password, $role) {
    $users = loadJsonUsers();
    $ids = array_column($users, 'id');
    $users[] = [
        'id' => $ids ? max($ids) + 1 : 1,
        'name' => $name,
        'email' => $email,
        'password' => password_hash($password, PASSWORD_DEFAULT),
        'role' => $role,
        'phone' => '',
        'address' => '',
        'department' => '',
        'year_level' => '',
        'student_number' => '',
        'profile_picture' => '',
        'created_at' => date('c')
    ];

    return saveJsonUsers($users);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'login') {
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            
            if (empty($email) || empty($password)) {
                $login_error = 'Please fill in all fields.';
            } else {
                $user = findJsonUserByEmail($email);
                
                if ($user && password_verify($password, $user['password'])) {
                    if (appMaintenanceActive() && ($user['role'] ?? '') !== ROLE_ADMIN) {
                        $login_error = 'UB FIND is temporarily under maintenance. Please try again later.';
                    } elseif (!empty($user['restricted'])) {
                        $login_error = 'This account is restricted. Contact the administrator.';
                    } else {
                    if (in_array($user['role'], [ROLE_STUDENT, ROLE_GUEST], true)) {
                        $sendResult = ubfindCreatePendingLogin($user);
                        if (!$sendResult['success']) {
                            $_SESSION['pending_login_notice'] = 'Email sender is not configured. Use the displayed development code to continue.';
                        }
                        header('Location: authentication_intro.php?mode=login');
                        exit;
                    }

                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['name'] = $user['name'] ?? '';
                    header('Location: loading_page.php?mode=default&next=MainMenu.php');
                    exit;
                    }
                } else {
                    $login_error = 'Invalid email or password.';
                }
            }
        } elseif ($_POST['action'] === 'signup') {
            $active_tab = 'signup';
            $name = trim($_POST['name'] ?? '');
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';
            $role = $_POST['role'] ?? 'student';
            $allowed_roles = ['student', 'admin', 'guest'];
            
            if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
                $signup_error = 'Please fill in all fields.';
            } elseif (strlen($name) < 2) {
                $signup_error = 'Name must be at least 2 characters.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $signup_error = 'Please enter a valid email address.';
            } elseif (!in_array($role, $allowed_roles, true)) {
                $signup_error = 'Please select a valid role.';
            } elseif ($password !== $confirm_password) {
                $signup_error = 'Passwords do not match.';
            } elseif (strlen($password) < 6) {
                $signup_error = 'Password must be at least 6 characters.';
            } else {
                if (findJsonUserByEmail($email)) {
                    $signup_error = 'Email already registered.';
                } elseif (createJsonUser($name, $email, $password, $role)) {
                    $active_tab = 'login';
                    $login_notice = 'Account created. Please log in to continue.';
                } else {
                    $signup_error = 'Failed to create account.';
                }
            }
        }
    }
} elseif (isset($_GET['signup'])) {
    $active_tab = 'signup';
}

if (isset($_GET['claim']) && $_GET['claim'] === 'required') {
    $claim_notice = 'To claim an item, please log in first. If you do not have an account yet, use Sign Up to create one.';
} elseif (isset($_GET['report']) && $_GET['report'] === 'required') {
    $claim_notice = 'To submit a lost or found report, please sign up or log in first.';
}

if ((isset($_GET['admin']) && $_GET['admin'] === 'required') || (isset($_GET['error']) && $_GET['error'] === 'access_denied')) {
    $access_notice = 'Access prevented: admin controls are restricted to administrator accounts only. Please log in with an admin account.';
}

if (isset($_GET['error']) && $_GET['error'] === 'maintenance') {
    $access_notice = 'UB FIND is temporarily under maintenance. Student and guest access is paused.';
}

if (isset($_GET['error']) && $_GET['error'] === 'restricted') {
    $access_notice = 'This account is restricted. Contact the administrator.';
}

$show_signup = ($active_tab === 'signup');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login / Sign Up - UB Find</title>
    <link rel="icon" href="favicon.ico" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/favicon-32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/favicon-16.png">
    <link rel="apple-touch-icon" href="assets/apple-touch-icon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700;800&display=swap" rel="stylesheet">
    <link href="assets/style.css" rel="stylesheet">
</head>
<body class="auth-page">
    <button type="button" class="auth-back-btn" id="authBackBtn" aria-label="Go back">
        <span aria-hidden="true">&larr;</span>
        Back
    </button>

    <main class="auth-shell">
        <section class="auth-brand-panel">
            <div class="auth-brand-content">
                <img src="background/ub.png" alt="University of Batangas seal" class="auth-seal">
                <h1><span>UB</span> FIND</h1>
                <p>Lost something?<br>Let's help you find it.</p>
                <div class="auth-search-illustration" aria-hidden="true">
                    <svg viewBox="0 0 128 128" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M25 70L64 54L103 70V101L64 116L25 101V70Z" stroke="currentColor" stroke-width="6" stroke-linejoin="round"/>
                        <path d="M25 70L64 86L103 70" stroke="currentColor" stroke-width="6" stroke-linejoin="round"/>
                        <path d="M64 86V116" stroke="currentColor" stroke-width="6" stroke-linecap="round"/>
                        <circle cx="75" cy="47" r="31" stroke="currentColor" stroke-width="7"/>
                        <path d="M98 70L116 88" stroke="currentColor" stroke-width="9" stroke-linecap="round"/>
                    </svg>
                </div>
            </div>
        </section>

        <section class="auth-content-panel">
            <div class="auth-content">
                <div class="auth-heading">
                    <h2>Welcome to UB FIND</h2>
                    <p>Choose an option to continue</p>
                </div>

                <div class="auth-option-card auth-form-card">
                    <div class="auth-option-icon auth-option-icon-red">
                        <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M12 12c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5Zm0 2c-4.42 0-8 2.24-8 5v1.5c0 .83.67 1.5 1.5 1.5h13c.83 0 1.5-.67 1.5-1.5V19c0-2.76-3.58-5-8-5Z"/>
                        </svg>
                    </div>
                    <div class="auth-option-body">
                        <?php if ($claim_notice): ?>
                            <div class="alert alert-warning"><?= htmlspecialchars($claim_notice) ?></div>
                        <?php endif; ?>
                        <?php if ($access_notice): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($access_notice) ?></div>
                        <?php endif; ?>
                        <ul class="nav auth-tabs" id="authTabs" role="tablist" aria-label="Authentication options">
                            <li class="nav-item">
                                <button class="nav-link <?= $show_signup ? '' : 'active' ?>" id="login-tab" type="button" data-bs-toggle="tab" data-bs-target="#login" role="tab" aria-controls="login" aria-selected="<?= $show_signup ? 'false' : 'true' ?>">Log In</button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link <?= $show_signup ? 'active' : '' ?>" id="signup-tab" type="button" data-bs-toggle="tab" data-bs-target="#signup" role="tab" aria-controls="signup" aria-selected="<?= $show_signup ? 'true' : 'false' ?>">Sign Up</button>
                            </li>
                        </ul>
                        <p class="auth-option-copy">For administrators, system staff, students, and guests.</p>

                        <div class="tab-content auth-tab-content">
                            <div class="tab-pane fade <?= $show_signup ? '' : 'show active' ?>" id="login">
                                <?php if ($login_notice): ?>
                                    <div class="alert alert-success"><?= htmlspecialchars($login_notice) ?></div>
                                <?php endif; ?>
                                <?php if ($login_error): ?>
                                    <div class="alert alert-danger"><?= htmlspecialchars($login_error) ?></div>
                                <?php endif; ?>
                                <form method="POST" action="" id="loginForm">
                                    <input type="hidden" name="action" value="login">
                                    <div class="mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" class="form-control" name="email" placeholder="you@example.com or your@ub.edu.ph" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Password</label>
                                        <input type="password" class="form-control" name="password" placeholder="Password" required>
                                    </div>
                                    <button type="submit" class="btn btn-primary w-100" id="loginSubmitBtn">Login</button>
                                </form>
                            </div>
                            <div class="tab-pane fade <?= $show_signup ? 'show active' : '' ?>" id="signup">
                                <?php if ($signup_error): ?>
                                    <div class="alert alert-danger"><?= htmlspecialchars($signup_error) ?></div>
                                <?php endif; ?>
                                <form method="POST" action="">
                                    <input type="hidden" name="action" value="signup">
                                    <div class="mb-3">
                                        <label class="form-label">Name</label>
                                        <input type="text" class="form-control" name="name" placeholder="Example: Juan Dela Cruz" required minlength="2">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" class="form-control" name="email" placeholder="you@example.com or your@ub.edu.ph" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Role</label>
                                        <select class="form-select" name="role" required>
                                            <option value="student">Student</option>
                                            <option value="admin">Admin</option>
                                            <option value="guest">Guest</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Password</label>
                                        <input type="password" class="form-control" name="password" placeholder="Password (min 6 characters)" required minlength="6">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Confirm Password</label>
                                        <input type="password" class="form-control" name="confirm_password" placeholder="Confirm Password" required minlength="6">
                                    </div>
                                    <button type="submit" class="btn btn-success w-100">Create Account</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <a class="auth-option-card auth-guest-card" href="guest_button.php">
                    <div class="auth-option-icon auth-option-icon-gold">
                        <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M12 12c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5Zm0 2c-4.42 0-8 2.24-8 5v1.5c0 .83.67 1.5 1.5 1.5h13c.83 0 1.5-.67 1.5-1.5V19c0-2.76-3.58-5-8-5Z"/>
                        </svg>
                    </div>
                    <div class="auth-option-body">
                        <h3>Continue as Guest</h3>
                        <p>Browse lost and found items without signing in.</p>
                    </div>
                    <span class="auth-option-arrow">›</span>
                </a>

                <footer class="auth-footer">University of Batangas | UB FIND System</footer>
            </div>
        </section>
    </main>

    <div class="auth-submit-loader" id="authSubmitLoader" hidden aria-live="assertive" aria-label="Preparing authentication">
        <section class="auth-submit-loader-card">
            <img src="background/ub.png" alt="University of Batangas seal">
            <strong>Preparing authentication</strong>
            <p>UB FIND is checking your account and sending your email code.</p>
            <div class="auth-submit-loader-track" aria-hidden="true"><span></span></div>
        </section>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('authBackBtn').addEventListener('click', () => {
            window.location.href = 'index.php?intro=done';
        });

        const loginForm = document.getElementById('loginForm');
        const loginSubmitBtn = document.getElementById('loginSubmitBtn');
        const authSubmitLoader = document.getElementById('authSubmitLoader');

        loginForm.addEventListener('submit', () => {
            authSubmitLoader.hidden = false;
            document.body.classList.add('auth-submit-loading');
            loginSubmitBtn.disabled = true;
            loginSubmitBtn.textContent = 'Preparing...';
        });
    </script>
</body>
</html>
