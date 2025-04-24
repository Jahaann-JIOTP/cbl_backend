<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Database connection settings
$servername = "65.0.16.20";
    $username = "jahaann";
    $password = "Jahaann#321";
$dbname = "gcl";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get the JSON data from the request body
$data = json_decode(file_get_contents("php://input"), true);

if ($data) {
    $date = $data['date']; // Extract the date from the input data

    // Check if a row with the same date already exists
    $checkQuery = "SELECT * FROM production WHERE date = ?";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bind_param("s", $date);
    $checkStmt->execute();
    $result = $checkStmt->get_result();

    if ($result->num_rows > 0) {
        // Update the existing row
        $updateQuery = "
            UPDATE production
            SET GWP = ?, Airjet = ?, Sewing2 = ?, Textile = ?, Sewing1 = ?, PG = ?
            WHERE date = ?";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->bind_param(
            "dddddds",
            $data['GWP'],
            $data['Airjet'],
            $data['Sewing2'],
            $data['Textile'],
            $data['Sewing1'],
            $data['PG'],
            $data['date']
        );

        if ($updateStmt->execute()) {
            echo json_encode(["status" => "success", "message" => "Data updated successfully"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Error updating data: " . $updateStmt->error]);
        }

        $updateStmt->close();
    } else {
        // Insert a new row
        $insertQuery = "
            INSERT INTO production (GWP, Airjet, Sewing2, Textile, Sewing1, PG, date)
            VALUES (?, ?, ?, ?, ?, ?, ?)";
        $insertStmt = $conn->prepare($insertQuery);
        $insertStmt->bind_param(
            "dddddds",
            $data['GWP'],
            $data['Airjet'],
            $data['Sewing2'],
            $data['Textile'],
            $data['Sewing1'],
            $data['PG'],
            $data['date']
        );

        if ($insertStmt->execute()) {
            echo json_encode(["status" => "success", "message" => "Data inserted successfully"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Error inserting data: " . $insertStmt->error]);
        }

        $insertStmt->close();
    }

    $checkStmt->close();
} else {
    echo json_encode(["status" => "error", "message" => "Invalid data"]);
}

// Close the connection
$conn->close();
?>
