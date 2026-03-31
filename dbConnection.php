<?php

$servername = "localhost";
$username   = "root";
$password   = "";
$database   = "mysteryshop";

$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Use UTF-8 and don't echo connection status (prevents header issues)
$conn->set_charset('utf8mb4');

?>