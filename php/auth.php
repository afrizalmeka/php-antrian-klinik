<?php
require_once __DIR__ . '/../config.php';
// BUG 1: requireLogin menggunakan isset bukan empty — nilai 0 lolos
function requireLogin(): void {
    if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
}
function requireAdmin(): void {
    requireLogin();
    if ($_SESSION['user_role'] !== 'admin') { header('Location: index.php'); exit; }
}
