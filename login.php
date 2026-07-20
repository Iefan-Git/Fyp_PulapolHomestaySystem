<?php
require_once 'config.php';

// Already logged in? Skip straight to the right dashboard.
if (isLoggedIn()) {
    header('Location: ' . (isAdmin() ? 'admininterface.php' : 'userinterface.php'));
    exit;
}

$error   = '';
$success = isset($_GET['registered']) ? 'Akaun berjaya didaftarkan. Sila log masuk.' : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Sila isi username dan password.';
    } else {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Regenerate session id on login to prevent session fixation.
            session_regenerate_id(true);
            $_SESSION['user_id']      = $user['id'];
            $_SESSION['username']     = $user['username'];
            $_SESSION['role']         = $user['role'];
            $_SESSION['personnel_id'] = $user['personnel_id'];

            header('Location: ' . ($user['role'] === 'admin' ? 'admininterface.php' : 'userinterface.php'));
            exit;
        } else {
            $error = 'Username atau password salah.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Log Masuk &mdash; PTK Homestay</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Oswald:wght@400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="s.css">
</head>
<body>
<div class="login-wrap">
  <div class="login-card">
    <div class="badge"><?= badgeSVG() ?></div>
    <h1>Homestay Payment Ledger</h1>
    <div class="sub">PTK Collection System</div>

    <form method="post" action="login.php">
      <div class="field">
        <label>Username</label>
        <input type="text" name="username" autocomplete="username" required value="<?= e($_POST['username'] ?? '') ?>" />
      </div>
      <div class="field">
        <label>Password</label>
        <input type="password" name="password" autocomplete="current-password" required />
      </div>
      <button type="submit" class="btn btn-primary">Log Masuk</button>
      <?php if ($error): ?><div class="login-error"><?= e($error) ?></div><?php endif; ?>
      <?php if ($success): ?><div class="login-success"><?= e($success) ?></div><?php endif; ?>
    </form>

    <div class="login-hint">
      Belum ada akaun? <a href="signup.php">Daftar sebagai pengguna</a>.
      <br/>Admin ledger diurus melalui akaun admin sedia ada.
    </div>
  </div>
</div>
</body>
</html>
