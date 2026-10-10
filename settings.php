<?php
require 'config.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

function settingsUsersJsonPath() {
    return __DIR__ . '/db/json_data/users.json';
}

function settingsLoadUsers() {
    $path = settingsUsersJsonPath();
    if (!file_exists($path)) {
        return [];
    }

    $users = json_decode(file_get_contents($path), true);
    return is_array($users) ? $users : [];
}

function settingsSaveUsers($users) {
    return file_put_contents(settingsUsersJsonPath(), json_encode(array_values($users), JSON_PRETTY_PRINT)) !== false;
}

function settingsCurrentUserIndex($users) {
    $sessionId = (int)($_SESSION['user_id'] ?? 0);
    $sessionEmail = strtolower($_SESSION['email'] ?? '');

    foreach ($users as $index => $user) {
        if ($sessionId && (int)($user['id'] ?? 0) === $sessionId) {
            return $index;
        }

        if ($sessionEmail && strtolower($user['email'] ?? '') === $sessionEmail) {
            return $index;
        }
    }

    return null;
}

function settingsJsonResponse($success, $message, $extra = []) {
    header('Content-Type: application/json');
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $extra));
    exit;
}

function settingsSaveProfilePicture($fieldName, $currentFilename = '') {
    if (!isset($_FILES[$fieldName]) || ($_FILES[$fieldName]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return $currentFilename;
    }

    $file = $_FILES[$fieldName];
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        settingsJsonResponse(false, 'Profile picture upload failed.');
    }

    if (($file['size'] ?? 0) > 2 * 1024 * 1024) {
        settingsJsonResponse(false, 'Profile picture must be 2MB or smaller.');
    }

    $imageInfo = @getimagesize($file['tmp_name']);
    if (!$imageInfo) {
        settingsJsonResponse(false, 'Upload a valid image file.');
    }

    $mimeToExt = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp'
    ];
    $mime = $imageInfo['mime'] ?? '';
    if (!isset($mimeToExt[$mime])) {
        settingsJsonResponse(false, 'Profile picture must be JPG, PNG, GIF, or WebP.');
    }

    $uploadDir = __DIR__ . '/' . UPLOAD_DIR;
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0777, true)) {
        settingsJsonResponse(false, 'Could not prepare upload folder.');
    }

    $filename = 'profile_' . (int)($_SESSION['user_id'] ?? 0) . '_' . bin2hex(random_bytes(6)) . '.' . $mimeToExt[$mime];
    $target = $uploadDir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $target)) {
        settingsJsonResponse(false, 'Could not save profile picture.');
    }

    return $filename;
}

function settingsRemoveProfilePicture($filename) {
    if (!$filename) {
        return;
    }

    $basename = basename($filename);
    if ($basename !== $filename || strpos($basename, 'profile_') !== 0) {
        return;
    }

    $path = __DIR__ . '/' . UPLOAD_DIR . $basename;
    if (is_file($path)) {
        @unlink($path);
    }
}

function settingsCleanAddress($address) {
    return trim(preg_replace('/\s*https?:?\/?\/?.*$/i', '', (string)$address));
}

$users = settingsLoadUsers();
$currentUserIndex = settingsCurrentUserIndex($users);
$currentUser = $currentUserIndex !== null ? $users[$currentUserIndex] : [];
$defaultPreferences = [
    'email_notifications' => true,
    'report_status_alerts' => true,
    'push_notifications' => true,
    'theme_mode' => 'light',
    'theme_color' => '#8f1624',
    'admin_privilege_animation' => false
];
$preferences = array_merge($defaultPreferences, $currentUser['preferences'] ?? []);

if (($_GET['admin'] ?? '') === 'shutdown' && getRole() === ROLE_ADMIN) {
    header('Location: loading_page.php?mode=admin_shutdown&next=settings.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($currentUserIndex === null) {
        settingsJsonResponse(false, 'Current account was not found.');
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'profile') {
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = settingsCleanAddress($_POST['address'] ?? '');
        $department = trim($_POST['department'] ?? '');
        $yearLevel = trim($_POST['year_level'] ?? '');
        $studentNumber = trim($_POST['student_number'] ?? '');
        $currentProfilePicture = $users[$currentUserIndex]['profile_picture'] ?? '';
        $removeProfilePicture = ($_POST['remove_profile_picture'] ?? '0') === '1';
        $profilePicture = $removeProfilePicture
            ? ''
            : settingsSaveProfilePicture('profile_picture', $currentProfilePicture);

        if (strlen($name) < 2) {
            settingsJsonResponse(false, 'Name must be at least 2 characters.');
        }

        $users[$currentUserIndex]['name'] = $name;
        $users[$currentUserIndex]['phone'] = $phone;
        $users[$currentUserIndex]['address'] = $address;
        $users[$currentUserIndex]['department'] = $department;
        $users[$currentUserIndex]['year_level'] = $yearLevel;
        $users[$currentUserIndex]['student_number'] = $studentNumber;
        $users[$currentUserIndex]['profile_picture'] = $profilePicture;
        $users[$currentUserIndex]['updated_at'] = date('c');
        $_SESSION['name'] = $name;

        if (!settingsSaveUsers($users)) {
            settingsJsonResponse(false, 'Failed to save profile.');
        }

        if ($currentProfilePicture && $profilePicture !== $currentProfilePicture) {
            settingsRemoveProfilePicture($currentProfilePicture);
        }

        settingsJsonResponse(true, 'Profile updated.', [
            'name' => $name,
            'phone' => $phone,
            'address' => $address,
            'department' => $department,
            'year_level' => $yearLevel,
            'student_number' => $studentNumber,
            'profile_picture_url' => getProfilePictureUrl($profilePicture),
            'profile_picture_removed' => $removeProfilePicture
        ]);
    }

    if ($action === 'email') {
        $email = trim($_POST['email'] ?? '');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            settingsJsonResponse(false, 'Enter a valid email address.');
        }

        foreach ($users as $index => $user) {
            if ($index !== $currentUserIndex && strtolower($user['email'] ?? '') === strtolower($email)) {
                settingsJsonResponse(false, 'Email is already registered.');
            }
        }

        $users[$currentUserIndex]['email'] = $email;
        $users[$currentUserIndex]['updated_at'] = date('c');
        $_SESSION['email'] = $email;
        settingsSaveUsers($users)
            ? settingsJsonResponse(true, 'Email address updated.', ['email' => $email])
            : settingsJsonResponse(false, 'Failed to save email address.');
    }

    if ($action === 'password') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $passwordHash = $users[$currentUserIndex]['password'] ?? '';

        if (!$passwordHash || !password_verify($currentPassword, $passwordHash)) {
            settingsJsonResponse(false, 'Current password is incorrect.');
        }

        if (strlen($newPassword) < 6) {
            settingsJsonResponse(false, 'New password must be at least 6 characters.');
        }

        if ($newPassword !== $confirmPassword) {
            settingsJsonResponse(false, 'New passwords do not match.');
        }

        $users[$currentUserIndex]['password'] = password_hash($newPassword, PASSWORD_DEFAULT);
        $users[$currentUserIndex]['updated_at'] = date('c');
        settingsSaveUsers($users)
            ? settingsJsonResponse(true, 'Password changed.')
            : settingsJsonResponse(false, 'Failed to save password.');
    }

    if ($action === 'preferences') {
        $themeMode = strtolower($_POST['theme_mode'] ?? $defaultPreferences['theme_mode']);
        $themeColor = $_POST['theme_color'] ?? $defaultPreferences['theme_color'];

        if (!in_array($themeMode, ['light', 'dark'], true)) {
            $themeMode = 'light';
        }

        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $themeColor)) {
            $themeColor = $defaultPreferences['theme_color'];
        }

        $users[$currentUserIndex]['preferences'] = [
            'email_notifications' => ($_POST['email_notifications'] ?? '0') === '1',
            'report_status_alerts' => ($_POST['report_status_alerts'] ?? '0') === '1',
            'push_notifications' => ($_POST['push_notifications'] ?? '0') === '1',
            'theme_mode' => $themeMode,
            'theme_color' => $themeColor,
            'admin_privilege_animation' => false
        ];
        $users[$currentUserIndex]['updated_at'] = date('c');

        settingsSaveUsers($users)
            ? settingsJsonResponse(true, 'Preferences saved.', ['preferences' => $users[$currentUserIndex]['preferences']])
            : settingsJsonResponse(false, 'Failed to save preferences.');
    }

    settingsJsonResponse(false, 'Invalid settings action.');
}

$userEmail = $currentUser['email'] ?? ($_SESSION['email'] ?? 'user@ub.edu.ph');
$userName = trim($currentUser['name'] ?? ($_SESSION['name'] ?? ''));
if (!$userName) {
    $userName = preg_replace('/@.*/', '', $userEmail);
    $userName = trim(str_replace(['.', '_', '-'], ' ', $userName));
}
$userName = $userName ? ucwords($userName) : 'User';
$userRole = strtolower($_SESSION['role'] ?? ROLE_GUEST);
$roleLabel = ucwords($userRole);
$isLimitedUser = in_array($userRole, [ROLE_GUEST, ROLE_STUDENT], true);
$adminPrivilegesBooted = hasBootedAdminPrivileges();
$adminAccessWarning = match ($_GET['admin'] ?? '') {
    'required' => 'Access prevented: admin controls are restricted to administrator accounts only.',
    'boot_required' => 'Boot admin privileges before opening administrator controls.',
    default => ''
};
$userPhone = $currentUser['phone'] ?? '';
$userAddress = settingsCleanAddress($currentUser['address'] ?? '');
$userDepartment = $currentUser['department'] ?? '';
$userYearLevel = $currentUser['year_level'] ?? '';
$userStudentNumber = $currentUser['student_number'] ?? '';
$userProfilePictureUrl = getProfilePictureUrl($currentUser['profile_picture'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - UB FIND</title>
    <link rel="icon" href="favicon.ico" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/favicon-32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/favicon-16.png">
    <link rel="apple-touch-icon" href="assets/apple-touch-icon.png">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700;800;900&display=swap" rel="stylesheet">
    <link href="assets/style.css" rel="stylesheet">
</head>
<body <?= getThemeBodyAttributes('settings-page') ?>>
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
            <a class="main-nav-link" href="user.php">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="1.7"/><path d="M4 21a8 8 0 0 1 16 0" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                User
            </a>
            <?= renderAdminSidebarLink() ?>
            <a class="main-nav-link active" href="settings.php">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="3.4" stroke="currentColor" stroke-width="1.7"/><path d="M19 12a7 7 0 0 0-.1-1.2l2-1.5-2-3.4-2.4 1a7 7 0 0 0-2-1.2L14.2 3h-4.4l-.4 2.7a7 7 0 0 0-2 1.2l-2.4-1-2 3.4 2 1.5a7.2 7.2 0 0 0 0 2.4l-2 1.5 2 3.4 2.4-1a7 7 0 0 0 2 1.2l.4 2.7h4.4l.4-2.7a7 7 0 0 0 2-1.2l2.4 1 2-3.4-2-1.5c.07-.4.1-.8.1-1.2Z" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/></svg>
                Settings
            </a>
        </nav>

        <?= renderSidebarShortcuts() ?>
    </aside>

    <main class="main-menu-shell settings-shell">
        <header class="main-topbar settings-topbar">
            <h1>Settings</h1>
            <div class="main-top-actions">
                <?= renderNotificationWidget() ?>
                <?= renderMessageWidget() ?>
                <?= renderAccountWidget($userName) ?>
            </div>
        </header>

        <?php if ($isLimitedUser): ?>
            <p class="settings-limited-note"><?= htmlspecialchars($roleLabel) ?> settings are limited to account, notification, and appearance preferences.</p>
        <?php endif; ?>
        <?php if ($adminAccessWarning): ?>
            <p class="settings-access-warning"><?= htmlspecialchars($adminAccessWarning) ?></p>
        <?php endif; ?>

        <section class="settings-grid" aria-label="User settings">
            <article class="settings-card">
                <h2><span><svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="8" r="3" stroke="currentColor" stroke-width="1.7"/><path d="M6 19a6 6 0 0 1 12 0" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg></span>Account Settings</h2>
                <div class="settings-row">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M12 20h9" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/><path d="m16.5 3.5 4 4L8 20H4v-4L16.5 3.5Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>
                    <div><strong>Edit Profile</strong><small>Update your personal information</small></div>
                    <button type="button" data-settings-modal-open="profile">Edit</button>
                </div>
                <div class="settings-row">
                    <svg viewBox="0 0 24 24" fill="none"><rect x="5" y="10" width="14" height="10" rx="2" stroke="currentColor" stroke-width="1.7"/><path d="M8 10V7a4 4 0 0 1 8 0v3" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                    <div><strong>Change Password</strong><small>Update your account password</small></div>
                    <button type="button" data-settings-modal-open="password">Change</button>
                </div>
                <div class="settings-row">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M4 5h16v14H4V5Z" stroke="currentColor" stroke-width="1.7"/><path d="m4 7 8 6 8-6" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                    <div><strong>Email Address</strong><small id="settingsEmailText"><?= htmlspecialchars($userEmail) ?></small></div>
                    <button type="button" data-settings-modal-open="email">Edit</button>
                </div>
            </article>

            <article class="settings-card">
                <h2><span><svg viewBox="0 0 24 24" fill="none"><path d="M18 9a6 6 0 1 0-12 0c0 7-3 7-3 7h18s-3 0-3-7Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="M10 20a2 2 0 0 0 4 0" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg></span>Notification Settings</h2>
                <div class="settings-row">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M4 5h16v14H4V5Z" stroke="currentColor" stroke-width="1.7"/><path d="m4 7 8 6 8-6" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                    <div><strong>Email Notifications</strong><small>Receive updates via email</small></div>
                    <label class="settings-toggle"><input type="checkbox" data-settings-preference="email_notifications" <?= $preferences['email_notifications'] ? 'checked' : '' ?>><span></span></label>
                </div>
                <div class="settings-row">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M5 5h14v10H7l-2 3V5Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>
                    <div><strong>Report Status Alerts</strong><small>Get notified about your reports</small></div>
                    <label class="settings-toggle"><input type="checkbox" data-settings-preference="report_status_alerts" <?= $preferences['report_status_alerts'] ? 'checked' : '' ?>><span></span></label>
                </div>
                <div class="settings-row">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M8 5h8v14H8V5Z" stroke="currentColor" stroke-width="1.7"/><path d="M11 17h2" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                    <div><strong>Push Notifications</strong><small>Receive push notifications</small></div>
                    <label class="settings-toggle"><input type="checkbox" data-settings-preference="push_notifications" <?= $preferences['push_notifications'] ? 'checked' : '' ?>><span></span></label>
                </div>
            </article>

            <article class="settings-card">
                <h2><span><svg viewBox="0 0 24 24" fill="none"><path d="M12 3a9 9 0 1 0 9 9 6.5 6.5 0 0 1-9-9Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg></span>Appearance</h2>
                <div class="settings-row">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M12 3a9 9 0 1 0 9 9 6.5 6.5 0 0 1-9-9Z" stroke="currentColor" stroke-width="1.7"/></svg>
                    <div><strong>Theme Mode</strong><small>Choose your preferred theme</small></div>
                    <select id="settingsThemeMode" data-settings-preference="theme_mode">
                        <option value="light" <?= $preferences['theme_mode'] === 'light' ? 'selected' : '' ?>>Light Mode</option>
                        <option value="dark" <?= $preferences['theme_mode'] === 'dark' ? 'selected' : '' ?>>Dark Mode</option>
                    </select>
                </div>
                <div class="settings-row">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M12 4a8 8 0 0 0-8 8 4 4 0 0 0 4 4h1.3a1.7 1.7 0 0 1 1.7 1.7A2.3 2.3 0 0 0 13.3 20 6.7 6.7 0 0 0 20 13.3 9.3 9.3 0 0 0 12 4Z" stroke="currentColor" stroke-width="1.7"/></svg>
                    <div><strong>Theme Color</strong><small>UB default color</small></div>
                    <label class="settings-color-picker" aria-label="Theme color">
                        <input type="color" id="settingsThemeColor" data-settings-preference="theme_color" value="<?= htmlspecialchars($preferences['theme_color']) ?>">
                        <span class="settings-color-swatch"></span>
                    </label>
                </div>
            </article>

            <article class="settings-card settings-restricted-card">
                <h2><span><svg viewBox="0 0 24 24" fill="none"><path d="M12 3 5 6v5c0 4.5 3 8.5 7 10 4-1.5 7-5.5 7-10V6l-7-3Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg></span>Restricted Settings</h2>
                <?php if ($userRole === ROLE_ADMIN): ?>
                    <div class="settings-row settings-admin-privilege-row" data-admin-privileges-boot>
                        <svg viewBox="0 0 24 24" fill="none"><path d="M12 2 15 8l6 .9-4.5 4.3 1.1 6.1L12 16.4 6.4 19.3l1.1-6.1L3 8.9 9 8l3-6Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>
                        <div>
                            <strong>Admin Privileges</strong>
                            <small><?= $adminPrivilegesBooted ? 'Administrator access is currently booted' : 'Boot administrator access' ?></small>
                        </div>
                        <button type="button" id="adminPrivilegesBootButton" data-admin-booted="<?= $adminPrivilegesBooted ? '1' : '0' ?>">
                            <?= $adminPrivilegesBooted ? 'Shutdown' : 'Boot' ?>
                        </button>
                    </div>
                <?php endif; ?>
                <div class="settings-restricted-list">
                    <a href="admin.php#admin-controls">Admin controls</a>
                    <a href="admin.php#user-management">User management</a>
                    <a href="admin.php#database-settings">Database settings</a>
                    <a href="admin.php#system-configuration">System configuration</a>
                </div>
            </article>
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

    <div class="settings-toast" id="settingsToast" role="status" aria-live="polite" hidden></div>

    <div class="settings-modal" data-settings-modal="profile" hidden>
        <button type="button" class="settings-modal-backdrop" data-settings-modal-close aria-label="Close profile editor"></button>
        <form class="settings-modal-card" data-settings-form="profile" enctype="multipart/form-data">
            <button type="button" class="settings-modal-close" data-settings-modal-close aria-label="Close">&times;</button>
            <h2>Edit Profile</h2>
            <label class="settings-photo-field">Profile Picture
                <span class="settings-photo-preview" id="settingsPhotoPreview">
                    <?php if ($userProfilePictureUrl): ?>
                        <img src="<?= htmlspecialchars($userProfilePictureUrl) ?>" alt="">
                    <?php else: ?>
                        <strong><?= htmlspecialchars(substr($userName, 0, 1)) ?></strong>
                    <?php endif; ?>
                </span>
                <input type="file" name="profile_picture" id="settingsProfilePicture" accept="image/png,image/jpeg,image/gif,image/webp">
                <input type="hidden" name="remove_profile_picture" id="settingsRemoveProfilePicture" value="0">
                <button type="button" class="settings-photo-remove" id="settingsRemoveProfilePictureButton" <?= $userProfilePictureUrl ? '' : 'hidden' ?>>Remove photo</button>
            </label>
            <label>Name<input type="text" name="name" value="<?= htmlspecialchars($userName) ?>" minlength="2" required></label>
            <label>Phone<input type="tel" name="phone" value="<?= htmlspecialchars($userPhone) ?>" placeholder="Example: 09XX XXX XXXX"></label>
            <label>Address<input type="text" name="address" value="<?= htmlspecialchars($userAddress) ?>" placeholder="Your address"></label>
            <label>Course / Department<input type="text" name="department" value="<?= htmlspecialchars($userDepartment) ?>" placeholder="Example: Computer Engineering"></label>
            <label>Year Level<input type="text" name="year_level" value="<?= htmlspecialchars($userYearLevel) ?>" placeholder="Example: 3rd Year"></label>
            <label>Student / Employee Number<input type="text" name="student_number" value="<?= htmlspecialchars($userStudentNumber) ?>" placeholder="Example: 2113567"></label>
            <button type="submit">Save Profile</button>
        </form>
    </div>

    <div class="settings-modal" data-settings-modal="email" hidden>
        <button type="button" class="settings-modal-backdrop" data-settings-modal-close aria-label="Close email editor"></button>
        <form class="settings-modal-card" data-settings-form="email">
            <button type="button" class="settings-modal-close" data-settings-modal-close aria-label="Close">&times;</button>
            <h2>Edit Email Address</h2>
            <label>Email<input type="email" name="email" value="<?= htmlspecialchars($userEmail) ?>" required></label>
            <button type="submit">Save Email</button>
        </form>
    </div>

    <div class="settings-modal" data-settings-modal="password" hidden>
        <button type="button" class="settings-modal-backdrop" data-settings-modal-close aria-label="Close password editor"></button>
        <form class="settings-modal-card" data-settings-form="password">
            <button type="button" class="settings-modal-close" data-settings-modal-close aria-label="Close">&times;</button>
            <h2>Change Password</h2>
            <label>Current Password<input type="password" name="current_password" required></label>
            <label>New Password<input type="password" name="new_password" minlength="6" required></label>
            <label>Confirm New Password<input type="password" name="confirm_password" minlength="6" required></label>
            <button type="submit">Change Password</button>
        </form>
    </div>

    <script>
        const settingsToast = document.getElementById('settingsToast');
        const settingsPreferenceInputs = document.querySelectorAll('[data-settings-preference]');
        let profileInitial = <?= json_encode(strtoupper(substr($userName, 0, 1))) ?>;
        let currentProfilePictureUrl = <?= json_encode($userProfilePictureUrl) ?>;
        let settingsToastTimer;

        function showSettingsToast(message, isError = false) {
            settingsToast.textContent = message;
            settingsToast.classList.toggle('is-error', isError);
            settingsToast.hidden = false;
            clearTimeout(settingsToastTimer);
            settingsToastTimer = setTimeout(() => {
                settingsToast.hidden = true;
            }, 2600);
        }

        function openSettingsModal(name) {
            const modal = document.querySelector(`[data-settings-modal="${name}"]`);
            if (modal) {
                modal.hidden = false;
                const firstInput = modal.querySelector('input:not([type="file"])');
                if (firstInput) {
                    firstInput.focus();
                    firstInput.select();
                }
            }
        }

        function closeSettingsModal(modal) {
            modal.hidden = true;
            const form = modal.querySelector('form');
            if (form) {
                form.reset();
            }
            resetProfilePictureControls();
        }

        function renderProfileInitial() {
            const preview = document.getElementById('settingsPhotoPreview');
            const initial = document.createElement('strong');
            initial.textContent = profileInitial;
            preview.replaceChildren(initial);
        }

        function renderProfilePicture(url) {
            if (!url) {
                renderProfileInitial();
                return;
            }

            document.getElementById('settingsPhotoPreview').innerHTML = `<img src="${url}" alt="">`;
        }

        function resetProfilePictureControls() {
            const profilePictureInput = document.getElementById('settingsProfilePicture');
            const removeProfilePictureInput = document.getElementById('settingsRemoveProfilePicture');
            const removeProfilePictureButton = document.getElementById('settingsRemoveProfilePictureButton');

            if (profilePictureInput) {
                profilePictureInput.value = '';
            }

            if (removeProfilePictureInput) {
                removeProfilePictureInput.value = '0';
            }

            if (removeProfilePictureButton) {
                removeProfilePictureButton.hidden = !currentProfilePictureUrl;
            }

            renderProfilePicture(currentProfilePictureUrl);
        }

        async function postSettings(data) {
            const options = {method: 'POST'};
            if (data instanceof FormData) {
                options.body = data;
            } else {
                options.headers = {'Content-Type': 'application/x-www-form-urlencoded'};
                options.body = new URLSearchParams(data);
            }

            const response = await fetch('settings.php', {
                ...options
            });
            return response.json();
        }

        function collectPreferences() {
            const preferences = {action: 'preferences'};
            settingsPreferenceInputs.forEach((input) => {
                preferences[input.dataset.settingsPreference] = input.type === 'checkbox'
                    ? (input.checked ? '1' : '0')
                    : input.value;
            });
            return preferences;
        }

        function applyAppearance() {
            const mode = document.getElementById('settingsThemeMode').value;
            const color = document.getElementById('settingsThemeColor').value;
            document.body.classList.toggle('theme-dark', mode === 'dark');
            document.body.style.setProperty('--settings-accent', color);
        }

        async function savePreferences() {
            applyAppearance();
            const result = await postSettings(collectPreferences());
            showSettingsToast(result.message || 'Preferences saved.', !result.success);
        }

        document.querySelectorAll('[data-settings-modal-open]').forEach((button) => {
            button.addEventListener('click', () => openSettingsModal(button.dataset.settingsModalOpen));
        });

        const requestedEditor = new URLSearchParams(window.location.search).get('edit');
        if (['profile', 'email', 'password'].includes(requestedEditor)) {
            openSettingsModal(requestedEditor);
        }

        document.querySelector('[data-admin-privileges-boot]')?.addEventListener('click', (event) => {
            const button = event.currentTarget.querySelector('#adminPrivilegesBootButton');
            if (!button || button.disabled) return;

            button.disabled = true;
            const isBooted = button.dataset.adminBooted === '1';
            button.textContent = isBooted ? 'Shutting down...' : 'Booting...';
            window.location.href = isBooted
                ? 'settings.php?admin=shutdown'
                : 'loading_page.php?mode=admin&next=admin.php%23admin-controls';
        });

        document.querySelectorAll('[data-settings-modal-close]').forEach((button) => {
            button.addEventListener('click', () => closeSettingsModal(button.closest('[data-settings-modal]')));
        });

        document.querySelectorAll('[data-settings-form]').forEach((form) => {
            form.addEventListener('submit', async (event) => {
                event.preventDefault();
                const submitButton = form.querySelector('button[type="submit"]');
                const data = new FormData(form);
                data.set('action', form.dataset.settingsForm);
                submitButton.disabled = true;

                try {
                    const result = await postSettings(data);
                    showSettingsToast(result.message, !result.success);

                    if (result.success) {
                        if (result.name) {
                            profileInitial = result.name.charAt(0).toUpperCase();
                            document.querySelectorAll('[data-account-name]').forEach((node) => {
                                node.textContent = result.name;
                            });
                            if (!result.profile_picture_url) {
                                document.querySelectorAll('[data-account-avatar]').forEach((node) => {
                                    node.classList.remove('has-image');
                                    node.textContent = profileInitial;
                                });
                            }
                        }

                        if (result.profile_picture_url) {
                            document.querySelectorAll('[data-account-avatar]').forEach((node) => {
                                node.classList.add('has-image');
                                node.innerHTML = `<img src="${result.profile_picture_url}" alt="">`;
                            });
                            currentProfilePictureUrl = result.profile_picture_url;
                            renderProfilePicture(currentProfilePictureUrl);
                        } else if (result.profile_picture_removed) {
                            currentProfilePictureUrl = '';
                            renderProfileInitial();
                        }

                        if (result.email) {
                            document.getElementById('settingsEmailText').textContent = result.email;
                        }

                        closeSettingsModal(form.closest('[data-settings-modal]'));
                    }
                } catch (error) {
                    showSettingsToast('Unable to save settings right now.', true);
                } finally {
                    submitButton.disabled = false;
                }
            });
        });

        const profilePictureInput = document.getElementById('settingsProfilePicture');
        if (profilePictureInput) {
            profilePictureInput.addEventListener('change', () => {
                const file = profilePictureInput.files && profilePictureInput.files[0];
                if (!file || !file.type.startsWith('image/')) {
                    return;
                }

                document.getElementById('settingsRemoveProfilePicture').value = '0';
                document.getElementById('settingsRemoveProfilePictureButton').hidden = false;
                const previewUrl = URL.createObjectURL(file);
                document.getElementById('settingsPhotoPreview').innerHTML = `<img src="${previewUrl}" alt="">`;
            });
        }

        const removeProfilePictureButton = document.getElementById('settingsRemoveProfilePictureButton');
        if (removeProfilePictureButton) {
            removeProfilePictureButton.addEventListener('click', () => {
                document.getElementById('settingsRemoveProfilePicture').value = '1';
                document.getElementById('settingsProfilePicture').value = '';
                removeProfilePictureButton.hidden = true;
                renderProfileInitial();
            });
        }

        settingsPreferenceInputs.forEach((input) => {
            input.addEventListener('change', savePreferences);
            input.addEventListener('input', () => {
                if (input.type === 'color') {
                    applyAppearance();
                }
            });
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                document.querySelectorAll('[data-settings-modal]:not([hidden])').forEach(closeSettingsModal);
            }
        });

        applyAppearance();
    </script>
</body>
</html>
