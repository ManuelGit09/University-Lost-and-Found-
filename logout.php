<?php
require 'config.php';

$role = strtolower($_SESSION['role'] ?? ROLE_GUEST);
$allowedRoles = [ROLE_ADMIN, ROLE_STUDENT, ROLE_GUEST];

if (($_GET['final'] ?? '') !== '1') {
    $mode = in_array($role, $allowedRoles, true) ? $role . '_logout' : 'logout';
    header('Location: loading_page.php?mode=' . rawurlencode($mode) . '&next=logout.php%3Ffinal%3D1');
    exit;
}

session_destroy();
header('Location: login.php');
exit;
?>
