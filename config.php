<?php
/**
 * config.php
 * Shared bootstrap: database connection, session start, constants,
 * and auth helper functions. Every other page starts with:
 *   require_once 'config.php';
 */

session_start();

// ---------------------------------------------------------------
// Database configuration — edit these to match your MySQL setup
// ---------------------------------------------------------------
define('DB_HOST', 'localhost');
define('DB_NAME', 'ptk_homestay');
define('DB_USER', 'root');
define('DB_PASS', '');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $ex) {
    die('Database connection failed: ' . $ex->getMessage());
}

// ---------------------------------------------------------------
// First-run seed: if there are no users yet, create a default admin
// account so the system is reachable. Change this password after
// your first login (see "Manage Users" in admininterface.php).
// ---------------------------------------------------------------
$userCount = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
if ($userCount === 0) {
    $defaultHash = password_hash('admin123', PASSWORD_DEFAULT);
    $seedStmt = $pdo->prepare(
        'INSERT INTO users (username, password, role, personnel_id) VALUES (?, ?, ?, NULL)'
    );
    $seedStmt->execute(['admin', $defaultHash, 'admin']);
}

// ---------------------------------------------------------------
// App-wide constants
// ---------------------------------------------------------------
const RANKS  = ["DSP","ASP","INSP","SI","SM","SJN","KPL","LANS KPL","KONST","KPL/D"];
const MONTHS = ["JAN","FEB","MAR","APR","MAY","JUN","JUL","AUG","SEP","OCT","NOV","DEC"];

// Monthly homestay contribution (RM) owed, based on rank.
// Adjust these figures to match the real fee schedule.
const RANK_FEES = [
    "DSP"      => 100,
    "ASP"      => 90,
    "INSP"     => 80,
    "SI"       => 70,
    "SM"       => 65,
    "SJN"      => 60,
    "KPL"      => 55,
    "LANS KPL" => 50,
    "KONST"    => 45,
    "KPL/D"    => 50,
];

function rank_fee(string $rank): int {
    return RANK_FEES[$rank] ?? 0;
}

// ---------------------------------------------------------------
// Auth helpers
// ---------------------------------------------------------------
function isLoggedIn(): bool {
    return !empty($_SESSION['user_id']);
}

function isAdmin(): bool {
    return isLoggedIn() && $_SESSION['role'] === 'admin';
}

/** Bounce anonymous visitors to login.php */
function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

/** Only allow admins; everyone else gets sent to their own dashboard */
function requireAdmin(): void {
    requireLogin();
    if (!isAdmin()) {
        header('Location: userinterface.php');
        exit;
    }
}

/** Only allow regular users; admins get sent to the admin dashboard */
function requireUser(): void {
    requireLogin();
    if (isAdmin()) {
        header('Location: admininterface.php');
        exit;
    }
}

/** Shorthand output-escaping helper */
function e(?string $s): string {
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}

/** Shared badge/logo SVG used on the login & signup screens */
function badgeSVG(): string {
    return '<svg viewBox="0 0 132 132" width="120" height="120">
        <polygon points="66,4 122,28 122,72 66,128 10,72 10,28" fill="#12233F" stroke="#C9A227" stroke-width="3"/>
        <polygon points="66,16 112,36 112,68 66,114 20,68 20,36" fill="none" stroke="#E7CE7C" stroke-width="1.5"/>
        <text x="66" y="60" text-anchor="middle" font-family="Oswald, sans-serif" font-size="22" fill="#E7CE7C" font-weight="700">PTK</text>
        <text x="66" y="80" text-anchor="middle" font-family="Inter, sans-serif" font-size="9" fill="#fff" letter-spacing="2">HOMESTAY</text>
        <text x="66" y="92" text-anchor="middle" font-family="Inter, sans-serif" font-size="9" fill="#fff" letter-spacing="2">TRACKER</text>
    </svg>';
}
