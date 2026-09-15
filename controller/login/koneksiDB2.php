<?php
declare(strict_types=1);
$hostname = getenv('DB_HOST') ?: '127.0.0.1';
$username = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASS') ?: '';
$database = getenv('DB_OPERATIONAL') ?: 'sampaikilat_operational';
mysqli_report(MYSQLI_REPORT_OFF);
$conn2 = new mysqli($hostname, $username, $password, $database);
if ($conn2->connect_errno) {
    error_log('Operational database connection failed: ' . $conn2->connect_errno);
    http_response_code(503);
    exit('Layanan database tidak tersedia.');
}
$conn2->set_charset('utf8mb4');
