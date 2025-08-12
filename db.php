<?php
$host = 'localhost'; // Replace with your database host if different
$dbname = 'dbknrgyv3hhtne'; // Your database name
$username = 'ugmxlgigg8w9m'; // Your database username
$password = 'gchokcgzhog2'; // Your database password

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
