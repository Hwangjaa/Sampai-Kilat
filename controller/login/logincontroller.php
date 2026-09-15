<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/koneksiDB.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../src/login/loginpage.html', true, 303);
    exit;
}

$username = trim((string) ($_POST['username'] ?? ''));
$password = (string) ($_POST['password'] ?? '');
if ($username === '' || $password === '' || strlen($username) > 30) {
    header('Location: ../../src/login/loginpage.html?error=1', true, 303);
    exit;
}

$stmt = $conn->prepare('SELECT username_staff, password_staff, id_role FROM akun_staff WHERE username_staff = ? LIMIT 1');
$stmt->bind_param('s', $username);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$valid = $row && password_verify($password, (string) $row['password_staff']);
if (!$valid) {
    usleep(250000);
    header('Location: ../../src/login/loginpage.html?error=1', true, 303);
    exit;
}

session_regenerate_id(true);
$_SESSION['username'] = $row['username_staff'];
$_SESSION['role'] = $row['id_role'];
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
header('Location: ../../src/homepageAS/HomePageAdminStaff.php', true, 303);
exit;
