<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database/init.php';
initDatabase(getDB());
if (!empty($_SESSION['user_id'])) { header('Location: index.php'); exit; }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if ($user && password_verify($pass, $user['password'])) {
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];
        header('Location: index.php'); exit;
    } else { $error = 'Email atau password salah.'; }
}
?>
<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Masuk — KliniKu</title><link rel="stylesheet" href="css/style.css"></head><body>
<div class="auth-wrapper"><div class="auth-card">
    <h2>🏥 KliniKu</h2>
    <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <form method="post">
        <div class="form-group"><label>Email</label><input type="text" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" autofocus></div>
        <div class="form-group"><label>Password</label><input type="password" name="password"></div>
        <button type="submit" class="btn btn-primary" style="width:100%">Masuk</button>
    </form>
    <div class="auth-footer"><small style="color:#aaa;">Demo: admin@kliniku.com / admin123</small></div>
</div></div></body></html>
