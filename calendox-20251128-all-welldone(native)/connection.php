<?php

// 1. Connect to Local MySQL Server (using XAMPP or MAMPP)
$username = "root";
$conn = new mysqli("localhost", $username, "", "calendox");
$conn->set_charset("utf8mb4");

// Basic connection error handling
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}
