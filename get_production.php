<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *"); // Allow requests from your frontend's origin
header("Access-Control-Allow-Methods: POST, OPTIONS"); // Allow POST and preflight OPTIONS
header("Access-Control-Allow-Headers: Content-Type, Authorization"); // Allow required headers
// Database connection settings
$servername = "127.0.0.1";
    $username = "jahaann";
    $password = "Jahaann#321";
$dbname = "gcl"; // Replace with your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch data from the production_data table
$sql = "SELECT id, GWP, Airjet, Sewing2, Textile, Sewing1, PG, date FROM production";
$result = $conn->query($sql);

$data = [];

if ($result->num_rows > 0) {
    // Fetch each row and store it in the $data array
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
}

// Return the data as JSON
header('Content-Type: application/json');
echo json_encode($data);

// Close the connection
$conn->close();
?>
