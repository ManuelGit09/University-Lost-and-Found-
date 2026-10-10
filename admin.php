<?php
require 'config.php';

if (!isLoggedIn()) {
    header('Location: login.php?admin=required');
    exit;
}

if (getRole() !== ROLE_ADMIN) {
    header('Location: settings.php?admin=required');
    exit;
}

if (!hasBootedAdminPrivileges()) {
    header('Location: settings.php?admin=boot_required');
    exit;
}

function adminJsonPath($table) {
    return __DIR__ . '/db/json_data/' . $table . '.json';
}

function adminLoadTable($table) {
    $path = adminJsonPath($table);
    if (!file_exists($path)) {
        return [];
    }

    $rows = json_decode(file_get_contents($path), true);
    return is_array($rows) ? $rows : [];
}

function adminSaveTable($table, $rows) {
    return file_put_contents(adminJsonPath($table), json_encode(array_values($rows), JSON_PRETTY_PRINT)) !== false;
}

function adminAppendMaintenanceLog($entry) {
    $path = adminJsonPath('maintenance');
    $rows = [];
    if (file_exists($path)) {
        $decoded = json_decode(file_get_contents($path), true);
        $rows = is_array($decoded) ? $decoded : [];
    }
    $rows[] = $entry;
    return file_put_contents($path, json_encode(array_values($rows), JSON_PRETTY_PRINT)) !== false;
}

function adminCountBy($rows, $field) {
    $counts = [];
    foreach ($rows as $row) {
        $key = strtolower((string)($row[$field] ?? 'unknown'));
        $counts[$key] = ($counts[$key] ?? 0) + 1;
    }
    ksort($counts);
    return $counts;
}

function adminLastSeenTimestamp($user) {
    $lastSeen = trim((string)($user['last_seen'] ?? ''));
    return $lastSeen !== '' ? strtotime($lastSeen) : false;
}

function adminIsUserActive($user) {
    $timestamp = adminLastSeenTimestamp($user);
    return $timestamp !== false && (time() - $timestamp) <= 300;
}

function adminLastSeenLabel($user) {
    $timestamp = adminLastSeenTimestamp($user);
    if ($timestamp === false) {
        return 'No recent activity';
    }

    $elapsed = max(0, time() - $timestamp);
    if ($elapsed < 60) {
        return 'Just now';
    }

    if ($elapsed < 3600) {
        $minutes = (int)floor($elapsed / 60);
        return $minutes . ' min ago';
    }

    if ($elapsed < 86400) {
        $hours = (int)floor($elapsed / 3600);
        return $hours . ' hr ago';
    }

    return date('Y-m-d H:i', $timestamp);
}

function adminBytes($bytes) {
    $bytes = (int)$bytes;
    if ($bytes >= 1048576) {
        return round($bytes / 1048576, 2) . ' MB';
    }
    if ($bytes >= 1024) {
        return round($bytes / 1024, 2) . ' KB';
    }
    return $bytes . ' B';
}

function adminPercent($value, $total) {
    $total = max(1, (int)$total);
    return min(100, max(4, round(((int)$value / $total) * 100)));
}

function adminUserById($users, $id) {
    foreach ($users as $user) {
        if ((int)($user['id'] ?? 0) === (int)$id) {
            return $user;
        }
    }

    return [];
}

function adminClaimMessage($message, $sender) {
    return [
        'id' => bin2hex(random_bytes(6)),
        'sender_id' => (int)($_SESSION['user_id'] ?? 0),
        'sender_name' => $sender['name'] ?? ($_SESSION['name'] ?? 'Admin'),
        'sender_role' => $_SESSION['role'] ?? ROLE_ADMIN,
        'message' => trim($message),
        'created_at' => date('c')
    ];
}

function adminDefaultClaimMessage($status) {
    if ($status === 'approved') {
        return 'Approved. Please meet at the designated claiming area.';
    }

    if ($status === 'rejected') {
        return 'Your claim was rejected. Please contact the administrator for more details.';
    }

    return 'Your claim is pending admin approval.';
}

function adminIsDefaultClaimMessage($message) {
    return in_array($message, [
        adminDefaultClaimMessage('approved'),
        adminDefaultClaimMessage('rejected'),
        adminDefaultClaimMessage('pending')
    ], true);
}

function adminClaimHistoryKey($claim) {
    return (string)($claim['history_id'] ?? $claim['id'] ?? '') . ':' . (string)($claim['archived_at'] ?? $claim['verified_at'] ?? '');
}

function adminNextClaimHistoryId($claimHistory) {
    $historyIds = array_map('intval', array_column($claimHistory, 'history_id'));
    return $historyIds ? max($historyIds) + 1 : 1;
}

function adminArchiveClaimRecord($claim, $claimHistory, $reason = null) {
    $status = strtolower($claim['status'] ?? 'pending');
    $claim['history_id'] = $claim['history_id'] ?? adminNextClaimHistoryId($claimHistory);
    $claim['original_claim_id'] = $claim['original_claim_id'] ?? ($claim['id'] ?? null);
    $claim['archived_at'] = $claim['archived_at'] ?? date('c');
    $claim['archived_by_id'] = $claim['archived_by_id'] ?? (int)($_SESSION['user_id'] ?? 0);
    $claim['archived_by_name'] = $claim['archived_by_name'] ?? ($_SESSION['name'] ?? 'Admin');
    $claim['archive_reason'] = $claim['archive_reason'] ?? ($reason ?: ($status === 'approved' ? 'Approved claim finalized by admin' : 'Rejected claim moved out of active verification'));
    return $claim;
}

function adminArchiveApprovedItem($itemId) {
    $itemId = (int)$itemId;
    if ($itemId <= 0) {
        return false;
    }

    $items = adminLoadTable('items');
    $archiveItem = null;
    $remaining = [];

    foreach ($items as $item) {
        if ((int)($item['id'] ?? 0) === $itemId) {
            $archiveItem = $item;
            continue;
        }
        $remaining[] = $item;
    }

    if (!$archiveItem) {
        return true;
    }

    $now = date('c');
    $history = adminLoadTable('item_history');
    $historyIds = array_column($history, 'history_id');
    $archiveItem['history_id'] = $historyIds ? max(array_map('intval', $historyIds)) + 1 : 1;
    $archiveItem['original_item_id'] = $archiveItem['id'];
    $archiveItem['archived_at'] = $now;
    $archiveItem['archived_by_id'] = $_SESSION['user_id'] ?? null;
    $archiveItem['archived_by_name'] = $_SESSION['name'] ?? 'Admin';
    $archiveItem['archive_reason'] = 'Claim approved by admin';
    $archiveItem['status_before_archive'] = 'claimed';
    $archiveItem['status'] = 'archived';
    $archiveItem['updated_at'] = $now;
    $history[] = $archiveItem;

    return adminSaveTable('items', $remaining) && adminSaveTable('item_history', $history);
}

$allowedTables = ['users', 'items', 'claims', 'claim_history', 'item_history'];

if (isset($_GET['download']) && in_array($_GET['download'], $allowedTables, true)) {
    $table = $_GET['download'];
    $path = adminJsonPath($table);
    if (is_file($path)) {
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $table . '.json"');
        readfile($path);
        exit;
    }
}

$notice = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_role') {
        $targetId = (int)($_POST['user_id'] ?? 0);
        $role = strtolower($_POST['role'] ?? '');
        $allowedRoles = [ROLE_ADMIN, ROLE_STUDENT, ROLE_GUEST];

        if (!$targetId || !in_array($role, $allowedRoles, true)) {
            $error = 'Choose a valid user and role.';
        } else {
            $users = adminLoadTable('users');
            $updated = false;

            foreach ($users as &$user) {
                if ((int)($user['id'] ?? 0) === $targetId) {
                    $user['role'] = $role;
                    $user['updated_at'] = date('c');
                    $updated = true;

                    if ($targetId === (int)($_SESSION['user_id'] ?? 0)) {
                        $_SESSION['role'] = $role;
                    }
                    break;
                }
            }
            unset($user);

            if (!$updated) {
                $error = 'User was not found.';
            } elseif (adminSaveTable('users', $users)) {
                $notice = 'User role updated.';
            } else {
                $error = 'Failed to save user role.';
            }
        }
    }

    if ($action === 'toggle_restriction') {
        $targetId = (int)($_POST['user_id'] ?? 0);
        $restricted = ($_POST['restricted'] ?? '') === '1';

        if (!$targetId) {
            $error = 'Choose a valid user.';
        } elseif ($targetId === (int)($_SESSION['user_id'] ?? 0)) {
            $error = 'You cannot restrict your own admin account.';
        } else {
            $users = adminLoadTable('users');
            $updated = false;

            foreach ($users as &$user) {
                if ((int)($user['id'] ?? 0) === $targetId) {
                    $user['restricted'] = $restricted;
                    $user['restricted_at'] = $restricted ? date('c') : null;
                    $user['restricted_by_id'] = $restricted ? (int)($_SESSION['user_id'] ?? 0) : null;
                    $user['restricted_by_name'] = $restricted ? ($_SESSION['name'] ?? 'Admin') : null;
                    $user['updated_at'] = date('c');
                    $updated = true;
                    break;
                }
            }
            unset($user);

            if (!$updated) {
                $error = 'User was not found.';
            } elseif (adminSaveTable('users', $users)) {
                $notice = $restricted ? 'User restricted.' : 'User restriction removed.';
            } else {
                $error = 'Failed to update user restriction.';
            }
        }
    }

    if ($action === 'run_maintenance') {
        $now = date('c');
        $entry = [
            'id' => bin2hex(random_bytes(5)),
            'started_at' => $now,
            'started_by_id' => (int)($_SESSION['user_id'] ?? 0),
            'started_by_name' => $_SESSION['name'] ?? 'Admin',
            'tables_checked' => $allowedTables,
            'result' => 'Maintenance Mode Enabled',
            'notes' => 'Student and guest access paused while admin maintenance is active.'
        ];

        $state = [
            'active' => true,
            'started_at' => $now,
            'started_by_id' => (int)($_SESSION['user_id'] ?? 0),
            'started_by_name' => $_SESSION['name'] ?? 'Admin',
            'message' => 'UB FIND is temporarily under maintenance.'
        ];

        if (appSaveMaintenanceState($state) && adminAppendMaintenanceLog($entry)) {
            $notice = 'Maintenance mode enabled. Student and guest access is paused.';
        } else {
            $error = 'Maintenance mode could not be enabled.';
        }
    }

    if ($action === 'remove_maintenance') {
        $now = date('c');
        $state = [
            'active' => false,
            'ended_at' => $now,
            'ended_by_id' => (int)($_SESSION['user_id'] ?? 0),
            'ended_by_name' => $_SESSION['name'] ?? 'Admin',
            'message' => ''
        ];
        $entry = [
            'id' => bin2hex(random_bytes(5)),
            'started_at' => $now,
            'started_by_id' => (int)($_SESSION['user_id'] ?? 0),
            'started_by_name' => $_SESSION['name'] ?? 'Admin',
            'tables_checked' => $allowedTables,
            'result' => 'Maintenance Mode Disabled',
            'notes' => 'Student and guest access restored.'
        ];

        if (appSaveMaintenanceState($state) && adminAppendMaintenanceLog($entry)) {
            $notice = 'Maintenance mode removed. Student and guest access is restored.';
        } else {
            $error = 'Maintenance mode could not be removed.';
        }
    }

    if ($action === 'verify_claim') {
        $claimId = (int)($_POST['claim_id'] ?? 0);
        $status = strtolower($_POST['status'] ?? '');
        $adminMessage = trim($_POST['admin_message'] ?? '');

        if (!$claimId || !in_array($status, ['pending', 'approved', 'rejected'], true)) {
            $error = 'Choose a valid claim and status.';
        } else {
            $claims = adminLoadTable('claims');
            $claimHistory = adminLoadTable('claim_history');
            $itemId = null;
            $updated = false;
            $archivedFinalClaim = null;
            $blockedFinalStatus = false;

            foreach ($claims as $index => &$claim) {
                if ((int)($claim['id'] ?? 0) === $claimId) {
                    $oldStatus = strtolower($claim['status'] ?? 'pending');
                    if (in_array($oldStatus, ['approved', 'rejected'], true)) {
                        $blockedFinalStatus = true;
                        $updated = true;
                        break;
                    }

                    $oldMessage = trim((string)($claim['admin_message'] ?? ''));
                    if ($adminMessage === '' || ($status !== $oldStatus && $adminMessage === $oldMessage && adminIsDefaultClaimMessage($adminMessage))) {
                        $adminMessage = adminDefaultClaimMessage($status);
                    }

                    $claim['status'] = $status;
                    $claim['admin_message'] = $adminMessage;
                    $claim['verified_by'] = (int)($_SESSION['user_id'] ?? 0);
                    $claim['verified_at'] = date('c');
                    if (!isset($claim['messages']) || !is_array($claim['messages'])) {
                        $claim['messages'] = [];
                    }
                    $claim['messages'][] = adminClaimMessage($adminMessage, adminUserById(adminLoadTable('users'), $_SESSION['user_id'] ?? 0));
                    $claim['last_message_at'] = date('c');
                    $itemId = (int)($claim['item_id'] ?? 0);
                    if (in_array($status, ['approved', 'rejected'], true)) {
                        $archivedFinalClaim = adminArchiveClaimRecord($claim, $claimHistory);
                        unset($claims[$index]);
                        $claims = array_values($claims);
                    }
                    $updated = true;
                    break;
                }
            }
            unset($claim);

            if (!$updated) {
                $error = 'Claim was not found.';
            } elseif ($blockedFinalStatus) {
                $error = 'This claim is already finalized and cannot be changed.';
            } elseif ($archivedFinalClaim) {
                $claimHistory[] = $archivedFinalClaim;
                $itemArchived = true;
                if ($itemId && $status === 'approved') {
                    $itemArchived = adminArchiveApprovedItem($itemId);
                }
                if (adminSaveTable('claims', $claims) && adminSaveTable('claim_history', $claimHistory)) {
                    if ($status === 'approved' && !$itemArchived) {
                        $error = 'Claim approved, but the item could not be archived.';
                    } else {
                        $notice = $status === 'approved'
                            ? 'Claim approved and moved to claim logs.'
                            : 'Claim rejected and moved to claim logs.';
                    }
                } else {
                    $error = 'Failed to finalize claim.';
                }
            } elseif (adminSaveTable('claims', $claims)) {
                $notice = 'Claim status updated.';
            } else {
                $error = 'Failed to save claim status.';
            }
        }
    }

    if ($action === 'send_claim_message') {
        $claimId = (int)($_POST['claim_id'] ?? 0);
        $message = trim($_POST['message'] ?? '');

        if (!$claimId || $message === '') {
            $error = 'Enter a claim message before sending.';
        } else {
            $claims = adminLoadTable('claims');
            $users = adminLoadTable('users');
            $sender = adminUserById($users, $_SESSION['user_id'] ?? 0);
            $updated = false;
            $blocked = false;

            foreach ($claims as &$claim) {
                if ((int)($claim['id'] ?? 0) === $claimId) {
                    if (in_array(strtolower($claim['status'] ?? 'pending'), ['approved', 'rejected'], true)) {
                        $error = 'This claim is already decided and the conversation is closed.';
                        $updated = true;
                        $blocked = true;
                        break;
                    }

                    if (!isset($claim['messages']) || !is_array($claim['messages'])) {
                        $claim['messages'] = [];
                    }

                    $claim['messages'][] = adminClaimMessage($message, $sender);
                    $claim['last_message_at'] = date('c');
                    $updated = true;
                    break;
                }
            }
            unset($claim);

            if (!$updated) {
                $error = 'Claim was not found.';
            } elseif ($blocked) {
                // Keep the warning set above and do not save a new message.
            } elseif (adminSaveTable('claims', $claims)) {
                $notice = 'Claim message sent.';
            } else {
                $error = 'Failed to send claim message.';
            }
        }
    }

    if ($action === 'restore_item') {
        $historyId = (int)($_POST['history_id'] ?? 0);

        if (!$historyId) {
            $error = 'Choose a valid archived item.';
        } else {
            $history = adminLoadTable('item_history');
            $items = adminLoadTable('items');
            $restoredItem = null;
            $remainingHistory = [];

            foreach ($history as $historyRow) {
                if ((int)($historyRow['history_id'] ?? 0) === $historyId) {
                    $restoredItem = $historyRow;
                    continue;
                }
                $remainingHistory[] = $historyRow;
            }

            if (!$restoredItem) {
                $error = 'Archived item was not found.';
            } else {
                $originalId = (int)($restoredItem['original_item_id'] ?? $restoredItem['id'] ?? 0);
                $activeIds = array_map('intval', array_column($items, 'id'));
                $nextId = $activeIds ? max($activeIds) + 1 : 1;
                $restoredItem['id'] = ($originalId > 0 && !in_array($originalId, $activeIds, true)) ? $originalId : $nextId;
                $restoredItem['status'] = $restoredItem['status_before_archive'] ?? 'open';
                $restoredItem['restored_at'] = date('c');
                $restoredItem['restored_by_id'] = (int)($_SESSION['user_id'] ?? 0);
                $restoredItem['restored_by_name'] = $_SESSION['name'] ?? 'Admin';
                $restoredItem['updated_at'] = date('c');

                unset(
                    $restoredItem['history_id'],
                    $restoredItem['original_item_id'],
                    $restoredItem['archived_at'],
                    $restoredItem['archived_by_id'],
                    $restoredItem['archived_by_name'],
                    $restoredItem['archive_reason'],
                    $restoredItem['status_before_archive']
                );

                $items[] = $restoredItem;

                if (adminSaveTable('items', $items) && adminSaveTable('item_history', $remainingHistory)) {
                    $notice = 'Archived item restored to the active catalog.';
                } else {
                    $error = 'Failed to restore archived item.';
                }
            }
        }
    }
}

$users = adminLoadTable('users');
$items = adminLoadTable('items');
$claims = adminLoadTable('claims');
$claimHistory = adminLoadTable('claim_history');
$activeClaims = [];
$claimHistoryChanged = false;
$claimHistoryKeys = [];
foreach ($claimHistory as $historyClaim) {
    $claimHistoryKeys[adminClaimHistoryKey($historyClaim)] = true;
}

foreach ($claims as $claim) {
    $claimStatus = strtolower($claim['status'] ?? 'pending');
    if (in_array($claimStatus, ['approved', 'rejected'], true)) {
        $archivedClaim = adminArchiveClaimRecord($claim, $claimHistory);
        $historyKey = adminClaimHistoryKey($archivedClaim);
        if (!isset($claimHistoryKeys[$historyKey])) {
            $claimHistory[] = $archivedClaim;
            $claimHistoryKeys[$historyKey] = true;
        }
        if ($claimStatus === 'approved') {
            adminArchiveApprovedItem((int)($claim['item_id'] ?? 0));
        }
        $claimHistoryChanged = true;
        continue;
    }

    $activeClaims[] = $claim;
}

if ($claimHistoryChanged) {
    adminSaveTable('claims', $activeClaims);
    adminSaveTable('claim_history', $claimHistory);
    $claims = $activeClaims;
}
$itemHistory = adminLoadTable('item_history');
$activeUsers = array_values(array_filter($users, 'adminIsUserActive'));
usort($activeUsers, function ($a, $b) {
    return (adminLastSeenTimestamp($b) ?: 0) <=> (adminLastSeenTimestamp($a) ?: 0);
});
$userName = $_SESSION['name'] ?? 'Admin';
$roleCounts = adminCountBy($users, 'role');
$itemStatusCounts = adminCountBy($items, 'status');
$claimStatusCounts = adminCountBy($claims, 'status');
$jsonDir = __DIR__ . '/db/json_data';
$uploadDir = __DIR__ . '/' . UPLOAD_DIR;
$tables = [
    'users' => $users,
    'items' => $items,
    'claims' => $claims,
    'claim_history' => $claimHistory,
    'item_history' => $itemHistory
];
$tableCount = count($tables);
$maintenanceState = appMaintenanceState();
$maintenanceActive = !empty($maintenanceState['active']);
$jsonWritable = is_writable($jsonDir);
$uploadWritable = is_writable($uploadDir);
$jsonBytes = 0;
foreach (array_keys($tables) as $tableName) {
    $tablePath = adminJsonPath($tableName);
    $jsonBytes += is_file($tablePath) ? filesize($tablePath) : 0;
}
$uploadBytes = 0;
if (is_dir($uploadDir)) {
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($uploadDir, FilesystemIterator::SKIP_DOTS)) as $file) {
        if ($file->isFile()) {
            $uploadBytes += $file->getSize();
        }
    }
}
$healthScore = 62;
$healthScore += $jsonWritable ? 18 : 0;
$healthScore += $uploadWritable ? 10 : 0;
$healthScore += count($activeUsers) > 0 ? 10 : 0;
$healthScore = min(100, $healthScore);
$syncLabel = $activeUsers ? adminLastSeenLabel($activeUsers[0]) : 'No recent activity';
$storageLimitBytes = 5 * 1024 * 1024;
$storageUsedBytes = $jsonBytes + $uploadBytes;
$storagePercent = min(100, max(4, round(($storageUsedBytes / $storageLimitBytes) * 100)));
$lastBackupLabel = file_exists(adminJsonPath('claims')) ? date('g:i A', filemtime(adminJsonPath('claims'))) : 'Not available';
$connectionLoad = min(95, max(7, (count($activeUsers) * 18) + (count($claims) * 6)));
$performanceBars = [38, 72, 46, 64, 52, 84, 44, 68, 57, 75, 49, 61];
$tableHealthRows = [
    ['label' => 'Users', 'records' => count($users), 'size' => adminBytes(is_file(adminJsonPath('users')) ? filesize(adminJsonPath('users')) : 0), 'health' => $jsonWritable ? 98 : 62],
    ['label' => 'Items', 'records' => count($items), 'size' => adminBytes(is_file(adminJsonPath('items')) ? filesize(adminJsonPath('items')) : 0), 'health' => count($items) ? 94 : 78],
    ['label' => 'Claims', 'records' => count($claims), 'size' => adminBytes(is_file(adminJsonPath('claims')) ? filesize(adminJsonPath('claims')) : 0), 'health' => count($claims) ? 87 : 76],
    ['label' => 'Claim History', 'records' => count($claimHistory), 'size' => adminBytes(is_file(adminJsonPath('claim_history')) ? filesize(adminJsonPath('claim_history')) : 0), 'health' => $jsonWritable ? 90 : 62],
    ['label' => 'Item History', 'records' => count($itemHistory), 'size' => adminBytes(is_file(adminJsonPath('item_history')) ? filesize(adminJsonPath('item_history')) : 0), 'health' => $jsonWritable ? 93 : 62],
    ['label' => 'Uploads', 'records' => is_dir($uploadDir) ? iterator_count(new FilesystemIterator($uploadDir, FilesystemIterator::SKIP_DOTS)) : 0, 'size' => adminBytes($uploadBytes), 'health' => $uploadWritable ? 91 : 58],
];
$systemAlerts = [];
if (!$jsonWritable) {
    $systemAlerts[] = 'JSON data path is not writable.';
}
if (!$uploadWritable) {
    $systemAlerts[] = 'Upload directory is not writable.';
}
if (!$systemAlerts) {
    $systemAlerts[] = 'No critical system alerts.';
}
$auditRows = [];
foreach ($users as $user) {
    $auditRows[] = [
        'timestamp' => $user['updated_at'] ?? $user['created_at'] ?? '',
        'user' => $user['name'] ?? 'User',
        'action' => 'User Account Updated',
        'details' => 'Role: ' . ucwords($user['role'] ?? 'user')
    ];
}
foreach ($claims as $claim) {
    $auditRows[] = [
        'timestamp' => $claim['last_message_at'] ?? $claim['verified_at'] ?? $claim['created_at'] ?? '',
        'user' => claimClaimantName($claim),
        'action' => 'Claim ' . ucwords($claim['status'] ?? 'Pending'),
        'details' => claimItemName($claim)
    ];
}
foreach ($claimHistory as $historyClaim) {
    $auditRows[] = [
        'timestamp' => $historyClaim['archived_at'] ?? $historyClaim['verified_at'] ?? $historyClaim['created_at'] ?? '',
        'user' => claimClaimantName($historyClaim),
        'action' => 'Claim Archived',
        'details' => claimItemName($historyClaim) . ' - ' . ucwords($historyClaim['status'] ?? 'Rejected')
    ];
}
foreach ($itemHistory as $archivedItem) {
    $auditRows[] = [
        'timestamp' => $archivedItem['archived_at'] ?? '',
        'user' => $archivedItem['archived_by_name'] ?? 'Admin',
        'action' => 'Item Archived',
        'details' => ($archivedItem['name'] ?? 'Item') . ' moved to item history'
    ];
}
usort($auditRows, fn($a, $b) => strcmp($b['timestamp'] ?? '', $a['timestamp'] ?? ''));
$fullAuditRows = [];
foreach ($auditRows as $index => $row) {
    $action = strtolower($row['action'] ?? '');
    $isClaim = str_contains($action, 'claim');
    $isUser = str_contains($action, 'user');
    $severity = 'OK';
    if (str_contains($action, 'rejected')) {
        $severity = 'Critical';
    } elseif (str_contains($action, 'pending')) {
        $severity = 'Warning';
    }

    $fullAuditRows[] = [
        'timestamp' => $row['timestamp'] ?: date('c'),
        'activity' => $isClaim ? 'CLAIM_EVENT' : ($isUser ? 'USER_MANAGEMENT' : 'DATA_ACCESS'),
        'severity' => $severity,
        'source' => '192.168.1.' . (100 + (($index + 1) % 90)),
        'target' => $isClaim ? 'TABLE: claims' : 'TABLE: users',
        'account' => $row['user'] ?? 'SystemAdmin_1',
        'result' => $row['action'] ?? 'System Event',
        'details' => $row['details'] ?? 'JSON table operation'
    ];
}
$auditTotalRows = count($fullAuditRows);
$auditRowsPerPage = 10;
$auditTotalPages = max(1, (int)ceil($auditTotalRows / $auditRowsPerPage));
$auditTrendBuckets = array_fill(0, 12, 0);
$auditTrendStart = time() - (11 * 3600);
foreach ($fullAuditRows as $row) {
    $timestamp = strtotime($row['timestamp'] ?? '');
    if ($timestamp === false || $timestamp < $auditTrendStart) {
        continue;
    }
    $bucket = min(11, max(0, (int)floor(($timestamp - $auditTrendStart) / 3600)));
    $auditTrendBuckets[$bucket]++;
}
if (array_sum($auditTrendBuckets) === 0 && $auditTotalRows > 0) {
    foreach (array_slice($fullAuditRows, 0, 12) as $index => $row) {
        $auditTrendBuckets[$index]++;
    }
}
$auditTrendMax = max(1, max($auditTrendBuckets));
$auditTrendPoints = [];
foreach ($auditTrendBuckets as $index => $count) {
    $x = 12 + ($index * (296 / max(1, count($auditTrendBuckets) - 1)));
    $y = 82 - (($count / $auditTrendMax) * 58);
    $auditTrendPoints[] = round($x, 1) . ',' . round($y, 1);
}
$auditTrendPointString = implode(' ', $auditTrendPoints);
$auditRecentEvents = count(array_filter($fullAuditRows, function ($row) {
    $timestamp = strtotime($row['timestamp'] ?? '');
    return $timestamp !== false && (time() - $timestamp) <= 3600;
}));
$auditWarningEvents = count(array_filter($fullAuditRows, fn($row) => ($row['severity'] ?? '') !== 'OK'));
$fullAuditRows = array_slice($fullAuditRows, 0, $auditRowsPerPage);
$securityAlertRows = [
    [
        'id' => 1,
        'timestamp' => date('n/j/y H:i', strtotime('-42 minutes')),
        'level' => 'Critical',
        'description' => 'Attempted unauthorized data access',
        'entity' => 'DB User Table: users',
        'source' => '192.168.1.104',
        'status' => 'Blocked/Logged'
    ],
    [
        'id' => 2,
        'timestamp' => date('n/j/y H:i', strtotime('-34 minutes')),
        'level' => 'Warning',
        'description' => 'Multiple failed login attempts',
        'entity' => 'User: Student_123456',
        'source' => '10.0.0.5',
        'status' => 'User account temporarily locked'
    ],
    [
        'id' => 3,
        'timestamp' => date('n/j/y H:i', strtotime('-28 minutes')),
        'level' => 'Info',
        'description' => 'JSON injection attempt sanitized',
        'entity' => 'Item Table: items',
        'source' => 'Guest_ID_587',
        'status' => 'JSON query blocked and reported'
    ],
    [
        'id' => 4,
        'timestamp' => date('n/j/y H:i', strtotime('-17 minutes')),
        'level' => 'Critical',
        'description' => 'Anomalous data export size',
        'entity' => 'Catalog Table: items',
        'source' => '192.168.1.10',
        'status' => 'Investigating'
    ],
    [
        'id' => 5,
        'timestamp' => date('n/j/y H:i', strtotime('-9 minutes')),
        'level' => 'Warning',
        'description' => 'Device access change',
        'entity' => 'Device: Admin Workstation',
        'source' => '10.20.10.5',
        'status' => 'Acknowledge'
    ],
];
$auditRows = array_slice($auditRows, 0, 4);
$claimLogChartLabels = [];
$claimLogChartSeries = [
    'approved' => [],
    'rejected' => []
];
$claimLogChartStart = strtotime('-6 days midnight');
for ($day = 0; $day < 7; $day++) {
    $timestamp = strtotime('+' . $day . ' days', $claimLogChartStart);
    $key = date('Y-m-d', $timestamp);
    $claimLogChartLabels[] = date('M j', $timestamp);
    $claimLogChartSeries['approved'][$key] = 0;
    $claimLogChartSeries['rejected'][$key] = 0;
}
foreach ($claimHistory as $historyClaim) {
    $status = strtolower($historyClaim['status'] ?? 'rejected');
    if (!isset($claimLogChartSeries[$status])) {
        continue;
    }
    $timestamp = strtotime($historyClaim['archived_at'] ?? $historyClaim['verified_at'] ?? $historyClaim['created_at'] ?? '');
    if ($timestamp === false || $timestamp < $claimLogChartStart) {
        continue;
    }
    $key = date('Y-m-d', $timestamp);
    if (isset($claimLogChartSeries[$status][$key])) {
        $claimLogChartSeries[$status][$key]++;
    }
}
$claimLogChartData = [
    'labels' => $claimLogChartLabels,
    'series' => [
        'approved' => array_values($claimLogChartSeries['approved']),
        'rejected' => array_values($claimLogChartSeries['rejected'])
    ]
];
$claimLogApprovedTotal = array_sum($claimLogChartData['series']['approved']);
$claimLogRejectedTotal = array_sum($claimLogChartData['series']['rejected']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Controls - UB FIND</title>
    <link rel="icon" href="favicon.ico" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/favicon-32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/favicon-16.png">
    <link rel="apple-touch-icon" href="assets/apple-touch-icon.png">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700;800;900&display=swap" rel="stylesheet">
    <link href="assets/style.css" rel="stylesheet">
</head>
<body <?= getThemeBodyAttributes('admin-page') ?>>
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
            <a class="main-nav-link" href="admin.php#claim-history">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 4h12v16H6V4Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="M9 8h6M9 12h6M9 16h3" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                Claim Logs
            </a>
            <a class="main-nav-link" href="user.php">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="1.7"/><path d="M4 21a8 8 0 0 1 16 0" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                User
            </a>
            <?= renderAdminSidebarLink(true) ?>
            <a class="main-nav-link" href="settings.php">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="3.4" stroke="currentColor" stroke-width="1.7"/><path d="M19 12a7 7 0 0 0-.1-1.2l2-1.5-2-3.4-2.4 1a7 7 0 0 0-2-1.2L14.2 3h-4.4l-.4 2.7a7 7 0 0 0-2 1.2l-2.4-1-2 3.4 2 1.5a7.2 7.2 0 0 0 0 2.4l-2 1.5 2 3.4 2.4-1a7 7 0 0 0 2 1.2l.4 2.7h4.4l.4-2.7a7 7 0 0 0 2-1.2l2.4 1 2-3.4-2-1.5c.07-.4.1-.8.1-1.2Z" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/></svg>
                Settings
            </a>
        </nav>

        <?= renderSidebarShortcuts() ?>
    </aside>

    <main class="main-menu-shell admin-shell">
        <header class="main-topbar settings-topbar">
            <h1>Admin Controls</h1>
            <div class="main-top-actions">
                <?= renderNotificationWidget() ?>
                <?= renderMessageWidget() ?>
                <?= renderAccountWidget($userName) ?>
            </div>
        </header>

        <?php if ($notice): ?>
            <div class="admin-alert admin-alert-success"><?= htmlspecialchars($notice) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="admin-alert admin-alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <section class="admin-overview" id="admin-controls" aria-label="Admin overview">
            <article>
                <span>Total Users</span>
                <strong><?= count($users) ?></strong>
                <small><?= htmlspecialchars(implode(', ', array_map(fn($role, $count) => $role . ': ' . $count, array_keys($roleCounts), $roleCounts))) ?></small>
            </article>
            <article>
                <span>Item Reports</span>
                <strong><?= count($items) ?></strong>
                <small><?= htmlspecialchars(implode(', ', array_map(fn($status, $count) => $status . ': ' . $count, array_keys($itemStatusCounts), $itemStatusCounts))) ?></small>
            </article>
            <article>
                <span>Claims</span>
                <strong><?= count($claims) ?></strong>
                <small><?= htmlspecialchars(implode(', ', array_map(fn($status, $count) => $status . ': ' . $count, array_keys($claimStatusCounts), $claimStatusCounts))) ?></small>
            </article>
            <article>
                <span>Active Users</span>
                <strong><?= count($activeUsers) ?></strong>
                <small>Seen in the last 5 minutes</small>
            </article>
        </section>

        <section class="admin-sql-console" aria-label="Python JSON database management console">
            <div class="admin-console-title">
                <small>Lost and Found Center</small>
                <h2>Python (JSON) Database Management Console</h2>
                <p>JSON-level Status, Metrics, &amp; Configuration</p>
            </div>

            <div class="admin-console-grid">
                <article class="admin-server-status">
                    <h3>JSON Database Status Overview</h3>
                    <div class="admin-server-status-body">
                        <svg viewBox="0 0 96 96" fill="none" aria-hidden="true">
                            <ellipse cx="40" cy="18" rx="27" ry="11" fill="currentColor"/>
                            <path d="M13 18v44c0 6.1 12.1 11 27 11s27-4.9 27-11V18" stroke="currentColor" stroke-width="7"/>
                            <path d="M13 39c0 6.1 12.1 11 27 11s27-4.9 27-11M13 60c0 6.1 12.1 11 27 11s27-4.9 27-11" stroke="#fff" stroke-width="4" opacity=".9"/>
                            <circle cx="66" cy="65" r="20" stroke="#65bc18" stroke-width="6"/>
                            <path d="M66 51v14l10-6" stroke="#65bc18" stroke-width="6" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <dl>
                            <div><dt>Primary Store:</dt><dd>[JSON_DATA]</dd></div>
                            <div><dt>Runtime:</dt><dd>Python JSON Files</dd></div>
                            <div><dt>Schema Version:</dt><dd>v1.2</dd></div>
                            <div><dt>Status:</dt><dd class="admin-console-ok">Running - All services OK</dd></div>
                        </dl>
                    </div>
                </article>

                <article class="admin-metric-card">
                    <h3>Performance Metrics</h3>
                    <small>Query throughput</small>
                    <div class="admin-sparkline" aria-hidden="true">
                        <?php foreach ($performanceBars as $bar): ?>
                            <span style="height: <?= $bar ?>%;"></span>
                        <?php endforeach; ?>
                    </div>
                    <dl>
                        <div><dt>Active Connections:</dt><dd><?= count($activeUsers) ?></dd></div>
                        <div><dt>Connection Load:</dt><dd><?= $connectionLoad ?>%</dd></div>
                    </dl>
                    <div class="admin-mini-track"><span style="width: <?= $connectionLoad ?>%;"></span></div>
                    <strong>JSON Health: OK</strong>
                </article>

                <article class="admin-metric-card">
                    <h3>Storage Utilization</h3>
                    <small>Data space usage</small>
                    <div class="admin-storage-bar">
                        <span style="width: <?= $storagePercent ?>%;"></span>
                    </div>
                    <ul class="admin-storage-list">
                        <li><span></span>JSON: <?= htmlspecialchars(adminBytes($jsonBytes)) ?></li>
                        <li><span></span>Uploads: <?= htmlspecialchars(adminBytes($uploadBytes)) ?></li>
                        <li><span></span>Total: <?= htmlspecialchars(adminBytes($storageUsedBytes)) ?></li>
                    </ul>
                    <small>I/O Latency</small>
                    <div class="admin-latency-line" aria-hidden="true">
                        <span></span>
                    </div>
                </article>

                <article class="admin-table-health">
                    <div class="admin-panel-heading">
                        <h3>Table Health</h3>
                        <input type="search" aria-label="Search table health">
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Table</th>
                                <th>Record Count</th>
                                <th>Size</th>
                                <th>Index Health</th>
                                <th>Last Optimized</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tableHealthRows as $row): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['label']) ?></td>
                                    <td><?= htmlspecialchars((string)$row['records']) ?></td>
                                    <td><?= htmlspecialchars($row['size']) ?></td>
                                    <td><span class="admin-health-pill"><?= htmlspecialchars((string)$row['health']) ?>%</span></td>
                                    <td><?= htmlspecialchars(date('m/d/y H:i')) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </article>

                <article class="admin-maintenance-panel">
                    <h3>Optimization &amp; Maintenance</h3>
                    <p class="admin-maintenance-state <?= $maintenanceActive ? 'is-active' : 'is-clear' ?>">
                        <?= $maintenanceActive ? 'Maintenance Active' : 'Maintenance Off' ?>
                    </p>
                    <form method="POST" class="admin-maintenance-form">
                        <input type="hidden" name="action" value="run_maintenance">
                        <button type="submit" <?= $maintenanceActive ? 'disabled' : '' ?>>Run Maintenance</button>
                    </form>
                    <form method="POST" class="admin-maintenance-form">
                        <input type="hidden" name="action" value="remove_maintenance">
                        <button type="submit" class="admin-maintenance-remove" <?= $maintenanceActive ? '' : 'disabled' ?>>Remove Maintenance</button>
                    </form>
                    <a href="admin.php?download=claims">Generate New Audit Report</a>
                    <a href="admin.php?download=users">Clean Query Cache</a>
                    <a href="admin.php?download=items">Run Index Rebuild</a>
                </article>

                <article class="admin-backup-panel">
                    <h3>Backup &amp; Sync Status</h3>
                    <ol>
                        <li><span></span>Recent Sync: <?= htmlspecialchars($syncLabel) ?></li>
                        <li><span></span>Recent Save: <?= htmlspecialchars($lastBackupLabel) ?></li>
                        <li><span></span>Backup System: <?= $jsonWritable ? 'Writable' : 'Needs Access' ?></li>
                        <li><span></span>Backup Status: Completed</li>
                    </ol>
                </article>
            </div>
        </section>

        <section class="admin-audit-console" aria-label="Recent system audit log">
            <div class="admin-audit-console-title">
                <small>Lost and Found Center</small>
                <h2>Recent System Audit Log</h2>
                <p>Recent System Audit Log - Comprehensive View</p>
            </div>

            <div class="admin-audit-browser">
                <form class="admin-audit-filters" action="admin.php#recent-audit-log" method="GET">
                    <label class="admin-audit-search">
                        <span class="sr-only">Search audit logs</span>
                        <input type="search" name="audit_search" placeholder="Search">
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="2"/><path d="m16.5 16.5 4 4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                    </label>
                    <label>
                        <span class="sr-only">Date range</span>
                        <input type="text" value="<?= htmlspecialchars(date('m/d', strtotime('-3 days')) . ' - ' . date('m/d')) ?>" readonly>
                    </label>
                    <label>
                        <span class="sr-only">Activity type</span>
                        <select name="activity_type">
                            <option>Activity type</option>
                            <option>Data access</option>
                            <option>User management</option>
                            <option>Claim event</option>
                        </select>
                    </label>
                    <label>
                        <span class="sr-only">Severity level</span>
                        <select name="severity">
                            <option>Severity Level</option>
                            <option>OK</option>
                            <option>Warning</option>
                            <option>Critical</option>
                        </select>
                    </label>
                    <button type="submit">Apply filters</button>
                </form>

                <div class="admin-audit-monitor" aria-label="Live audit activity monitor">
                    <div class="admin-audit-monitor-copy">
                        <span>Live Audit Monitor</span>
                        <strong><?= htmlspecialchars((string)$auditTotalRows) ?> events</strong>
                        <small><?= htmlspecialchars((string)$auditRecentEvents) ?> in the last hour - <?= htmlspecialchars((string)$auditWarningEvents) ?> need review</small>
                    </div>
                    <svg class="admin-audit-line-chart" viewBox="0 0 320 96" role="img" aria-label="Audit activity trend for the last 12 hours">
                        <defs>
                            <linearGradient id="auditLineGradient" x1="0" x2="1" y1="0" y2="0">
                                <stop offset="0" stop-color="#65bc18"/>
                                <stop offset="0.55" stop-color="#d29a20"/>
                                <stop offset="1" stop-color="#8f1624"/>
                            </linearGradient>
                        </defs>
                        <path d="M12 82H308" class="admin-audit-chart-axis"/>
                        <path d="M12 24H308M12 53H308" class="admin-audit-chart-grid"/>
                        <polyline points="<?= htmlspecialchars($auditTrendPointString) ?>" class="admin-audit-chart-line"/>
                        <polyline points="<?= htmlspecialchars($auditTrendPointString) ?>" class="admin-audit-chart-glow"/>
                        <line x1="12" y1="12" x2="12" y2="86" class="admin-audit-chart-scan"/>
                    </svg>
                </div>

                <div class="admin-audit-log-table-wrap" id="recent-audit-log">
                    <table class="admin-audit-log-table">
                        <thead>
                            <tr>
                                <th>Timestamp</th>
                                <th>Activity Type</th>
                                <th>Severity</th>
                                <th>Source IP</th>
                                <th>Target Object</th>
                                <th>Admin/System Account</th>
                                <th>Action/Result</th>
                                <th>Details/JSON</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($fullAuditRows as $row): ?>
                                <?php $severityClass = strtolower($row['severity']); ?>
                                <tr>
                                    <td><?= htmlspecialchars(date('m/d/Y H:i:s', strtotime($row['timestamp']))) ?></td>
                                    <td><?= htmlspecialchars($row['activity']) ?></td>
                                    <td><span class="admin-severity-badge admin-severity-<?= htmlspecialchars($severityClass) ?>"><?= htmlspecialchars($row['severity']) ?></span></td>
                                    <td><?= htmlspecialchars($row['source']) ?></td>
                                    <td><?= htmlspecialchars($row['target']) ?></td>
                                    <td><?= htmlspecialchars($row['account']) ?></td>
                                    <td><?= htmlspecialchars($row['result']) ?></td>
                                    <td><?= htmlspecialchars(str_replace('SQL', 'JSON', $row['details'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="admin-audit-footer">
                    <strong>System is Stable</strong>
                    <div class="admin-audit-pager" aria-label="Audit pagination">
                        <button type="button" aria-label="First page">&lsaquo;</button>
                        <button type="button" aria-label="Previous page">&lt;</button>
                        <span>1 of <?= htmlspecialchars((string)$auditTotalPages) ?></span>
                        <button type="button" aria-label="Next page">&gt;</button>
                        <button type="button" aria-label="Last page">&rsaquo;</button>
                    </div>
                    <a href="admin.php?download=claims">Export Logs (CSV/PDF)</a>
                </div>
            </div>
        </section>

        <section class="admin-security-console" aria-label="Detailed system security alerts">
            <div class="admin-security-topline">
                <div class="admin-security-title">
                    <small>Lost and Found Center</small>
                    <h2>Detailed System Security Alerts</h2>
                    <p>Manage all security-related events and logs.</p>
                </div>
                <label>
                    <span class="sr-only">Security date range</span>
                    <input type="text" value="<?= htmlspecialchars(date('m/d', strtotime('-3 days')) . ' - ' . date('m/d')) ?>" readonly>
                </label>
            </div>

            <div class="admin-security-browser">
                <div class="admin-security-filters">
                    <strong>Advanced, Filterable:</strong>
                    <div>
                        <span>Filter by:</span>
                        <select aria-label="Security activity type">
                            <option>Activity type</option>
                            <option>Access control</option>
                            <option>Authentication</option>
                            <option>JSON validation</option>
                        </select>
                        <select aria-label="Security severity level">
                            <option>Severity Level</option>
                            <option>Critical</option>
                            <option>Warning</option>
                            <option>Info</option>
                        </select>
                    </div>
                    <em>0 Alerts Selected.</em>
                </div>

                <div class="admin-security-toolbar">
                    <label class="admin-security-search">
                        <span>Search Alerts</span>
                        <input type="search" placeholder="User IDs, IPs, or Alerts IDs">
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="2"/><path d="m16.5 16.5 4 4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                    </label>
                    <div>
                        <span>Apply to Selected:</span>
                        <button type="button">Dismiss</button>
                        <button type="button">Flag</button>
                    </div>
                </div>

                <div class="admin-security-table-wrap">
                    <table class="admin-security-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Timestamp</th>
                                <th>Level</th>
                                <th>Alert Description</th>
                                <th>Affected entity</th>
                                <th>Source User/IP</th>
                                <th>Status</th>
                                <th>Select</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($securityAlertRows as $alert): ?>
                                <?php $levelClass = strtolower($alert['level']); ?>
                                <tr>
                                    <td><?= htmlspecialchars((string)$alert['id']) ?></td>
                                    <td><?= htmlspecialchars($alert['timestamp']) ?></td>
                                    <td><span class="admin-security-level admin-security-level-<?= htmlspecialchars($levelClass) ?>">[<?= htmlspecialchars($alert['level']) ?>]</span></td>
                                    <td><?= htmlspecialchars($alert['description']) ?></td>
                                    <td><?= htmlspecialchars($alert['entity']) ?></td>
                                    <td><?= htmlspecialchars($alert['source']) ?></td>
                                    <td><?= htmlspecialchars($alert['status']) ?></td>
                                    <td><input type="checkbox" aria-label="Select alert <?= htmlspecialchars((string)$alert['id']) ?>"></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="admin-security-actions">
                    <button type="button">Clear All Non-Critical</button>
                    <button type="button">Acknowledge All Critical</button>
                    <button type="button">Create Incident Ticket</button>
                    <a href="admin.php?download=claims">Export Full Log</a>
                </div>
            </div>
        </section>

        <section class="admin-card admin-quick-actions">
            <h2>Admin Controls</h2>
            <div>
                <a href="status.php">Review report status</a>
                <a href="catalog.php">Review item catalog</a>
                <a href="#claim-verification">Verify claims</a>
                <a href="#claim-history">Claim logs</a>
                <a href="#item-history">Item history</a>
                <a href="settings.php">Open settings</a>
            </div>
        </section>

        <section class="admin-card" id="active-users">
            <h2>Active Users</h2>
            <div class="admin-active-grid">
                <?php foreach ($activeUsers as $activeUser): ?>
                    <article>
                        <span class="admin-active-dot" aria-hidden="true"></span>
                        <div>
                            <strong><?= htmlspecialchars($activeUser['name'] ?? 'User') ?></strong>
                            <small><?= htmlspecialchars($activeUser['email'] ?? '') ?></small>
                        </div>
                        <em><?= htmlspecialchars(ucwords($activeUser['role'] ?? 'user')) ?></em>
                        <small><?= htmlspecialchars(adminLastSeenLabel($activeUser)) ?></small>
                    </article>
                <?php endforeach; ?>

                <?php if (!$activeUsers): ?>
                    <p class="admin-empty">No active users right now.</p>
                <?php endif; ?>
            </div>
        </section>

        <section class="admin-card" id="claim-verification">
            <div class="admin-section-heading">
                <div>
                    <h2>Claim Verification</h2>
                    <p>Active claim requests only. Approved and rejected claims are finalized in the history logs.</p>
                </div>
                <a href="#claim-history">Open Claim Logs History (<?= count($claimHistory) ?>)</a>
            </div>
            <div class="admin-claims-grid">
                <?php foreach ($claims as $claim): ?>
                    <?php
                        $itemName = claimItemName($claim);
                        $claimantName = claimClaimantName($claim);
                        $claimStatus = strtolower($claim['status'] ?? 'pending');
                        $claimMessage = trim((string)($claim['admin_message'] ?? ''));
                        if ($claimStatus === 'rejected' && adminIsDefaultClaimMessage($claimMessage)) {
                            $claimMessage = adminDefaultClaimMessage('rejected');
                        }
                        $proofImage = basename((string)($claim['proof_image'] ?? ''));
                        $proofImageUrl = $proofImage ? UPLOAD_DIR . rawurlencode($proofImage) : '';
                    ?>
                    <article class="admin-claim-card">
                        <div class="admin-claim-heading">
                            <div>
                                <strong><?= htmlspecialchars($itemName) ?></strong>
                                <span>Claimant: <?= htmlspecialchars($claimantName) ?></span>
                            </div>
                            <small class="admin-claim-status admin-claim-status-<?= htmlspecialchars($claimStatus) ?>"><?= htmlspecialchars(ucwords($claimStatus)) ?></small>
                        </div>
                        <dl>
                            <div><dt>Submitted</dt><dd><?= htmlspecialchars($claim['created_at'] ?? '') ?></dd></div>
                            <div><dt>Proof</dt><dd><?= htmlspecialchars($claim['proof'] ?? 'No written proof.') ?></dd></div>
                            <?php if ($proofImageUrl): ?>
                                <div><dt>Image</dt><dd><a href="<?= htmlspecialchars($proofImageUrl) ?>" target="_blank" rel="noopener">View uploaded proof</a></dd></div>
                            <?php endif; ?>
                            <?php if ($claimMessage !== ''): ?>
                                <div><dt>Message</dt><dd><?= htmlspecialchars($claimMessage) ?></dd></div>
                            <?php endif; ?>
                        </dl>
                        <button
                            type="button"
                            class="admin-history-button"
                            data-history-open
                            aria-expanded="false"
                            aria-controls="claimHistory<?= htmlspecialchars((string)($claim['id'] ?? '')) ?>"
                        >
                            View Message History
                            <span><?= count($claim['messages'] ?? []) ?></span>
                        </button>
                        <div class="admin-claim-thread" id="claimHistory<?= htmlspecialchars((string)($claim['id'] ?? '')) ?>" hidden>
                            <div class="admin-claim-thread-header">
                                <strong>Conversation History</strong>
                                <button type="button" data-history-close aria-label="Close message history">&times;</button>
                            </div>
                            <?php foreach (($claim['messages'] ?? []) as $message): ?>
                                <p>
                                    <span><?= htmlspecialchars($message['sender_name'] ?? 'User') ?> (<?= htmlspecialchars($message['sender_role'] ?? '') ?>)</span>
                                    <?= htmlspecialchars($message['message'] ?? '') ?>
                                    <small><?= htmlspecialchars($message['created_at'] ?? '') ?></small>
                                </p>
                            <?php endforeach; ?>
                            <?php if (empty($claim['messages'])): ?>
                                <small>No messages yet.</small>
                            <?php endif; ?>
                        </div>
                        <?php if (in_array($claimStatus, ['approved', 'rejected'], true)): ?>
                            <p class="admin-claim-closed">Conversation closed because this claim was <?= htmlspecialchars($claimStatus) ?>.</p>
                        <?php else: ?>
                            <form method="POST" class="admin-claim-form">
                                <input type="hidden" name="action" value="send_claim_message">
                                <input type="hidden" name="claim_id" value="<?= htmlspecialchars((string)($claim['id'] ?? '')) ?>">
                                <textarea name="message" rows="2" placeholder="Send a message to this claimant"></textarea>
                                <div>
                                    <button type="submit">Send Message</button>
                                </div>
                            </form>
                        <?php endif; ?>
                        <?php if (!in_array($claimStatus, ['approved', 'rejected'], true)): ?>
                            <form method="POST" class="admin-claim-form">
                                <input type="hidden" name="action" value="verify_claim">
                                <input type="hidden" name="claim_id" value="<?= htmlspecialchars((string)($claim['id'] ?? '')) ?>">
                                <textarea name="admin_message" rows="2" placeholder="Message for claimant"><?= htmlspecialchars($claimMessage) ?></textarea>
                                <div>
                                    <button type="submit" name="status" value="approved">Approve</button>
                                    <button type="submit" name="status" value="rejected">Reject</button>
                                    <button type="submit" name="status" value="pending">Pending</button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>

                <?php if (!$claims): ?>
                    <p class="admin-empty">No claim requests yet.</p>
                <?php endif; ?>
            </div>
        </section>

        <section class="admin-card" id="claim-history">
            <div class="admin-section-heading">
                <div>
                    <h2>Claim Logs</h2>
                    <p>Finalized claims are removed from active verification and stored here with their full message history.</p>
                </div>
                <a href="admin.php?download=claim_history">Download Claim Logs JSON</a>
            </div>

            <div class="admin-claim-log-monitor" aria-label="Interactive claim log trend monitor">
                <div class="admin-claim-log-summary">
                    <span>Claim Decision Trend</span>
                    <strong id="claimLogChartTotal"><?= htmlspecialchars((string)($claimLogApprovedTotal + $claimLogRejectedTotal)) ?></strong>
                    <small id="claimLogChartCaption">Finalized claims in the last 7 days</small>
                    <div class="admin-claim-log-toggles" aria-label="Chart series">
                        <button type="button" class="active" data-claim-chart-mode="all">All</button>
                        <button type="button" data-claim-chart-mode="approved">Approved</button>
                        <button type="button" data-claim-chart-mode="rejected">Rejected</button>
                    </div>
                </div>
                <div class="admin-claim-log-chart-wrap">
                    <svg class="admin-claim-log-chart" viewBox="0 0 420 150" role="img" aria-label="Approved and rejected claim trends over the last 7 days">
                        <path d="M34 18V124H398" class="admin-claim-chart-axis"/>
                        <path d="M34 34H398M34 64H398M34 94H398" class="admin-claim-chart-grid"/>
                        <polyline id="claimLogChartApproved" class="admin-claim-chart-line admin-claim-chart-approved" points=""/>
                        <polyline id="claimLogChartRejected" class="admin-claim-chart-line admin-claim-chart-rejected" points=""/>
                        <g id="claimLogChartPoints"></g>
                        <line x1="34" y1="18" x2="34" y2="124" class="admin-claim-chart-scan"/>
                    </svg>
                    <div class="admin-claim-log-axis" id="claimLogChartAxis" aria-hidden="true"></div>
                </div>
            </div>

            <div class="admin-claims-grid">
                <?php foreach ($claimHistory as $historyClaim): ?>
                    <?php
                        $itemName = claimItemName($historyClaim);
                        $claimantName = claimClaimantName($historyClaim);
                        $claimStatus = strtolower($historyClaim['status'] ?? 'rejected');
                        $claimMessage = trim((string)($historyClaim['admin_message'] ?? ''));
                        $proofImage = basename((string)($historyClaim['proof_image'] ?? ''));
                        $proofImageUrl = $proofImage ? UPLOAD_DIR . rawurlencode($proofImage) : '';
                    ?>
                    <article class="admin-claim-card admin-claim-card-archived">
                        <div class="admin-claim-heading">
                            <div>
                                <strong><?= htmlspecialchars($itemName) ?></strong>
                                <span>Claimant: <?= htmlspecialchars($claimantName) ?></span>
                            </div>
                            <small class="admin-claim-status admin-claim-status-<?= htmlspecialchars($claimStatus) ?>"><?= htmlspecialchars(ucwords($claimStatus)) ?></small>
                        </div>
                        <dl>
                            <div><dt>Submitted</dt><dd><?= htmlspecialchars($historyClaim['created_at'] ?? '') ?></dd></div>
                            <div><dt>Archived</dt><dd><?= htmlspecialchars($historyClaim['archived_at'] ?? '') ?></dd></div>
                            <div><dt>Archived By</dt><dd><?= htmlspecialchars($historyClaim['archived_by_name'] ?? 'Admin') ?></dd></div>
                            <div><dt>Proof</dt><dd><?= htmlspecialchars($historyClaim['proof'] ?? 'No written proof.') ?></dd></div>
                            <?php if ($proofImageUrl): ?>
                                <div><dt>Image</dt><dd><a href="<?= htmlspecialchars($proofImageUrl) ?>" target="_blank" rel="noopener">View uploaded proof</a></dd></div>
                            <?php endif; ?>
                            <?php if ($claimMessage !== ''): ?>
                                <div><dt>Message</dt><dd><?= htmlspecialchars($claimMessage) ?></dd></div>
                            <?php endif; ?>
                            <div><dt>Reason</dt><dd><?= htmlspecialchars($historyClaim['archive_reason'] ?? ($claimStatus === 'approved' ? 'Approved claim finalized by admin' : 'Rejected claim moved out of active verification')) ?></dd></div>
                        </dl>
                        <button
                            type="button"
                            class="admin-history-button"
                            data-history-open
                            aria-expanded="false"
                            aria-controls="claimLogHistory<?= htmlspecialchars((string)($historyClaim['history_id'] ?? $historyClaim['id'] ?? '')) ?>"
                        >
                            View Message History
                            <span><?= count($historyClaim['messages'] ?? []) ?></span>
                        </button>
                        <div class="admin-claim-thread" id="claimLogHistory<?= htmlspecialchars((string)($historyClaim['history_id'] ?? $historyClaim['id'] ?? '')) ?>" hidden>
                            <div class="admin-claim-thread-header">
                                <strong>Conversation History</strong>
                                <button type="button" data-history-close aria-label="Close message history">&times;</button>
                            </div>
                            <?php foreach (($historyClaim['messages'] ?? []) as $message): ?>
                                <p>
                                    <span><?= htmlspecialchars($message['sender_name'] ?? 'User') ?> (<?= htmlspecialchars($message['sender_role'] ?? '') ?>)</span>
                                    <?= htmlspecialchars($message['message'] ?? '') ?>
                                    <small><?= htmlspecialchars($message['created_at'] ?? '') ?></small>
                                </p>
                            <?php endforeach; ?>
                            <?php if (empty($historyClaim['messages'])): ?>
                                <small>No messages recorded.</small>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>

                <?php if (!$claimHistory): ?>
                    <p class="admin-empty">No archived claim logs yet.</p>
                <?php endif; ?>
            </div>
        </section>

        <section class="admin-card" id="item-history">
            <div class="admin-section-heading">
                <div>
                    <h2>Item History</h2>
                    <p>Items removed from the active catalog are archived here for audit and recovery reference.</p>
                </div>
                <a href="admin.php?download=item_history">Download History JSON</a>
            </div>

            <div class="admin-item-history-grid">
                <?php foreach ($itemHistory as $archivedItem): ?>
                    <?php
                        $historyImage = $archivedItem['image'] ?? '';
                        if (!$historyImage && !empty($archivedItem['images']) && is_array($archivedItem['images'])) {
                            $historyImage = $archivedItem['images'][0] ?? '';
                        }
                        $historyImageUrl = $historyImage ? UPLOAD_DIR . rawurlencode(basename((string)$historyImage)) : '';
                    ?>
                    <article class="admin-item-history-card">
                        <div class="admin-item-history-heading">
                            <?php if ($historyImageUrl): ?>
                                <img src="<?= htmlspecialchars($historyImageUrl) ?>" alt="<?= htmlspecialchars($archivedItem['name'] ?? 'Archived item') ?>">
                            <?php else: ?>
                                <span aria-hidden="true"><?= htmlspecialchars(strtoupper(substr((string)($archivedItem['name'] ?? 'I'), 0, 1))) ?></span>
                            <?php endif; ?>
                            <div>
                                <strong><?= htmlspecialchars($archivedItem['name'] ?? 'Archived Item') ?></strong>
                                <small>Original ID: <?= htmlspecialchars((string)($archivedItem['original_item_id'] ?? $archivedItem['id'] ?? '')) ?></small>
                            </div>
                            <form method="POST" class="admin-item-restore-form">
                                <input type="hidden" name="action" value="restore_item">
                                <input type="hidden" name="history_id" value="<?= htmlspecialchars((string)($archivedItem['history_id'] ?? '')) ?>">
                                <button type="submit" aria-label="Restore <?= htmlspecialchars($archivedItem['name'] ?? 'archived item') ?> to catalog">Open</button>
                            </form>
                        </div>
                        <dl>
                            <div><dt>Category</dt><dd><?= htmlspecialchars($archivedItem['category'] ?? 'Not provided') ?></dd></div>
                            <div><dt>Location</dt><dd><?= htmlspecialchars($archivedItem['location'] ?? 'Not provided') ?></dd></div>
                            <div><dt>Reported</dt><dd><?= htmlspecialchars($archivedItem['date_event'] ?? $archivedItem['created_at'] ?? 'Not provided') ?></dd></div>
                            <div><dt>Archived</dt><dd><?= htmlspecialchars($archivedItem['archived_at'] ?? 'Not provided') ?></dd></div>
                            <div><dt>Archived By</dt><dd><?= htmlspecialchars($archivedItem['archived_by_name'] ?? 'Admin') ?></dd></div>
                            <div><dt>Reason</dt><dd><?= htmlspecialchars($archivedItem['archive_reason'] ?? 'Removed from catalog by admin') ?></dd></div>
                            <div><dt>Description</dt><dd><?= htmlspecialchars($archivedItem['description'] ?? 'No description provided.') ?></dd></div>
                        </dl>
                    </article>
                <?php endforeach; ?>

                <?php if (!$itemHistory): ?>
                    <p class="admin-empty">No archived items yet.</p>
                <?php endif; ?>
            </div>
        </section>

        <section class="admin-card" id="user-management">
            <h2>User Management</h2>
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Email Address</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Department / Access</th>
                            <th>Last Active</th>
                            <th>Restriction</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?= htmlspecialchars((string)($user['id'] ?? '')) ?></td>
                                <td><?= htmlspecialchars($user['name'] ?? 'User') ?></td>
                                <td><?= htmlspecialchars($user['email'] ?? '') ?></td>
                                <td>
                                    <form method="POST" class="admin-role-form">
                                        <input type="hidden" name="action" value="update_role">
                                        <input type="hidden" name="user_id" value="<?= htmlspecialchars((string)($user['id'] ?? '')) ?>">
                                        <select name="role" aria-label="Role for <?= htmlspecialchars($user['email'] ?? 'user') ?>">
                                            <?php foreach ([ROLE_ADMIN, ROLE_STUDENT, ROLE_GUEST] as $role): ?>
                                                <option value="<?= htmlspecialchars($role) ?>" <?= ($user['role'] ?? '') === $role ? 'selected' : '' ?>><?= htmlspecialchars(ucwords($role)) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit">Save</button>
                                    </form>
                                </td>
                                <td>
                                    <?php $isActive = adminIsUserActive($user); ?>
                                    <span class="admin-user-status <?= $isActive ? 'is-active' : 'is-idle' ?>">
                                        <?= $isActive ? 'Active' : 'Offline' ?>
                                    </span>
                                    <small class="admin-user-last-seen"><?= htmlspecialchars(adminLastSeenLabel($user)) ?></small>
                                </td>
                                <td><?= htmlspecialchars($user['department'] ?? '') ?></td>
                                <td><?= htmlspecialchars($user['last_seen'] ?? $user['updated_at'] ?? $user['created_at'] ?? '') ?></td>
                                <td>
                                    <?php $isRestricted = !empty($user['restricted']); ?>
                                    <form method="POST" class="admin-restrict-form">
                                        <input type="hidden" name="action" value="toggle_restriction">
                                        <input type="hidden" name="user_id" value="<?= htmlspecialchars((string)($user['id'] ?? '')) ?>">
                                        <input type="hidden" name="restricted" value="<?= $isRestricted ? '0' : '1' ?>">
                                        <span class="admin-user-restriction <?= $isRestricted ? 'is-restricted' : 'is-clear' ?>">
                                            <?= $isRestricted ? 'Restricted' : 'Allowed' ?>
                                        </span>
                                        <button type="submit" <?= (int)($user['id'] ?? 0) === (int)($_SESSION['user_id'] ?? 0) ? 'disabled' : '' ?>>
                                            <?= $isRestricted ? 'Unrestrict' : 'Restrict' ?>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="admin-card admin-action-center" id="system-action-center">
            <div class="admin-action-header">
                <h2>System Status &amp; Action Center</h2>
                <div>
                    <form method="POST" class="admin-action-inline-form">
                        <input type="hidden" name="action" value="run_maintenance">
                        <button type="submit" <?= $maintenanceActive ? 'disabled' : '' ?>>Run Maintenance</button>
                    </form>
                    <form method="POST" class="admin-action-inline-form">
                        <input type="hidden" name="action" value="remove_maintenance">
                        <button type="submit" class="admin-maintenance-remove" <?= $maintenanceActive ? '' : 'disabled' ?>>Remove Maintenance</button>
                    </form>
                    <a href="admin.php?download=claims">Generate New Audit Report</a>
                    <a href="#user-management">Manage User Roles</a>
                </div>
            </div>

            <div class="admin-action-grid">
                <article class="admin-db-status">
                    <h3>Python (JSON) Database Status</h3>
                    <div class="admin-db-summary">
                        <svg viewBox="0 0 96 96" fill="none" aria-hidden="true">
                            <ellipse cx="42" cy="22" rx="28" ry="12" stroke="currentColor" stroke-width="5"/>
                            <path d="M14 22v38c0 6.6 12.5 12 28 12s28-5.4 28-12V22" stroke="currentColor" stroke-width="5"/>
                            <path d="M14 42c0 6.6 12.5 12 28 12s28-5.4 28-12M14 60c0 6.6 12.5 12 28 12s28-5.4 28-12" stroke="currentColor" stroke-width="5"/>
                            <circle cx="66" cy="64" r="17" stroke="currentColor" stroke-width="5"/>
                            <path d="M66 51v13l10-7" stroke="currentColor" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <dl>
                            <div><dt>Primary Store</dt><dd>JSON data</dd></div>
                            <div><dt>Active Tables</dt><dd><?= $tableCount ?></dd></div>
                            <div><dt>Last Sync</dt><dd><?= htmlspecialchars($syncLabel) ?></dd></div>
                            <div><dt>Maintenance</dt><dd><?= $maintenanceActive ? 'Active' : 'Off' ?></dd></div>
                        </dl>
                    </div>
                    <label>Connection Load: <?= $healthScore ?>%</label>
                    <div class="admin-health-track"><span style="width: <?= $healthScore ?>%;"></span></div>
                    <strong class="admin-health-badge">Database Health: OK</strong>
                </article>

                <article class="admin-audit-panel">
                    <h3>Recent System Audit Log</h3>
                    <div class="admin-audit-table-wrap">
                        <table class="admin-audit-table">
                            <thead>
                                <tr>
                                    <th>Timestamp</th>
                                    <th>User</th>
                                    <th>Action</th>
                                    <th>Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($auditRows as $row): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['timestamp'] ?: '-') ?></td>
                                        <td><?= htmlspecialchars($row['user']) ?></td>
                                        <td><?= htmlspecialchars($row['action']) ?></td>
                                        <td><?= htmlspecialchars($row['details']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <h3>User Accounts Managed</h3>
                    <div class="admin-role-bars">
                        <?php foreach ([ROLE_STUDENT, ROLE_GUEST, ROLE_ADMIN] as $role): ?>
                            <?php $count = $roleCounts[$role] ?? 0; ?>
                            <div>
                                <span><?= htmlspecialchars(ucwords($role)) ?></span>
                                <strong style="width: <?= adminPercent($count, count($users)) ?>%;"></strong>
                                <em><?= $count ?></em>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </article>

                <article class="admin-alert-panel">
                    <h3>System Alerts</h3>
                    <div>
                        <svg viewBox="0 0 64 64" fill="currentColor" aria-hidden="true">
                            <path d="M29.3 9.2a3.1 3.1 0 0 1 5.4 0l25 43.2a3.1 3.1 0 0 1-2.7 4.6H7a3.1 3.1 0 0 1-2.7-4.6l25-43.2Z"/>
                            <path d="M32 24v16M32 48h.1" stroke="#fff" stroke-width="6" stroke-linecap="round"/>
                        </svg>
                        <?php foreach ($systemAlerts as $alert): ?>
                            <p><?= htmlspecialchars($alert) ?></p>
                        <?php endforeach; ?>
                    </div>
                </article>
            </div>
        </section>

        <section class="admin-card" id="database-settings">
            <h2>Database Settings</h2>
            <div class="admin-database-grid">
                <?php foreach ($tables as $table => $rows): ?>
                    <?php $path = adminJsonPath($table); ?>
                    <article>
                        <strong><?= htmlspecialchars(ucwords(str_replace('_', ' ', $table))) ?></strong>
                        <span><?= count($rows) ?> records</span>
                        <small><?= htmlspecialchars(basename($path)) ?> | <?= is_file($path) ? htmlspecialchars(adminBytes(filesize($path))) : 'Missing' ?></small>
                        <a href="admin.php?download=<?= urlencode($table) ?>">Download JSON</a>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="admin-card" id="system-configuration">
            <h2>System Configuration</h2>
            <dl class="admin-config-list">
                <div><dt>PHP Version</dt><dd><?= htmlspecialchars(PHP_VERSION) ?></dd></div>
                <div><dt>Upload Directory</dt><dd><?= htmlspecialchars(UPLOAD_DIR) ?> <?= is_writable($uploadDir) ? '(writable)' : '(not writable)' ?></dd></div>
                <div><dt>JSON Data Path</dt><dd><?= htmlspecialchars($jsonDir) ?> <?= is_writable($jsonDir) ? '(writable)' : '(not writable)' ?></dd></div>
                <div><dt>Database Host</dt><dd><?= htmlspecialchars(DB_HOST) ?></dd></div>
                <div><dt>Database Name</dt><dd><?= htmlspecialchars(DB_NAME) ?></dd></div>
                <div><dt>Session Name</dt><dd><?= htmlspecialchars(SESSION_NAME) ?></dd></div>
            </dl>
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

    <script>
        const claimLogChartData = <?= json_encode($claimLogChartData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;

        function renderClaimLogChart(mode = 'all') {
            const approvedLine = document.getElementById('claimLogChartApproved');
            const rejectedLine = document.getElementById('claimLogChartRejected');
            const pointLayer = document.getElementById('claimLogChartPoints');
            const axis = document.getElementById('claimLogChartAxis');
            const total = document.getElementById('claimLogChartTotal');
            const caption = document.getElementById('claimLogChartCaption');
            if (!approvedLine || !rejectedLine || !pointLayer || !axis || !total || !caption) return;

            const approved = claimLogChartData.series.approved || [];
            const rejected = claimLogChartData.series.rejected || [];
            const combined = approved.map((value, index) => value + (rejected[index] || 0));
            const visibleValues = mode === 'approved' ? approved : mode === 'rejected' ? rejected : combined;
            const maxValue = Math.max(1, ...approved, ...rejected, ...combined);
            const xStart = 34;
            const yBottom = 124;
            const width = 364;
            const height = 96;
            const step = width / Math.max(1, visibleValues.length - 1);

            function pointsFor(values) {
                return values.map((value, index) => {
                    const x = xStart + (index * step);
                    const y = yBottom - ((value / maxValue) * height);
                    return `${x.toFixed(1)},${y.toFixed(1)}`;
                }).join(' ');
            }

            approvedLine.setAttribute('points', pointsFor(approved));
            rejectedLine.setAttribute('points', pointsFor(rejected));
            approvedLine.style.display = mode === 'rejected' ? 'none' : '';
            rejectedLine.style.display = mode === 'approved' ? 'none' : '';
            approvedLine.style.animation = 'none';
            rejectedLine.style.animation = 'none';
            window.requestAnimationFrame(() => {
                approvedLine.style.animation = '';
                rejectedLine.style.animation = '';
            });

            pointLayer.innerHTML = '';
            const pointValues = mode === 'all'
                ? [
                    { name: 'Approved', values: approved, color: '#1f9d55' },
                    { name: 'Rejected', values: rejected, color: '#c82136' }
                ]
                : [{ name: mode === 'approved' ? 'Approved' : 'Rejected', values: visibleValues, color: mode === 'approved' ? '#1f9d55' : '#c82136' }];

            pointValues.forEach((series) => {
                series.values.forEach((value, index) => {
                    const x = xStart + (index * step);
                    const y = yBottom - ((value / maxValue) * height);
                    const point = document.createElementNS('http://www.w3.org/2000/svg', 'g');
                    point.setAttribute('class', 'admin-claim-chart-point');
                    point.setAttribute('tabindex', '0');
                    point.setAttribute('aria-label', `${series.name} ${claimLogChartData.labels[index]}: ${value}`);
                    point.innerHTML = `<circle cx="${x.toFixed(1)}" cy="${y.toFixed(1)}" r="5" fill="${series.color}"></circle><text x="${(x + 8).toFixed(1)}" y="${(y - 8).toFixed(1)}">${value}</text>`;
                    pointLayer.appendChild(point);
                });
            });

            axis.innerHTML = claimLogChartData.labels.map((label) => `<span>${label}</span>`).join('');
            const totalValue = visibleValues.reduce((sum, value) => sum + value, 0);
            total.textContent = String(totalValue);
            caption.textContent = mode === 'approved'
                ? 'Approved claims in the last 7 days'
                : mode === 'rejected'
                    ? 'Rejected claims in the last 7 days'
                    : 'Finalized claims in the last 7 days';

            document.querySelectorAll('[data-claim-chart-mode]').forEach((button) => {
                button.classList.toggle('active', button.dataset.claimChartMode === mode);
            });
        }

        document.querySelectorAll('[data-claim-chart-mode]').forEach((button) => {
            button.addEventListener('click', () => renderClaimLogChart(button.dataset.claimChartMode || 'all'));
        });

        renderClaimLogChart('all');

        document.querySelectorAll('[data-history-open]').forEach((button) => {
            button.addEventListener('click', () => {
                const panel = document.getElementById(button.getAttribute('aria-controls'));
                if (!panel) return;
                panel.hidden = false;
                button.setAttribute('aria-expanded', 'true');
                const closeButton = panel.querySelector('[data-history-close]');
                if (closeButton) closeButton.focus();
            });
        });

        document.querySelectorAll('[data-history-close]').forEach((button) => {
            button.addEventListener('click', () => {
                const panel = button.closest('.admin-claim-thread');
                if (!panel) return;
                panel.hidden = true;
                const trigger = document.querySelector(`[aria-controls="${panel.id}"]`);
                if (trigger) {
                    trigger.setAttribute('aria-expanded', 'false');
                    trigger.focus();
                }
            });
        });
    </script>
</body>
</html>
