<?php
session_start();

function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}

function getRole()
{
    return $_SESSION['role'] ?? null;
}

function requireRole($role)
{
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
    if (getRole() !== $role) {
        header('Location: index.php?error=unauthorized');
        exit();
    }
}

function requireLogin()
{
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

function logout()
{
    session_destroy();
    header('Location: login.php');
    exit();
}
?>