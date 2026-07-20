<?php
require_once 'config.php';
requireUser(); // logged in AND not an admin

$personnelId = $_SESSION['personnel_id'] ?? null;

// Selected year (defaults to current year), constrained to a sane range.
$year = isset($_GET['year']) ? (int) $_GET['year'] : (int) date('Y');
if ($year < 2000 || $year > 2100) {
    $year = (int) date('Y');
}

$person = null;
$paidMonths = [];      // month index (0-11) => bool
$paidCount  = 0;

if ($personnelId) {
    // Fetch ONLY this user's own personnel row — no other rows are ever queried.
    $stmt = $pdo->prepare('SELECT id, rank_name, name FROM personnel WHERE id = ?');
    $stmt->execute([$personnelId]);
    $person = $stmt->fetch();

    if ($person) {
        $payStmt = $pdo->prepare('SELECT month, paid FROM payments WHERE personnel_id = ? AND year = ?');
        $payStmt->execute([$personnelId, $year]);
        foreach ($payStmt->fetchAll() as $row) {
            $paidMonths[(int) $row['month']] = (bool) $row['paid'];
        }
        foreach ($paidMonths as $paid) {
            if ($paid) $paidCount++;
        }
    }
}

$monthlyFee = $person ? rank_fee($person['rank_name']) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Ledger Saya &mdash; PTK Homestay</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Oswald:wght@400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="s.css">
</head>
<body>
<div class="app">
  <div class="topbar">
    <div class="title">
      <h1>Ledger Bayaran Saya</h1>
      <small>Paparan peribadi &mdash; hanya rekod anda sendiri</small>
    </div>
    <div class="who">
      <span class="role-badge viewer">Pengguna</span>
      <span><?= e($_SESSION['username']) ?></span>
      <a href="logout.php" class="btn btn-ghost">Log Keluar</a>
    </div>
  </div>

  <div class="viewer-banner">
    <span>&#128274;</span>
    <span>Anda hanya boleh melihat maklumat anda sendiri &mdash; nama, pangkat dan bayaran bulanan. Rekod anggota lain tidak dipaparkan.</span>
  </div>

  <?php if (!$person): ?>
    <div class="panel">
      <p>Akaun anda belum dikaitkan dengan sebarang rekod anggota. Sila hubungi admin untuk bantuan.</p>
    </div>
  <?php else: ?>

    <div class="profile-card">
      <div class="profile-item">
        <div class="lbl">Nama / Call Sign</div>
        <div class="val"><?= e($person['name']) ?></div>
      </div>
      <div class="profile-item">
        <div class="lbl">Pangkat</div>
        <div class="val"><?= e($person['rank_name']) ?></div>
      </div>
      <div class="profile-item">
        <div class="lbl">Bayaran Bulanan Yang Perlu Dibayar</div>
        <div class="val fee">RM <?= number_format($monthlyFee, 2) ?></div>
      </div>
    </div>

    <div class="panel">
      <div class="panel-row">
        <div class="control">
          <label>Tahun</label>
          <div class="year-nav">
            <a class="btn" href="userinterface.php?year=<?= $year - 1 ?>" style="background:var(--paper-alt);border:1px solid var(--line);width:30px;height:30px;display:flex;align-items:center;justify-content:center;color:var(--navy);text-decoration:none;">&#8592;</a>
            <span class="yr"><?= $year ?></span>
            <a class="btn" href="userinterface.php?year=<?= $year + 1 ?>" style="background:var(--paper-alt);border:1px solid var(--line);width:30px;height:30px;display:flex;align-items:center;justify-content:center;color:var(--navy);text-decoration:none;">&#8594;</a>
          </div>
        </div>
      </div>
    </div>

    <div class="stats">
      <div class="stat-card">
        <div class="num"><?= $paidCount ?>/12</div>
        <div class="lbl">Bulan Dibayar Pada <?= $year ?></div>
        <div class="bar-track"><div class="bar-fill" style="width:<?= round(($paidCount / 12) * 100) ?>%"></div></div>
      </div>
      <div class="stat-card">
        <div class="num">RM <?= number_format($monthlyFee * (12 - $paidCount), 2) ?></div>
        <div class="lbl">Baki Belum Dibayar (<?= $year ?>)</div>
      </div>
    </div>

    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <?php foreach (MONTHS as $m): ?><th><?= $m ?></th><?php endforeach; ?>
          </tr>
        </thead>
        <tbody>
          <tr>
            <?php foreach (MONTHS as $idx => $m):
                $paid = $paidMonths[$idx] ?? false; ?>
              <td class="cell" title="<?= $m ?>: <?= $paid ? 'Sudah bayar' : 'Belum bayar' ?>">
                <div class="fill <?= $paid ? 'paid' : 'unpaid' ?>"><?= $paid ? 'PAID' : '' ?></div>
              </td>
            <?php endforeach; ?>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="legend">
      <span><span class="swatch" style="background:var(--green)"></span>Sudah Dibayar</span>
      <span><span class="swatch" style="background:var(--red)"></span>Belum Dibayar</span>
      <span style="color:var(--slate-soft)">Paparan lihat sahaja &mdash; hubungi admin untuk sebarang pembetulan.</span>
    </div>

  <?php endif; ?>

  <footer class="note">PTK Homestay Payment Ledger &mdash; paparan peribadi.</footer>
</div>
</body>
</html>
