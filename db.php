<?php
// Define persistence layer credentials
$host = 'localhost';
$username = 'root'; // The default elevated user in XAMPP environments
$password = '';     // XAMPP ships with an empty root password by default
$database = 'climate_wins';

// Instantiate the MySQLi object to establish the TCP/IP connection tunnel
$conn = new mysqli($host, $username, $password, $database);

// Execute integrity verification
if ($conn->connect_error) {
    // In strict production environments, raw exceptions must be suppressed from the client.
    // They should be routed to error_log() to prevent infrastructure leakage.
    error_log("Persistence layer connection failed: ". $conn->connect_error);
    die("Critical System Error: Database connection malfunction. Please notify the system administrator.");
}

// Enforce 4-byte UTF-8 encoding to guarantee safe storage of complex typography
$conn->set_charset("utf8mb4");
?>