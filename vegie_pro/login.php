<?php
require_once 'auth.php';
if (!empty($_SESSION['user_id'])) { header('Location: index.php'); exit; }
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = trim($_POST['username'] ?? '');
    $p = $_POST['password'] ?? '';
    $stmt = $conn->prepare("SELECT id,username,password,full_name,role FROM users WHERE username=?");
    $stmt->bind_param('s', $u); $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc(); $stmt->close();
    if ($row) {
        $stored = $row['password']; $ok = false;
        if (strlen($stored) === 64 && ctype_xdigit($stored)) {
            if (hash_equals($stored, hash('sha256', $p))) {
                $ok = true;
                $new = password_hash($p, PASSWORD_DEFAULT);
                $up = $conn->prepare("UPDATE users SET password=? WHERE id=?");
                $up->bind_param('si', $new, $row['id']); $up->execute(); $up->close();
            }
        } else { $ok = password_verify($p, $stored); }
        if ($ok) {
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['full_name'] = $row['full_name'] ?: $row['username'];
            $_SESSION['role'] = $row['role'];
            header('Location: index.php'); exit;
        }
    }
    $err = 'passwrod incorrect.';
}
?>
<!DOCTYPE html>
<html lang="si">
<head>
<meta charset="UTF-8">
<title>Login | Vegie Pro</title>
<link rel="stylesheet" href="style.css">
</head>
<body class="login-body">
<form method="POST" class="login-card" autocomplete="off">
    <h1>🥦 VEGIE PRO</h1>
    <p class="sub">Vegetable Shop Management System</p>
    <?php if ($err): ?><div class="alert alert-error shake"><?= e($err) ?></div><?php endif; ?>
    <input type="text" name="username" placeholder="Username" required autofocus>
    <div class="pwd-wrap">
        <input type="password" name="password" placeholder="Password" required>
        <button type="button" class="pwd-toggle">👁</button>
    </div>
    <button class="btn" type="submit">🚀 Login</button>
    <p class="hint"> hi </b></p>
</form>
<script src="app.js"></script>
</body>
</html>