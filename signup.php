<?php
require_once 'config.php';

if (isLoggedIn()) {
    header('Location: ' . (isAdmin() ? 'admininterface.php' : 'userinterface.php'));
    exit;
}

$error = '';
$old   = ['username' => '', 'name' => '', 'rank' => RANKS[0]];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old['username'] = trim($_POST['username'] ?? '');
    $old['name']      = trim($_POST['name'] ?? '');
    $old['rank']      = $_POST['rank'] ?? RANKS[0];
    $password         = $_POST['password'] ?? '';
    $confirm          = $_POST['confirm_password'] ?? '';

    if ($old['username'] === '' || $old['name'] === '' || $password === '' || $confirm === '') {
        $error = 'Sila lengkapkan semua ruangan.';
    } elseif (!in_array($old['rank'], RANKS, true)) {
        $error = 'Pangkat tidak sah.';
    } elseif (strlen($password) < 6) {
        $error = 'Password mesti sekurang-kurangnya 6 aksara.';
    } elseif ($password !== $confirm) {
        $error = 'Password dan pengesahan password tidak sepadan.';
    } else {
        // Check username availability
        $check = $pdo->prepare('SELECT id FROM users WHERE username = ?');
        $check->execute([$old['username']]);
        if ($check->fetch()) {
            $error = 'Username ini sudah digunakan. Sila pilih username lain.';
        } else {
            try {
                $pdo->beginTransaction();

                // Create the personnel record this account represents.
                $insPersonnel = $pdo->prepare('INSERT INTO personnel (rank_name, name) VALUES (?, ?)');
                $insPersonnel->execute([$old['rank'], mb_strtoupper($old['name'])]);
                $personnelId = (int) $pdo->lastInsertId();

                // Create the login account, always restricted to role 'user'.
                // (Admin accounts are only created by an existing admin via
                // admininterface.php — signup can never grant admin rights.)
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $insUser = $pdo->prepare(
                    'INSERT INTO users (username, password, role, personnel_id) VALUES (?, ?, ?, ?)'
                );
                $insUser->execute([$old['username'], $hash, 'user', $personnelId]);

                $pdo->commit();

                header('Location: login.php?registered=1');
                exit;
            } catch (Exception $ex) {
                $pdo->rollBack();
                $error = 'Pendaftaran gagal. Sila cuba lagi.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Daftar Akaun &mdash; PTK Homestay</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Oswald:wght@400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="s.css">
</head>
<body>
<div class="login-wrap">
  <div class="login-card">
    <div class="badge"><?= badgeSVG() ?></div>
    <h1>Daftar Akaun Baru</h1>
    <div class="sub">PTK Collection System</div>

    <div class="viewer-note">
      Akaun yang didaftar di sini hanya boleh <b>melihat</b> nama, pangkat dan bayaran
      bulanan anda sendiri. Ia tidak boleh melihat rekod anggota lain.
    </div>

    <form method="post" action="signup.php">
      <div class="field">
        <label>Nama / Call Sign</label>
        <input type="text" name="name" required value="<?= e($old['name']) ?>" placeholder="cth. TANGO" />
      </div>
      <div class="field">
        <label>Pangkat</label>
        <select name="rank" required>
          <?php foreach (RANKS as $r): ?>
            <option value="<?= e($r) ?>" <?= $r === $old['rank'] ? 'selected' : '' ?>><?= e($r) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label>Username</label>
        <input type="text" name="username" required value="<?= e($old['username']) ?>" autocomplete="username" />
      </div>
      <div class="field">
        <label>Password</label>
        <input type="password" name="password" required autocomplete="new-password" />
        <div class="hint">Sekurang-kurangnya 6 aksara.</div>
      </div>
      <div class="field">
        <label>Sahkan Password</label>
        <input type="password" name="confirm_password" required autocomplete="new-password" />
      </div>
      <button type="submit" class="btn btn-primary">Daftar</button>
      <?php if ($error): ?><div class="login-error"><?= e($error) ?></div><?php endif; ?>
    </form>

    <div class="login-hint">
      Sudah ada akaun? <a href="login.php">Log masuk di sini</a>.
    </div>
  </div>
</div>
</body>
</html>
