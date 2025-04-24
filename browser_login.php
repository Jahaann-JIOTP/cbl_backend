<?php
header("Content-Type: application/json");

// Database connection
$host = "65.0.16.20";
$dbname = "gcl";
$dbuser = "root";
$dbpassword = "";

$conn = new mysqli($host, $dbuser, $dbpassword, $dbname);

if ($conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "Database connection failed."]);
    exit();
}

// Get email and password from URL parameters
$email = isset($_GET['email']) ? $_GET['email'] : null;
$password = isset($_GET['password']) ? $_GET['password'] : null;

// Validate input
if (!$email || !$password) {
    echo json_encode(["status" => "error", "message" => "Email and password are required."]);
    exit();
}

// Escape the email to prevent SQL injection
$email = $conn->real_escape_string($email);

// Query to fetch user
$sql = "SELECT id, password FROM users WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();

    // Check if the password matches
    if ($user['password'] === $password) { // Direct comparison
        echo json_encode(["status" => "success", "message" => "Login successful.", "user_id" => $user['id']]);
    } else {
        echo json_encode(["status" => "error", "message" => "Invalid email or password."]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Invalid email or password."]);
}

// Close the statement and connection
$stmt->close();
$conn->close();
?>
