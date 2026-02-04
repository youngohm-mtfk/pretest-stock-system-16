<?php
require_once 'auth.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$role = getRole();
if ($role === 'admin' || $role === 'seller') {
    header('Location: seller_dashboard.php');
} else {
    header('Location: buyer_dashboard.php');
}
exit();
?>