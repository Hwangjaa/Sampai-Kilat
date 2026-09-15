<?php
declare(strict_types=1);
$hostname = getenv('DB_HOST') ?: '127.0.0.1';
$username = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASS') ?: '';
$database = getenv('DB_ACCOUNT') ?: 'sampaikilat_account';
mysqli_report(MYSQLI_REPORT_OFF);
$conn = new mysqli($hostname, $username, $password, $database);
if ($conn->connect_errno) {
    error_log('Account database connection failed: ' . $conn->connect_errno);
    http_response_code(503);
    exit('Layanan database tidak tersedia.');
}
$conn->set_charset('utf8mb4');
