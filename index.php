<?php
require_once 'auth.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$role = getRole();
if ($role === 'admin') {
    header('Location: admin.php');
} else {
    header('Location: user.php');
}
exit();
?>