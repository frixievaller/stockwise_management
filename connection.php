<?php
// connection.php
// Dito nagkokonek ang PHP sa MySQL (kapareho ng itinuro sa Lecture 7).
// Ang file na ito ay ini-include sa taas ng bawat page: include 'connection.php';

// PALITAN ang 4 na ito gamit ang detalye mula sa hosting control panel (MySQL Databases).
$servername = "localhost";
$username   = "root";
$password   = "";
$database   = "stockwise";

mysqli_report(MYSQLI_REPORT_OFF);   // para hindi mag-crash ang page kapag may SQL error
$conn = @mysqli_connect($servername, $username, $password, $database);

if (!$conn) {
    die("Connection Failed: " . mysqli_connect_error());
}
mysqli_set_charset($conn, "utf8mb4");

session_start();   // kailangan para sa login (gumagamit ng $_SESSION)

// e() = pang-protekta laban sa XSS. Ilagay sa lahat ng ipinapakitang data galing sa user/database.
function e($text) {
    return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8');
}

// run() = ligtas na SQL query gamit ang PREPARED STATEMENT (proteksyon laban sa SQL Injection).
// $sql    = query na may ? kapalit ng mga value
// $types  = uri ng bawat ?  (s = text, i = number)
// $values = listahan ng mga value, ayon sa pagkakasunod ng ?
function run($conn, $sql, $types = "", $values = []) {
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        error_log("StockWise SQL prepare error: " . mysqli_error($conn));
        die("A database query failed. Please check the database tables and columns.");
    }
    if ($types !== "" && !mysqli_stmt_bind_param($stmt, $types, ...$values)) {
        error_log("StockWise SQL bind error: " . mysqli_stmt_error($stmt));
        die("A database query failed. Please check the database settings.");
    }
    if (!mysqli_stmt_execute($stmt)) {
        error_log("StockWise SQL execute error: " . mysqli_stmt_error($stmt));
    }
    return $stmt;
}
