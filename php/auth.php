<?php
require_once __DIR__ . '/../config.php';
function requireLogin(): void {
    if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
}
function requireAdmin(): void {
    requireLogin();
    if ($_SESSION['user_role'] !== 'admin') { header('Location: index.php'); exit; }
}
