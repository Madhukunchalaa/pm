<?php
$host = "localhost"; // Change if needed
$username = "root"; // Default for XAMPP
$password = ""; // Default for XAMPP (leave empty)
$dbname = "pmzoho"; // Change this to your actual database name

// Create connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
