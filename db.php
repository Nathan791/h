<?php
// Centrally managed connection
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $db = new mysqli("localhost", "root", "", "commerce");
    $db->set_charset("utf8mb4");
} catch (Exception $e) {
    error_log($e->getMessage());
    die("A system error occurred. Please try again later.");
}