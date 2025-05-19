<?php
header("Access-Control-Allow-Origin: *"); // Allow requests from your frontend's origin
header("Access-Control-Allow-Methods: POST, OPTIONS"); // Allow POST and preflight OPTIONS
header("Access-Control-Allow-Headers: Content-Type, Authorization"); // Allow required headers

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}
header("Content-Type: application/json");

// Database connection
$host = "127.0.0.1";
$dbname = "gcl";
$dbuser = "jahaann";
$dbpassword = "Jahaann#321";

$conn = new mysqli($host, $dbuser, $dbpassword, $dbname);

if ($conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "Database connection failed."]);
    exit();
}

// Read JSON input from POST request
$input = json_decode(file_get_contents("php://input"), true);

if (!isset($input['email']) || !isset($input['password'])) {
    echo json_encode(["status" => "error", "message" => "Email and password are required."]);
    exit();
}

$email = $conn->real_escape_string($input['email']);
$password = $input['password'];

// Query to fetch user
$sql = "SELECT id, password FROM users WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();

    // Check if the password matches
    if ($user['password'] === $password) { // Direct comparison for plain text
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
