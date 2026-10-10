<?php
// UB FIND Database Configuration
// Update these with your local MySQL creds (e.g., XAMPP: root, '')

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'ub_find');

define('UPLOAD_DIR', 'uploads/');
define('SESSION_NAME', 'ubfind_session');

// Role constants
define('ROLE_ADMIN', 'admin');
define('ROLE_STUDENT', 'student');
define('ROLE_GUEST', 'guest');

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// DB connection function (PDO for security)
function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
        } catch (PDOException $e) {
            die('DB Connection failed: ' . $e->getMessage());
        }
    }
    return $pdo;
}

// Check if logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Get role
function getRole() {
    return $_SESSION['role'] ?? null;
}

// Require role
function requireRole($roles) {
    $userRole = getRole();
    if (!isLoggedIn() || !in_array($userRole, (array)$roles)) {
        header('Location: login.php?error=access_denied');
        exit;
    }

    if (isCurrentUserRestricted()) {
        session_unset();
        session_destroy();
        header('Location: login.php?error=restricted');
        exit;
    }
}

// Sanitize input
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function appMaintenanceStatePath() {
    return __DIR__ . '/db/json_data/maintenance_state.json';
}

function appMaintenanceState() {
    $path = appMaintenanceStatePath();
    if (!file_exists($path)) {
        return ['active' => false];
    }

    $state = json_decode(file_get_contents($path), true);
    return is_array($state) ? array_merge(['active' => false], $state) : ['active' => false];
}

function appMaintenanceActive() {
    $state = appMaintenanceState();
    return !empty($state['active']);
}

function appSaveMaintenanceState($state) {
    return file_put_contents(appMaintenanceStatePath(), json_encode($state, JSON_PRETTY_PRINT)) !== false;
}

function appJsonUsersPath() {
    return __DIR__ . '/db/json_data/users.json';
}

function appLoadJsonUsers() {
    $path = appJsonUsersPath();
    if (!file_exists($path)) {
        return [];
    }

    $users = json_decode(file_get_contents($path), true);
    return is_array($users) ? $users : [];
}

function appSaveJsonUsers($users) {
    return file_put_contents(appJsonUsersPath(), json_encode(array_values($users), JSON_PRETTY_PRINT)) !== false;
}

function appTouchCurrentUser() {
    if (!isLoggedIn()) {
        return;
    }

    $now = time();
    if (!empty($_SESSION['last_seen_touched_at']) && ($now - (int)$_SESSION['last_seen_touched_at']) < 60) {
        return;
    }

    $sessionId = (int)($_SESSION['user_id'] ?? 0);
    $sessionEmail = strtolower($_SESSION['email'] ?? '');
    $users = appLoadJsonUsers();
    $changed = false;

    foreach ($users as &$user) {
        $matchesId = $sessionId && (int)($user['id'] ?? 0) === $sessionId;
        $matchesEmail = $sessionEmail && strtolower($user['email'] ?? '') === $sessionEmail;

        if ($matchesId || $matchesEmail) {
            $user['last_seen'] = date('c', $now);
            $changed = true;
            break;
        }
    }
    unset($user);

    if ($changed && appSaveJsonUsers($users)) {
        $_SESSION['last_seen_touched_at'] = $now;
    }
}

function appJsonDataPath($name) {
    return __DIR__ . '/db/json_data/' . $name . '.json';
}

function appLoadJsonTable($name) {
    $path = appJsonDataPath($name);
    if (!file_exists($path)) {
        return [];
    }

    $rows = json_decode(file_get_contents($path), true);
    return is_array($rows) ? $rows : [];
}

function getCurrentJsonUser() {
    $sessionId = (int)($_SESSION['user_id'] ?? 0);
    $sessionEmail = strtolower($_SESSION['email'] ?? '');

    foreach (appLoadJsonUsers() as $user) {
        if ($sessionId && (int)($user['id'] ?? 0) === $sessionId) {
            return $user;
        }

        if ($sessionEmail && strtolower($user['email'] ?? '') === $sessionEmail) {
            return $user;
        }
    }

    return [];
}

function isCurrentUserRestricted() {
    $user = getCurrentJsonUser();
    return !empty($user['restricted']);
}

function appEnforceRestriction() {
    if (!isLoggedIn() || !isCurrentUserRestricted()) {
        return;
    }

    session_unset();
    session_destroy();
    header('Location: login.php?error=restricted');
    exit;
}

function appEnforceMaintenance() {
    if (!isLoggedIn() || !appMaintenanceActive() || getRole() === ROLE_ADMIN) {
        return;
    }

    session_unset();
    session_destroy();
    header('Location: login.php?error=maintenance');
    exit;
}

function getCurrentUserPreferences() {
    $defaults = [
        'email_notifications' => true,
        'report_status_alerts' => true,
        'push_notifications' => true,
        'theme_mode' => 'light',
        'theme_color' => '#8f1624',
        'admin_privilege_animation' => false
    ];
    $user = getCurrentJsonUser();

    return array_merge($defaults, is_array($user['preferences'] ?? null) ? $user['preferences'] : []);
}

function getThemeBodyAttributes($extraClasses = '') {
    $preferences = getCurrentUserPreferences();
    $themeMode = strtolower($preferences['theme_mode'] ?? 'light');
    $themeColor = $preferences['theme_color'] ?? '#8f1624';

    if (!preg_match('/^#[0-9a-fA-F]{6}$/', $themeColor)) {
        $themeColor = '#8f1624';
    }

    $classes = trim('main-menu-page ' . $extraClasses . ($themeMode === 'dark' ? ' theme-dark' : ''));
    return 'class="' . htmlspecialchars($classes) . '" style="--settings-accent: ' . htmlspecialchars($themeColor) . ';"';
}

function getProfilePictureUrl($filename) {
    $filename = basename((string)$filename);
    if ($filename === '') {
        return '';
    }

    $path = __DIR__ . '/' . UPLOAD_DIR . $filename;
    if (!file_exists($path)) {
        return '';
    }

    return UPLOAD_DIR . rawurlencode($filename);
}

function getCurrentUserClaimUpdates($limit = 5) {
    $userId = (int)($_SESSION['user_id'] ?? 0);
    if (!$userId) {
        return [];
    }

    $claims = array_values(array_filter(appLoadJsonTable('claims'), function ($claim) use ($userId) {
        return (int)($claim['claimant_id'] ?? 0) === $userId;
    }));

    usort($claims, function ($a, $b) {
        return strcmp(
            $b['last_message_at'] ?? $b['verified_at'] ?? $b['created_at'] ?? '',
            $a['last_message_at'] ?? $a['verified_at'] ?? $a['created_at'] ?? ''
        );
    });

    return array_slice($claims, 0, $limit);
}

function canReviewClaims() {
    return getRole() === ROLE_ADMIN;
}

function hasBootedAdminPrivileges() {
    return getRole() === ROLE_ADMIN && !empty($_SESSION['admin_privileges_booted']);
}

function getClaimReviewUpdates($limit = 5) {
    $claims = appLoadJsonTable('claims');

    usort($claims, function ($a, $b) {
        return strcmp(
            $b['last_message_at'] ?? $b['verified_at'] ?? $b['created_at'] ?? '',
            $a['last_message_at'] ?? $a['verified_at'] ?? $a['created_at'] ?? ''
        );
    });

    return array_slice($claims, 0, $limit);
}

function claimItemName($claim) {
    return $claim['item']['name'] ?? $claim['name'] ?? ('Item #' . ($claim['item_id'] ?? ''));
}

function claimClaimantName($claim) {
    return $claim['claimant']['name'] ?? $claim['claimant_name'] ?? ('User #' . ($claim['claimant_id'] ?? ''));
}

function claimLatestMessage($claim) {
    $messages = $claim['messages'] ?? [];
    if (!is_array($messages) || !$messages) {
        return null;
    }

    return $messages[array_key_last($messages)];
}

function claimUpdateTitle($claim) {
    $status = strtolower($claim['status'] ?? 'pending');
    $itemName = claimItemName($claim);
    $latestMessage = claimLatestMessage($claim);

    if ($latestMessage && (int)($latestMessage['sender_id'] ?? 0) !== (int)($_SESSION['user_id'] ?? 0)) {
        return 'New message about ' . $itemName;
    }

    if ($status === 'approved') {
        return $itemName . ' is ready to claim';
    }

    if ($status === 'rejected') {
        return $itemName . ' claim was rejected';
    }

    return $itemName . ' is pending approval';
}

function claimUpdateMessage($claim) {
    $latestMessage = claimLatestMessage($claim);
    if ($latestMessage) {
        $sender = $latestMessage['sender_name'] ?? 'User';
        return $sender . ': ' . ($latestMessage['message'] ?? '');
    }

    $status = strtolower($claim['status'] ?? 'pending');
    if (!empty($claim['admin_message'])) {
        return $claim['admin_message'];
    }

    if ($status === 'approved') {
        return 'Approved. Please meet at the designated claiming area.';
    }

    if ($status === 'rejected') {
        return 'Your claim was rejected. Please contact the administrator for more details.';
    }

    return 'Your claim is waiting for admin approval.';
}

function claimReviewTitle($claim) {
    $status = strtolower($claim['status'] ?? 'pending');
    $itemName = claimItemName($claim);

    if ($status === 'approved') {
        return $itemName . ' claim was approved';
    }

    if ($status === 'rejected') {
        return $itemName . ' claim was rejected';
    }

    return 'Review claim for ' . $itemName;
}

function claimReviewMessage($claim) {
    $latestMessage = claimLatestMessage($claim);
    if ($latestMessage) {
        $sender = $latestMessage['sender_name'] ?? 'User';
        return $sender . ': ' . ($latestMessage['message'] ?? '');
    }

    $claimant = claimClaimantName($claim);
    $proof = trim((string)($claim['proof'] ?? ''));

    if ($proof !== '') {
        return 'Claimant: ' . $claimant . '. Proof: ' . $proof;
    }

    return 'Claimant: ' . $claimant . '. Proof is ready for review.';
}

function renderNotificationWidget() {
    $isReviewer = canReviewClaims();
    $updates = $isReviewer ? getClaimReviewUpdates(5) : getCurrentUserClaimUpdates(5);
    $alertUpdates = array_values(array_filter($updates, function ($claim) use ($isReviewer) {
        $status = strtolower($claim['status'] ?? 'pending');
        return $isReviewer ? $status === 'pending' : in_array($status, ['pending', 'approved'], true);
    }));
    $count = count($alertUpdates);
    $badge = $count ? '<small>' . htmlspecialchars((string)$count) . '</small>' : '';
    $items = '';

    foreach ($alertUpdates as $claim) {
        $href = $isReviewer ? 'admin.php#claim-verification' : 'status.php#claimMessagesTitle';
        $title = $isReviewer ? claimReviewTitle($claim) : claimUpdateTitle($claim);
        $message = $isReviewer ? claimReviewMessage($claim) : claimUpdateMessage($claim);
        $items .= '<a href="' . $href . '"><strong>' . htmlspecialchars($title) . '</strong><span>' . htmlspecialchars($message) . '</span></a>';
    }

    if ($items === '') {
        $items = $isReviewer
            ? '<a href="admin.php#claim-verification"><strong>No claim requests</strong><span>New pending claim requests will appear here.</span></a>'
            : '<a href="status.php"><strong>No claim alerts</strong><span>Pending approvals and ready-to-claim updates will appear here.</span></a>';
    }

    $items .= '<a href="settings.php"><strong>Notification settings</strong><span>Manage email, status, and push alerts.</span></a>';

    return <<<HTML
                <div class="notification-widget" data-notification-widget>
                    <button type="button" class="user-notification-icon" aria-label="Open notifications" aria-expanded="false">
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M18 9a6 6 0 1 0-12 0c0 7-3 7-3 7h18s-3 0-3-7Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="M10 20a2 2 0 0 0 4 0" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                        {$badge}
                    </button>
                    <div class="notification-menu" hidden>
                        <h2>Notifications</h2>
                        {$items}
                    </div>
                </div>
                <script>
                    if (!window.ubFindNotificationsReady) {
                        window.ubFindNotificationsReady = true;
                        document.addEventListener('click', (event) => {
                            document.querySelectorAll('[data-notification-widget]').forEach((widget) => {
                                const button = widget.querySelector('.user-notification-icon');
                                const menu = widget.querySelector('.notification-menu');
                                const clickedButton = button && button.contains(event.target);

                                if (clickedButton) {
                                    const isOpen = !menu.hidden;
                                    menu.hidden = isOpen;
                                    button.setAttribute('aria-expanded', String(!isOpen));
                                    return;
                                }

                                if (!widget.contains(event.target)) {
                                    menu.hidden = true;
                                    button.setAttribute('aria-expanded', 'false');
                                }
                            });
                        });

                        document.addEventListener('keydown', (event) => {
                            if (event.key === 'Escape') {
                                document.querySelectorAll('[data-notification-widget]').forEach((widget) => {
                                    widget.querySelector('.notification-menu').hidden = true;
                                    widget.querySelector('.user-notification-icon').setAttribute('aria-expanded', 'false');
                                });
                            }
                        });
                    }
                </script>
HTML;
}

function renderMessageWidget() {
    $isReviewer = canReviewClaims();
    $updates = $isReviewer ? getClaimReviewUpdates(5) : getCurrentUserClaimUpdates(5);
    $messageCount = count(array_filter($updates, function ($claim) {
        $latestMessage = claimLatestMessage($claim);
        return $latestMessage && (int)($latestMessage['sender_id'] ?? 0) !== (int)($_SESSION['user_id'] ?? 0);
    }));
    $badge = $messageCount ? '<small>' . htmlspecialchars((string)$messageCount) . '</small>' : '';
    $items = '';

    foreach ($updates as $claim) {
        $href = $isReviewer ? 'admin.php#claim-verification' : 'status.php#claimMessagesTitle';
        $title = $isReviewer ? claimReviewTitle($claim) : claimUpdateTitle($claim);
        $message = $isReviewer ? claimReviewMessage($claim) : claimUpdateMessage($claim);
        $items .= '<a href="' . $href . '"><strong>' . htmlspecialchars($title) . '</strong><span>' . htmlspecialchars($message) . '</span></a>';
    }

    if ($items === '') {
        $items = $isReviewer
            ? '<a href="admin.php#claim-verification"><strong>No claim messages</strong><span>Student and guest claim requests will appear here.</span></a>'
            : '<a href="catalog.php"><strong>No claim messages</strong><span>Admin messages about approvals and claim meetups will appear here.</span></a>';
    }

    $items .= '<a href="settings.php"><strong>Email preferences</strong><span>Manage message and notification settings.</span></a>';

    return <<<HTML
                <div class="message-widget" data-message-widget>
                    <button type="button" class="user-message-icon" aria-label="Open messages" aria-expanded="false">
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 5h16v14H4V5Z" stroke="currentColor" stroke-width="1.7"/><path d="m4 7 8 6 8-6" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                        {$badge}
                    </button>
                    <div class="message-menu" hidden>
                        <h2>Messages</h2>
                        {$items}
                    </div>
                </div>
                <script>
                    if (!window.ubFindMessagesReady) {
                        window.ubFindMessagesReady = true;
                        document.addEventListener('click', (event) => {
                            document.querySelectorAll('[data-message-widget]').forEach((widget) => {
                                const button = widget.querySelector('.user-message-icon');
                                const menu = widget.querySelector('.message-menu');
                                const clickedButton = button && button.contains(event.target);

                                if (clickedButton) {
                                    const isOpen = !menu.hidden;
                                    menu.hidden = isOpen;
                                    button.setAttribute('aria-expanded', String(!isOpen));
                                    return;
                                }

                                if (!widget.contains(event.target)) {
                                    menu.hidden = true;
                                    button.setAttribute('aria-expanded', 'false');
                                }
                            });
                        });

                        document.addEventListener('keydown', (event) => {
                            if (event.key === 'Escape') {
                                document.querySelectorAll('[data-message-widget]').forEach((widget) => {
                                    widget.querySelector('.message-menu').hidden = true;
                                    widget.querySelector('.user-message-icon').setAttribute('aria-expanded', 'false');
                                });
                            }
                        });
                    }
                </script>
HTML;
}

function renderAccountWidget($userName) {
    $user = getCurrentJsonUser();
    $profilePictureUrl = getProfilePictureUrl($user['profile_picture'] ?? '');
    $safeName = htmlspecialchars($userName ?: 'User');
    $safeInitial = htmlspecialchars(strtoupper(substr($userName ?: 'U', 0, 1)));
    $avatar = $profilePictureUrl
        ? '<span class="user-mini-avatar has-image" data-account-avatar><img src="' . htmlspecialchars($profilePictureUrl) . '" alt=""></span>'
        : '<span class="user-mini-avatar" data-account-avatar>' . $safeInitial . '</span>';

    return <<<HTML
                <div class="account-widget" data-account-widget>
                    <button type="button" class="account-menu-button" aria-label="Open account menu" aria-expanded="false">
                        {$avatar}
                        <span data-account-name>{$safeName}</span>
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m6 9 6 6 6-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                    <div class="account-menu" hidden>
                        <a href="user.php">View profile</a>
                        <a href="settings.php?edit=profile">Edit profile</a>
                        <a href="settings.php">Settings</a>
                        <a href="logout.php">Logout</a>
                    </div>
                </div>
                <script>
                    if (!window.ubFindAccountMenuReady) {
                        window.ubFindAccountMenuReady = true;
                        document.addEventListener('click', (event) => {
                            document.querySelectorAll('[data-account-widget]').forEach((widget) => {
                                const button = widget.querySelector('.account-menu-button');
                                const menu = widget.querySelector('.account-menu');
                                const clickedButton = button && button.contains(event.target);

                                if (clickedButton) {
                                    const isOpen = !menu.hidden;
                                    menu.hidden = isOpen;
                                    button.setAttribute('aria-expanded', String(!isOpen));
                                    return;
                                }

                                if (!widget.contains(event.target)) {
                                    menu.hidden = true;
                                    button.setAttribute('aria-expanded', 'false');
                                }
                            });
                        });

                        document.addEventListener('keydown', (event) => {
                            if (event.key === 'Escape') {
                                document.querySelectorAll('[data-account-widget]').forEach((widget) => {
                                    widget.querySelector('.account-menu').hidden = true;
                                    widget.querySelector('.account-menu-button').setAttribute('aria-expanded', 'false');
                                });
                            }
                        });
                    }
                </script>
HTML;
}

function renderAdminSidebarLink($active = false) {
    if (!hasBootedAdminPrivileges()) {
        return '';
    }

    $activeClass = $active ? ' active' : '';
    return <<<HTML
            <a class="main-nav-link{$activeClass}" href="admin.php">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 3 5 6v5c0 4.5 3 8.5 7 10 4-1.5 7-5.5 7-10V6l-7-3Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>
                Admin
            </a>
HTML;
}

function renderSidebarShortcuts() {
    if (!hasBootedAdminPrivileges()) {
        return <<<HTML
        <div class="main-sidebar-help main-help-card" aria-label="Lost and found office help">
            <div class="main-help-panel">
                <strong>Need Help?</strong>
                <span>Contact the Lost and Found Office</span>
                <button type="button" class="main-help-contact" data-help-center-open>
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 13v-1a7 7 0 0 1 14 0v1" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M5 13h3v5H6a1 1 0 0 1-1-1v-4ZM19 13h-3v5h2a1 1 0 0 0 1-1v-4Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M16 18c0 1.5-1.3 2.5-3 2.5h-1" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                    Contact Us
                </button>
            </div>
            <div class="main-help-box" aria-hidden="true">
                <svg viewBox="0 0 128 112" fill="none">
                    <path d="M26 47h76l-10 46H36L26 47Z" fill="#d8944b"/>
                    <path d="M26 47 64 65l38-18-16-18H42L26 47Z" fill="#efc07a"/>
                    <path d="M36 93h56l10-46-38 18-38-18 10 46Z" fill="#c57937"/>
                    <path d="M64 65v36" stroke="#9f5f2f" stroke-width="4" stroke-linecap="round"/>
                    <path d="M44 43c-1-17 10-27 20-18 7 6-1 15-8 11" stroke="#733c2d" stroke-width="5" stroke-linecap="round"/>
                    <path d="M85 42c1-16 13-22 20-13 4 6-3 12-9 8" stroke="#a05b24" stroke-width="5" stroke-linecap="round"/>
                    <path d="M53 49 47 24M75 51l6-31M62 50l-1-22" stroke="#305f53" stroke-width="6" stroke-linecap="round"/>
                    <path d="M49 28h8M77 25h8M57 32h8" stroke="#a92537" stroke-width="5" stroke-linecap="round"/>
                </svg>
            </div>
            <p>Let's keep our campus safe and organized.</p>
        </div>

        <div class="help-center-modal" data-help-center-modal hidden>
            <button type="button" class="help-center-backdrop" data-help-center-close aria-label="Close help center"></button>
            <section class="help-center-panel" role="dialog" aria-modal="true" aria-labelledby="helpCenterTitle">
                <div class="help-center-header">
                    <h2 id="helpCenterTitle">Help center</h2>
                    <button type="button" class="help-center-close" data-help-center-close aria-label="Close">&times;</button>
                </div>
                <div class="help-center-grid">
                    <a class="help-center-option" href="https://ub.edu.ph/" target="_blank" rel="noopener">
                        <span class="help-center-icon help-center-icon-book" aria-hidden="true">?</span>
                        <span><strong>Knowledge base</strong><small>Browse help articles or search for specific topics.</small></span>
                    </a>
                    <a class="help-center-option" href="mailto:lostfound@ub.edu.ph">
                        <span class="help-center-icon help-center-icon-chat" aria-hidden="true">...</span>
                        <span><strong>Student help desk</strong><small>If you're a student, post your help desk questions here.</small></span>
                    </a>
                    <a class="help-center-option" href="https://ub.edu.ph/" target="_blank" rel="noopener">
                        <span class="help-center-icon help-center-icon-news" aria-hidden="true">!</span>
                        <span><strong>Product news</strong><small>Discover the latest platform features and updates.</small></span>
                    </a>
                    <a class="help-center-option" href="https://ub.edu.ph/" target="_blank" rel="noopener">
                        <span class="help-center-icon help-center-icon-demo" aria-hidden="true">&#9654;</span>
                        <span><strong>Product demos</strong><small>Demonstrations that provide step-by-step guidance.</small></span>
                    </a>
                    <a class="help-center-option" href="https://ub.edu.ph/" target="_blank" rel="noopener">
                        <span class="help-center-icon help-center-icon-guide" aria-hidden="true">&#10003;</span>
                        <span><strong>Getting started guides</strong><small>Download or print guides to help you get started.</small></span>
                    </a>
                    <a class="help-center-option" href="https://ub.edu.ph/" target="_blank" rel="noopener">
                        <span class="help-center-icon help-center-icon-shortcuts" aria-hidden="true">#</span>
                        <span><strong>Shortcuts</strong><small>Open the University of Batangas website for quick access.</small></span>
                    </a>
                </div>
            </section>
        </div>

        <script>
            if (!window.ubFindHelpCenterReady) {
                window.ubFindHelpCenterReady = true;
                document.addEventListener('click', (event) => {
                    const modal = document.querySelector('[data-help-center-modal]');
                    if (!modal) return;

                    if (event.target.closest('[data-help-center-open]')) {
                        modal.hidden = false;
                        document.body.classList.add('help-center-open');
                        modal.querySelector('[data-help-center-close]')?.focus();
                    }

                    if (event.target.closest('[data-help-center-close]')) {
                        modal.hidden = true;
                        document.body.classList.remove('help-center-open');
                    }
                });

                document.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape') {
                        document.querySelectorAll('[data-help-center-modal]').forEach((modal) => {
                            modal.hidden = true;
                        });
                        document.body.classList.remove('help-center-open');
                    }
                });
            }
        </script>
HTML;
    }

    return <<<HTML
        <div class="main-sidebar-help main-sidebar-admin-tools">
            <button type="button" data-admin-shortcuts-open>* Shortcuts</button>
            <button type="button" data-admin-shortcuts-open>? Help</button>
        </div>

        <div class="admin-shortcuts-modal" data-admin-shortcuts-modal hidden>
            <button type="button" class="admin-shortcuts-backdrop" data-admin-shortcuts-close aria-label="Close admin shortcuts"></button>
            <section class="admin-shortcuts-card" role="dialog" aria-modal="true" aria-labelledby="adminShortcutsTitle">
                <button type="button" class="admin-shortcuts-close" data-admin-shortcuts-close aria-label="Close">&times;</button>
                <h2 id="adminShortcutsTitle">Admin Shortcuts</h2>
                <div class="admin-shortcuts-grid">
                    <a href="admin.php#admin-controls">Admin controls</a>
                    <a href="admin.php#claim-verification">Verify claims</a>
                    <a href="admin.php#claim-history">Claim logs history</a>
                    <a href="admin.php#item-history">Item history</a>
                    <a href="admin.php#user-management">User management</a>
                    <a href="admin.php#database-settings">Database settings</a>
                    <a href="admin.php#system-configuration">System configuration</a>
                    <a href="settings.php">System preferences</a>
                </div>
            </section>
        </div>

        <script>
            if (!window.ubFindAdminShortcutsReady) {
                window.ubFindAdminShortcutsReady = true;
                document.addEventListener('click', (event) => {
                    const modal = document.querySelector('[data-admin-shortcuts-modal]');
                    if (!modal) return;

                    if (event.target.closest('[data-admin-shortcuts-open]')) {
                        modal.hidden = false;
                        modal.querySelector('[data-admin-shortcuts-close]')?.focus();
                    }

                    if (event.target.closest('[data-admin-shortcuts-close]')) {
                        modal.hidden = true;
                    }
                });

                document.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape') {
                        document.querySelectorAll('[data-admin-shortcuts-modal]').forEach((modal) => {
                            modal.hidden = true;
                        });
                    }
                });
            }
        </script>
HTML;
}

appEnforceMaintenance();
appEnforceRestriction();
appTouchCurrentUser();

// Log activity
function logActivity($action) {
    if (isLoggedIn()) {
        $pdo = getDB();
        $stmt = $pdo->prepare('INSERT INTO logs (user_id, action, created_at) VALUES (?, ?, NOW())');
        $stmt->execute([$_SESSION['user_id'], $action]);
    }
}
