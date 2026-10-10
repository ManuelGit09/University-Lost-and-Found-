<?php
require 'config.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

function userUsersJsonPath() {
    return __DIR__ . '/db/json_data/users.json';
}

function userLoadUsers() {
    $path = userUsersJsonPath();
    if (!file_exists($path)) {
        return [];
    }

    $users = json_decode(file_get_contents($path), true);
    return is_array($users) ? $users : [];
}

function userCurrentJsonUser() {
    $sessionId = (int)($_SESSION['user_id'] ?? 0);
    $sessionEmail = strtolower($_SESSION['email'] ?? '');

    foreach (userLoadUsers() as $user) {
        if ($sessionId && (int)($user['id'] ?? 0) === $sessionId) {
            return $user;
        }

        if ($sessionEmail && strtolower($user['email'] ?? '') === $sessionEmail) {
            return $user;
        }
    }

    return [];
}

$userId = (int)($_SESSION['user_id'] ?? 0);
$currentUser = userCurrentJsonUser();
$userEmail = $currentUser['email'] ?? ($_SESSION['email'] ?? 'user@ub.edu.ph');
$userName = trim($currentUser['name'] ?? ($_SESSION['name'] ?? ''));
if (!$userName) {
    $userName = preg_replace('/@.*/', '', $userEmail);
    $userName = trim(str_replace(['.', '_', '-'], ' ', $userName));
}
$userName = $userName ? ucwords($userName) : 'User';
$userRole = ucwords($currentUser['role'] ?? ($_SESSION['role'] ?? 'Student'));
$userPhone = trim($currentUser['phone'] ?? '');
$userAddress = trim($currentUser['address'] ?? '');
$userDepartment = trim($currentUser['department'] ?? '');
$userYearLevel = trim($currentUser['year_level'] ?? '');
$studentNumber = trim($currentUser['student_number'] ?? '') ?: (preg_replace('/\D+/', '', $userEmail) ?: 'Not set');
$userProfilePictureUrl = getProfilePictureUrl($currentUser['profile_picture'] ?? '');
$userOnlineClass = isLoggedIn() ? 'is-online' : 'is-offline';

$itemsPath = __DIR__ . '/db/json_data/items.json';
$items = file_exists($itemsPath) ? json_decode(file_get_contents($itemsPath), true) : [];
$items = is_array($items) ? $items : [];
$userItems = array_values(array_filter($items, fn($item) => (int)($item['reporter_id'] ?? 0) === $userId));
$lostCount = count(array_filter($userItems, fn($item) => ($item['type'] ?? '') === 'lost'));
$foundCount = count(array_filter($userItems, fn($item) => ($item['type'] ?? '') === 'found'));
$currentCount = count(array_filter($userItems, fn($item) => ($item['status'] ?? 'open') === 'open'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile - UB FIND</title>
    <link rel="icon" href="favicon.ico" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/favicon-32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/favicon-16.png">
    <link rel="apple-touch-icon" href="assets/apple-touch-icon.png">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700;800;900&display=swap" rel="stylesheet">
    <link href="assets/style.css" rel="stylesheet">
</head>
<body <?= getThemeBodyAttributes('user-page') ?>>
    <aside class="main-sidebar">
        <div class="main-seal">
            <img src="background/ub.png" alt="University of Batangas seal" class="main-seal-logo">
            <span>University of Batangas</span>
            <strong>1946</strong>
        </div>

        <nav class="main-nav" aria-label="Main menu">
            <a class="main-nav-link" href="MainMenu.php">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 11 12 3l9 8v9a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1v-9Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>
                Home
            </a>
            <a class="main-nav-link" href="MainMenu.php#lostReport">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 3h9l3 3v15H6V3Z" stroke="currentColor" stroke-width="1.7"/><path d="M15 3v4h4M9 11h6M9 15h6" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                Report item
            </a>
            <a class="main-nav-link" href="status.php">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.7"/><path d="m8 12 2.5 2.5L16 9" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Status
            </a>
            <a class="main-nav-link" href="catalog.php">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="4" y="5" width="16" height="14" rx="2" stroke="currentColor" stroke-width="1.7"/><path d="M8 9h8M8 13h4M16 13l2 2" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                Catalog
            </a>
            <a class="main-nav-link active" href="user.php">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="1.7"/><path d="M4 21a8 8 0 0 1 16 0" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                User
            </a>
            <?= renderAdminSidebarLink() ?>
            <a class="main-nav-link" href="settings.php">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="3.4" stroke="currentColor" stroke-width="1.7"/><path d="M19 12a7 7 0 0 0-.1-1.2l2-1.5-2-3.4-2.4 1a7 7 0 0 0-2-1.2L14.2 3h-4.4l-.4 2.7a7 7 0 0 0-2 1.2l-2.4-1-2 3.4 2 1.5a7.2 7.2 0 0 0 0 2.4l-2 1.5 2 3.4 2.4-1a7 7 0 0 0 2 1.2l.4 2.7h4.4l.4-2.7a7 7 0 0 0 2-1.2l2.4 1 2-3.4-2-1.5c.07-.4.1-.8.1-1.2Z" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/></svg>
                Settings
            </a>
        </nav>

        <?= renderSidebarShortcuts() ?>
    </aside>

    <main class="main-menu-shell user-shell">
        <header class="main-topbar user-topbar">
            <h1>User Profile</h1>
            <div class="main-top-actions">
                <?= renderNotificationWidget() ?>
                <?= renderMessageWidget() ?>
                <?= renderAccountWidget($userName) ?>
            </div>
        </header>

        <section class="user-layout">
            <div class="user-content">
                <section class="user-profile-hero" aria-labelledby="userProfileName">
                    <div class="user-identity">
                        <div class="user-avatar" aria-hidden="true">
                            <?php if ($userProfilePictureUrl): ?>
                                <img src="<?= htmlspecialchars($userProfilePictureUrl) ?>" alt="">
                            <?php else: ?>
                                <span><?= htmlspecialchars(substr($userName, 0, 1)) ?></span>
                            <?php endif; ?>
                            <i class="<?= $userOnlineClass ?>" aria-hidden="true"></i>
                        </div>
                        <div>
                            <h2 id="userProfileName"><?= htmlspecialchars($userName) ?></h2>
                            <span class="user-role-badge"><?= htmlspecialchars($userRole) ?></span>
                            <p>Welcome back! Here's an overview of your information and activity.</p>
                        </div>
                    </div>
                    <button type="button" class="user-edit-btn" onclick="window.location.href='settings.php?edit=profile'">
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 20h9" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="m16.5 3.5 4 4L8 20H4v-4L16.5 3.5Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
                        Edit Profile
                    </button>
                </section>

                <div class="user-info-grid">
                        <section class="user-info-card">
                            <h3>
                                <span><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="8" r="3" stroke="currentColor" stroke-width="1.7"/><path d="M6 19a6 6 0 0 1 12 0" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg></span>
                                Contact Information
                            </h3>
                            <dl>
                                <div><dt><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 5h16v14H4V5Z" stroke="currentColor" stroke-width="1.7"/><path d="m4 7 8 6 8-6" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>Email</dt><dd><?= htmlspecialchars($userEmail) ?></dd></div>
                                <div><dt><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 4h3l1.5 4-2 1.2a11 11 0 0 0 5.3 5.3l1.2-2 4 1.5v3a2 2 0 0 1-2.2 2A15 15 0 0 1 4 6.2 2 2 0 0 1 6 4Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>Phone</dt><dd><?= htmlspecialchars($userPhone ?: 'Not set') ?></dd></div>
                                <div><dt><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 21s7-4.5 7-11a7 7 0 1 0-14 0c0 6.5 7 11 7 11Z" stroke="currentColor" stroke-width="1.7"/><circle cx="12" cy="10" r="2.5" stroke="currentColor" stroke-width="1.7"/></svg>Address</dt><dd><?= htmlspecialchars($userAddress ?: 'Not set') ?></dd></div>
                            </dl>
                        </section>

                        <section class="user-info-card">
                            <h3>
                                <span><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m4 9 8-4 8 4-8 4-8-4Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="M7 11v5c3 2 7 2 10 0v-5" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg></span>
                                University Information
                            </h3>
                            <dl>
                                <div><dt><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 4h10v16H7V4Z" stroke="currentColor" stroke-width="1.7"/><path d="M10 8h4M10 12h4M10 16h2" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>Course/Department</dt><dd><?= htmlspecialchars($userDepartment ?: 'Not set') ?></dd></div>
                                <div><dt><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 5h12v14H6V5Z" stroke="currentColor" stroke-width="1.7"/><path d="M9 9h6M9 13h6" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>Year Level</dt><dd><?= htmlspecialchars($userYearLevel ?: 'Not set') ?></dd></div>
                                <div><dt><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="6" y="4" width="12" height="16" rx="2" stroke="currentColor" stroke-width="1.7"/><path d="M9 9h6M9 13h3" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>Student Number</dt><dd><?= htmlspecialchars($studentNumber) ?></dd></div>
                            </dl>
                        </section>
                </div>

                <section class="user-activity">
                        <div class="user-section-title">
                            <h3>
                                <span><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 19V9M12 19V5M19 19v-7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M4 19h16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></span>
                                Activity Overview
                            </h3>
                            <select aria-label="Activity period">
                                <option>This Semester</option>
                                <option>This Month</option>
                                <option>This Year</option>
                            </select>
                        </div>
                        <div class="user-activity-grid">
                            <div class="lost">
                                <span class="user-stat-icon"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 3h7l3 3v15H7V3Z" stroke="currentColor" stroke-width="1.7"/><path d="M14 3v4h4M10 11h4M10 15h4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg></span>
                                <section><span>Total Lost Report</span><strong><?= $lostCount ?></strong><p>Reports you have submitted</p></section>
                            </div>
                            <div class="found">
                                <span class="user-stat-icon"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="8.5" stroke="currentColor" stroke-width="1.7"/><path d="m8.5 12 2.2 2.2 4.8-5" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                                <section><span>Total Found Report</span><strong><?= $foundCount ?></strong><p>Reports you have submitted</p></section>
                            </div>
                            <div class="current">
                                <span class="user-stat-icon"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="8.5" stroke="currentColor" stroke-width="1.7"/><path d="M12 7v5l3 2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                                <section><span>Current Report</span><strong><?= $currentCount ?></strong><p>Reports currently active</p></section>
                            </div>
                        </div>
                </section>
            </div>
        </section>
    </main>

    <footer class="main-footer">
        <div class="main-footer-brand">
            <img src="background/ub.png" alt="University of Batangas seal" class="main-footer-seal">
            <strong>University Of<br>Batangas</strong>
            <small>Lipa Campus</small>
        </div>
        <div class="main-footer-block">
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 21s7-4.5 7-11a7 7 0 1 0-14 0c0 6.5 7 11 7 11Z" stroke="currentColor" stroke-width="1.8"/><circle cx="12" cy="10" r="2.5" stroke="currentColor" stroke-width="1.8"/></svg>
            <span>University Of Batangas<br>Lipa City Batangas<br>Philippines</span>
        </div>
        <div class="main-footer-block">
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 5h16v14H4V5Z" stroke="currentColor" stroke-width="1.8"/><path d="m4 7 8 6 8-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
            <span>UBFind.edu.ph<br><br>www.ub.edu.ph</span>
        </div>
        <small class="main-copy">Copyright &copy; 2026 Group 1. All rights reserved.</small>
    </footer>
</body>
</html>
