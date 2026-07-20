<?php
require_once 'config.php';
requireAdmin();

// -----------------------------------------------------------------
// Filters — year, search text, selected ranks (comma list).
// Action forms resubmit these as hidden POST fields so the current
// view is preserved after a redirect; plain navigation uses GET.
// -----------------------------------------------------------------
$src         = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $_GET;
$year        = isset($src['year']) ? (int) $src['year'] : (int) date('Y');
if ($year < 2000 || $year > 2100) { $year = (int) date('Y'); }
$search      = trim($src['search'] ?? '');
$selRanksRaw = trim($src['ranks'] ?? '');
$selRanks    = $selRanksRaw === '' ? [] : explode(',', $selRanksRaw);

function buildQuery(array $overrides = []) {
    global $year, $search, $selRanks;
    $params = [
        'year'   => $year,
        'search' => $search,
        'ranks'  => implode(',', $selRanks),
    ];
    $params = array_merge($params, $overrides);
    // Drop empty values for a cleaner URL
    $params = array_filter($params, function ($v) {
        return $v !== '' && $v !== null;
    });
    return http_build_query($params);
}

// -----------------------------------------------------------------
// Handle POST actions
// -----------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'toggle_payment') {
        $pid = (int) ($_POST['personnel_id'] ?? 0);
        $mth = (int) ($_POST['month'] ?? -1);
        $yr  = (int) ($_POST['year'] ?? 0);

        if ($pid > 0 && $mth >= 0 && $mth <= 11 && $yr > 0) {
            $stmt = $pdo->prepare('SELECT paid FROM payments WHERE personnel_id = ? AND year = ? AND month = ?');
            $stmt->execute([$pid, $yr, $mth]);
            $row = $stmt->fetch();

            if ($row) {
                $upd = $pdo->prepare('UPDATE payments SET paid = ? WHERE personnel_id = ? AND year = ? AND month = ?');
                $upd->execute([$row['paid'] ? 0 : 1, $pid, $yr, $mth]);
            } else {
                $ins = $pdo->prepare('INSERT INTO payments (personnel_id, year, month, paid) VALUES (?, ?, ?, 1)');
                $ins->execute([$pid, $yr, $mth]);
            }
        }

    } elseif ($action === 'add_personnel') {
        $name = mb_strtoupper(trim($_POST['name'] ?? ''));
        $rank = $_POST['rank'] ?? RANKS[0];
        if ($name !== '' && in_array($rank, RANKS, true)) {
            $ins = $pdo->prepare('INSERT INTO personnel (rank_name, name) VALUES (?, ?)');
            $ins->execute([$rank, $name]);
        }

    } elseif ($action === 'edit_personnel') {
        $id   = (int) ($_POST['id'] ?? 0);
        $name = mb_strtoupper(trim($_POST['name'] ?? ''));
        $rank = $_POST['rank'] ?? RANKS[0];
        if ($id > 0 && $name !== '' && in_array($rank, RANKS, true)) {
            $upd = $pdo->prepare('UPDATE personnel SET name = ?, rank_name = ? WHERE id = ?');
            $upd->execute([$name, $rank, $id]);
        }

    } elseif ($action === 'delete_personnel') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            $del = $pdo->prepare('DELETE FROM personnel WHERE id = ?');
            $del->execute([$id]); // payments cascade-delete, linked user's personnel_id is set NULL
        }

    } elseif ($action === 'add_user') {
        $uname = trim($_POST['username'] ?? '');
        $upass = $_POST['password'] ?? '';
        $urole = $_POST['role'] ?? 'user';
        $upidRaw = $_POST['personnel_id'] ?? '';
        $upid  = $upidRaw !== '' ? (int) $upidRaw : null;

        if ($uname !== '' && $upass !== '' && in_array($urole, ['admin', 'user'], true)) {
            $check = $pdo->prepare('SELECT id FROM users WHERE username = ?');
            $check->execute([$uname]);
            if (!$check->fetch()) {
                $hash = password_hash($upass, PASSWORD_DEFAULT);
                $ins = $pdo->prepare('INSERT INTO users (username, password, role, personnel_id) VALUES (?, ?, ?, ?)');
                $ins->execute([$uname, $hash, $urole, $urole === 'user' ? $upid : null]);
            }
        }

    } elseif ($action === 'delete_user') {
        $uid = (int) ($_POST['id'] ?? 0);
        if ($uid > 0 && $uid !== (int) $_SESSION['user_id']) { // can't delete yourself
            $del = $pdo->prepare('DELETE FROM users WHERE id = ?');
            $del->execute([$uid]);
        }
    }

    header('Location: admininterface.php?' . buildQuery());
    exit;
}

// -----------------------------------------------------------------
// Fetch filtered personnel + payments for the ledger table
// -----------------------------------------------------------------
$sql = 'SELECT id, rank_name, name FROM personnel WHERE 1=1';
$args = [];
if ($search !== '') {
    $sql .= ' AND name LIKE ?';
    $args[] = '%' . $search . '%';
}
if (!empty($selRanks)) {
    $placeholders = implode(',', array_fill(0, count($selRanks), '?'));
    $sql .= " AND rank_name IN ($placeholders)";
    $args = array_merge($args, $selRanks);
}
$sql .= ' ORDER BY name ASC';
$stmt = $pdo->prepare($sql);
$stmt->execute($args);
$personnelList = $stmt->fetchAll();

// Payments for this year, keyed by "id|month"
$payMap = [];
$payStmt = $pdo->prepare('SELECT personnel_id, month, paid FROM payments WHERE year = ?');
$payStmt->execute([$year]);
foreach ($payStmt->fetchAll() as $row) {
    $payMap[$row['personnel_id'] . '|' . $row['month']] = (bool) $row['paid'];
}
function isPaid(array $map, int $id, int $month): bool {
    return $map[$id . '|' . $month] ?? false;
}

// Stats
$totalCells = count($personnelList) * 12;
$paidCells  = 0;
$fullyPaid  = 0;
foreach ($personnelList as $pp) {
    $count = 0;
    for ($m = 0; $m < 12; $m++) { if (isPaid($payMap, $pp['id'], $m)) $count++; }
    $paidCells += $count;
    if ($count === 12) $fullyPaid++;
}
$rate = $totalCells ? round(($paidCells / $totalCells) * 100) : 0;
$currentMonthIdx = ((int) date('Y')) === $year ? ((int) date('n') - 1) : null;
$paidThisMonth = $currentMonthIdx !== null
    ? count(array_filter($personnelList, function ($pp) use ($payMap, $currentMonthIdx) {
        return isPaid($payMap, $pp['id'], $currentMonthIdx);
    }))
    : null;

// -----------------------------------------------------------------
// CSV export
// -----------------------------------------------------------------
if (isset($_GET['export'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="ptk-homestay-' . $year . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, array_merge(['Rank', 'Name'], MONTHS, ['Paid Count']));
    foreach ($personnelList as $pp) {
        $count = 0;
        $line = [$pp['rank_name'], $pp['name']];
        for ($m = 0; $m < 12; $m++) {
            $paid = isPaid($payMap, $pp['id'], $m);
            if ($paid) $count++;
            $line[] = $paid ? 'PAID' : 'NOT PAID';
        }
        $line[] = $count . '/12';
        fputcsv($out, $line);
    }
    fclose($out);
    exit;
}

// All users, for the "Manage Users" modal
$allUsers = $pdo->query('SELECT id, username, role FROM users ORDER BY username ASC')->fetchAll();

// Which modal (if any) is open, driven by GET params
$modal = $_GET['modal'] ?? null;
$editData = null;
if ($modal === 'edit' && isset($_GET['id'])) {
    $stmt = $pdo->prepare('SELECT id, rank_name, name FROM personnel WHERE id = ?');
    $stmt->execute([(int) $_GET['id']]);
    $editData = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin &mdash; PTK Homestay</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Oswald:wght@400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="s.css">
</head>
<body>
<div class="app">
  <div class="topbar">
    <div class="title">
      <h1>Homestay Payment Ledger &mdash; PTK</h1>
      <small>Status kutipan bulanan mengikut pangkat</small>
    </div>
    <div class="who">
      <span class="role-badge admin">Admin</span>
      <span><?= e($_SESSION['username']) ?></span>
      <a href="logout.php" class="btn btn-ghost">Log Keluar</a>
    </div>
  </div>

  <div class="panel">
    <form method="get" action="admininterface.php" id="filterForm">
      <input type="hidden" name="ranks" id="ranksField" value="<?= e(implode(',', $selRanks)) ?>">
      <input type="hidden" name="year" value="<?= $year ?>">
      <div class="panel-row">
        <div class="control">
          <label>Tahun</label>
          <div class="year-nav">
            <a href="admininterface.php?<?= buildQuery(['year' => $year - 1]) ?>" style="background:var(--paper-alt);border:1px solid var(--line);width:30px;height:30px;display:flex;align-items:center;justify-content:center;color:var(--navy);text-decoration:none;border-radius:4px;">&#8592;</a>
            <span class="yr"><?= $year ?></span>
            <a href="admininterface.php?<?= buildQuery(['year' => $year + 1]) ?>" style="background:var(--paper-alt);border:1px solid var(--line);width:30px;height:30px;display:flex;align-items:center;justify-content:center;color:var(--navy);text-decoration:none;border-radius:4px;">&#8594;</a>
          </div>
        </div>
        <div class="control">
          <label>Cari Nama</label>
          <input class="search-input" type="text" name="search" value="<?= e($search) ?>" placeholder="Taip nama..." onchange="document.getElementById('filterForm').submit()">
        </div>
        <div class="control" style="flex:1;min-width:260px;">
          <label>Tapis Pangkat</label>
          <div class="chips" id="rankChips">
            <?php foreach (RANKS as $r):
                $active = in_array($r, $selRanks, true); ?>
              <div class="chip <?= $active ? '' : 'off' ?>" data-rank="<?= e($r) ?>"><?= e($r) ?></div>
            <?php endforeach; ?>
          </div>
        </div>
        <a class="btn export-btn" href="admininterface.php?<?= buildQuery(['export' => 1]) ?>">Eksport CSV</a>
        <a class="btn manage-btn" href="admininterface.php?<?= buildQuery(['modal' => 'manageUsers']) ?>">Urus Pengguna</a>
        <a class="btn add-btn" href="admininterface.php?<?= buildQuery(['modal' => 'add']) ?>">+ Tambah Anggota</a>
      </div>
    </form>
  </div>

  <div class="stats">
    <div class="stat-card"><div class="num"><?= count($personnelList) ?></div><div class="lbl">Anggota Dipaparkan</div></div>
    <div class="stat-card">
      <div class="num"><?= $rate ?>%</div>
      <div class="lbl">Kadar Kutipan <?= $year ?></div>
      <div class="bar-track"><div class="bar-fill" style="width:<?= $rate ?>%"></div></div>
    </div>
    <div class="stat-card"><div class="num"><?= $fullyPaid ?></div><div class="lbl">Bayaran Penuh (12/12)</div></div>
    <div class="stat-card"><div class="num"><?= $paidThisMonth === null ? '—' : $paidThisMonth . '/' . count($personnelList) ?></div><div class="lbl">Bayar Bulan Semasa</div></div>
  </div>

  <div class="table-wrap">
    <table class="full-ledger">
      <thead>
        <tr>
          <th class="left">Rank</th>
          <th class="left">Name / Call Sign</th>
          <?php foreach (MONTHS as $m): ?><th><?= $m ?></th><?php endforeach; ?>
          <th>Progress</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($personnelList)): ?>
          <tr class="empty-row"><td colspan="15">Tiada anggota sepadan dengan penapis ini.</td></tr>
        <?php else: foreach ($personnelList as $pp):
          $paidCount = 0;
          $ticks = '';
          for ($m = 0; $m < 12; $m++) {
              $paid = isPaid($payMap, $pp['id'], $m);
              if ($paid) $paidCount++;
              $ticks .= '<div class="tick ' . ($paid ? 'on' : '') . '"></div>';
          }
        ?>
          <tr>
            <td class="info rank"><?= e($pp['rank_name']) ?></td>
            <td class="info name">
              <?= e($pp['name']) ?>
              <span class="row-actions">
                <a class="icon-btn" href="admininterface.php?<?= buildQuery(['modal' => 'edit', 'id' => $pp['id']]) ?>" title="Edit">&#9998;</a>
                <form method="post" action="admininterface.php" style="display:inline" onsubmit="return confirm('Padam <?= e(addslashes($pp['name'])) ?> (<?= e($pp['rank_name']) ?>) daripada ledger? Tindakan ini tidak boleh dibatalkan.');">
                  <input type="hidden" name="action" value="delete_personnel">
                  <input type="hidden" name="id" value="<?= $pp['id'] ?>">
                  <input type="hidden" name="year" value="<?= $year ?>">
                  <input type="hidden" name="search" value="<?= e($search) ?>">
                  <input type="hidden" name="ranks" value="<?= e(implode(',', $selRanks)) ?>">
                  <button type="submit" class="icon-btn" title="Remove">&times;</button>
                </form>
              </span>
            </td>
            <?php foreach (MONTHS as $idx => $mn):
              $paid = isPaid($payMap, $pp['id'], $idx); ?>
              <td class="cell">
                <form method="post" action="admininterface.php">
                  <input type="hidden" name="action" value="toggle_payment">
                  <input type="hidden" name="personnel_id" value="<?= $pp['id'] ?>">
                  <input type="hidden" name="month" value="<?= $idx ?>">
                  <input type="hidden" name="year" value="<?= $year ?>">
                  <input type="hidden" name="search" value="<?= e($search) ?>">
                  <input type="hidden" name="ranks" value="<?= e(implode(',', $selRanks)) ?>">
                  <button type="submit" class="fillbtn <?= $paid ? 'paid' : 'unpaid' ?>" title="Klik untuk tukar status <?= $mn ?>"></button>
                </form>
              </td>
            <?php endforeach; ?>
            <td class="progress-cell">
              <div class="ribbon"><?= $ticks ?></div>
              <div class="ratio"><?= $paidCount ?>/12 paid</div>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>

  <div class="legend">
    <span><span class="swatch" style="background:var(--green)"></span>Paid</span>
    <span><span class="swatch" style="background:var(--red)"></span>Not Paid Yet</span>
    <span style="color:var(--slate-soft)">Klik petak bulan untuk tukar status.</span>
  </div>

  <footer class="note">Ledger dikongsi &mdash; sebarang perubahan oleh Admin akan dilihat oleh semua yang guna sistem ini.</footer>
</div>

<?php if ($modal === 'add' || $modal === 'edit'):
  $isEdit = $modal === 'edit' && $editData;
  $dName = $isEdit ? $editData['name'] : '';
  $dRank = $isEdit ? $editData['rank_name'] : RANKS[0];
?>
<div class="overlay">
  <div class="modal">
    <h3><?= $isEdit ? 'Edit Anggota' : 'Tambah Anggota' ?></h3>
    <form method="post" action="admininterface.php">
      <input type="hidden" name="action" value="<?= $isEdit ? 'edit_personnel' : 'add_personnel' ?>">
      <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= $editData['id'] ?>"><?php endif; ?>
      <input type="hidden" name="year" value="<?= $year ?>">
      <input type="hidden" name="search" value="<?= e($search) ?>">
      <input type="hidden" name="ranks" value="<?= e(implode(',', $selRanks)) ?>">
      <div class="field">
        <label>Nama / Call Sign</label>
        <input type="text" name="name" value="<?= e($dName) ?>" placeholder="cth. TANGO" required>
      </div>
      <div class="field">
        <label>Pangkat</label>
        <select name="rank">
          <?php foreach (RANKS as $r): ?>
            <option value="<?= e($r) ?>" <?= $r === $dRank ? 'selected' : '' ?>><?= e($r) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="modal-actions">
        <a class="btn btn-secondary" href="admininterface.php?<?= buildQuery() ?>">Batal</a>
        <button class="btn btn-primary" type="submit" style="margin-top:0;"><?= $isEdit ? 'Simpan' : 'Tambah' ?></button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<?php if ($modal === 'manageUsers'): ?>
<div class="overlay">
  <div class="modal">
    <h3>Urus Pengguna</h3>
    <div>
      <?php foreach ($allUsers as $u): ?>
        <div class="user-row">
          <span><?= e($u['username']) ?></span>
          <span class="rtag <?= $u['role'] ?>"><?= $u['role'] === 'admin' ? 'Admin' : 'Pengguna' ?></span>
          <?php if ((int) $u['id'] !== (int) $_SESSION['user_id']): ?>
            <form method="post" action="admininterface.php" onsubmit="return confirm('Padam pengguna &quot;<?= e(addslashes($u['username'])) ?>&quot;?');" style="display:inline">
              <input type="hidden" name="action" value="delete_user">
              <input type="hidden" name="id" value="<?= $u['id'] ?>">
              <input type="hidden" name="year" value="<?= $year ?>">
              <input type="hidden" name="search" value="<?= e($search) ?>">
              <input type="hidden" name="ranks" value="<?= e(implode(',', $selRanks)) ?>">
              <button type="submit" class="icon-btn" title="Padam">&times;</button>
            </form>
          <?php else: ?>
            <span style="width:20px;display:inline-block;"></span>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
    <div class="divider"></div>
    <form method="post" action="admininterface.php">
      <input type="hidden" name="action" value="add_user">
      <input type="hidden" name="year" value="<?= $year ?>">
      <input type="hidden" name="search" value="<?= e($search) ?>">
      <input type="hidden" name="ranks" value="<?= e(implode(',', $selRanks)) ?>">
      <div class="field"><label>Username Baru</label><input type="text" name="username" placeholder="cth. konst_amin" required></div>
      <div class="field"><label>Password</label><input type="text" name="password" placeholder="Password" required></div>
      <div class="field">
        <label>Peranan</label>
        <select name="role" id="newUserRole">
          <option value="user">Pengguna (lihat rekod sendiri sahaja)</option>
          <option value="admin">Admin (setup penuh)</option>
        </select>
      </div>
      <div class="field" id="linkField">
        <label>Kaitkan Dengan Anggota</label>
        <select name="personnel_id">
          <option value="">&mdash; Tiada &mdash;</option>
          <?php foreach ($personnelList as $pp): ?>
            <option value="<?= $pp['id'] ?>"><?= e($pp['name']) ?> (<?= e($pp['rank_name']) ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="modal-actions">
        <a class="btn btn-secondary" href="admininterface.php?<?= buildQuery() ?>">Tutup</a>
        <button class="btn btn-primary" type="submit" style="margin-top:0;">Tambah Pengguna</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<script>
  // Rank filter chips toggle by re-submitting the filter form with an
  // updated comma-separated list in the hidden "ranks" field.
  document.querySelectorAll('#rankChips .chip').forEach(function(chip){
    chip.addEventListener('click', function(){
      var field = document.getElementById('ranksField');
      var current = field.value ? field.value.split(',') : [];
      var rank = chip.getAttribute('data-rank');
      var idx = current.indexOf(rank);
      if (idx === -1) current.push(rank); else current.splice(idx, 1);
      field.value = current.join(',');
      document.getElementById('filterForm').submit();
    });
  });

  // Manage-users modal: hide the "link to personnel" field when role = admin
  var roleSelect = document.getElementById('newUserRole');
  if (roleSelect) {
    var linkField = document.getElementById('linkField');
    var toggleLink = function(){ linkField.style.display = roleSelect.value === 'admin' ? 'none' : ''; };
    roleSelect.addEventListener('change', toggleLink);
    toggleLink();
  }
</script>
</body>
</html>
