<?php
// Allow requests from any origin (you can restrict this to specific domains)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Accept, Authorization");

// Handle OPTIONS requests (preflight)
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit; // Exit early for OPTIONS requests
}

// Database connection
$con = mysqli_connect("65.0.16.20", "root", "", "gcl");
$response = array();

if ($con) {
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $data = json_decode(file_get_contents("php://input"), true);
        $meter = $data['meter'];
        $option_selected = $data['option_selected'];
        $value = $data['value'];
        $created_at = $data['created_at'];  // Assuming it's in UTC
    
        // Convert the 'created_at' to a DateTime object
        $datetime = new DateTime($created_at, new DateTimeZone('UTC'));  // 'created_at' is assumed to be in UTC
        $datetime->setTimezone(new DateTimeZone('Asia/Karachi'));  // Convert to your local timezone (e.g., Asia/Karachi)
        $created_at_local = $datetime->format('Y-m-d H:i:s');  // Format the date/time
    
        // Check if a record with the same 'Source' and 'Status' exists
        $check_sql = "SELECT * FROM meterdata WHERE Source = '$meter' AND Status = '$option_selected'";
        $check_result = mysqli_query($con, $check_sql);
        
        if (mysqli_num_rows($check_result) > 0) {
            // If a row exists, update the 'Value'
            $update_sql = "UPDATE meterdata SET Value = '$value', Time = '$created_at_local' 
                           WHERE Source = '$meter' AND Status = '$option_selected'";
            if (mysqli_query($con, $update_sql)) {
                echo json_encode([
                    "success" => true,
                    "message" => "Data updated successfully"
                ]);
            } else {
                echo json_encode([
                    "success" => false,
                    "message" => "Error updating data: " . mysqli_error($con)
                ]);
            }
        } else {
            // If no row exists, insert a new one
            $insert_sql = "INSERT INTO meterdata (Source, Status, Value, Time) 
                           VALUES ('$meter', '$option_selected', '$value', '$created_at_local')";
            if (mysqli_query($con, $insert_sql)) {
                echo json_encode([
                    "success" => true,
                    "message" => "Data saved successfully"
                ]);
            } else {
                echo json_encode([
                    "success" => false,
                    "message" => "Error saving data: " . mysqli_error($con)
                ]);
            }
        }
    } else {
        $sql = "SELECT * FROM meterdata";
        $result = mysqli_query($con, $sql);
        if ($result) {
            header("Content-Type: application/json");
            $i = 0;
            while ($row = mysqli_fetch_assoc($result)) {
                $response[$i]['id'] = $row['id'];
                $response[$i]['meter'] = $row['meter'];
                $response[$i]['option_selected'] = $row['option_selected'];
                $response[$i]['value'] = $row['value'];
                $response[$i]['created_at'] = $row['created_at'];
                $i++;
            }
            echo json_encode($response, JSON_PRETTY_PRINT);
        }
    }
} else {
    echo json_encode([
        "success" => false,
        "message" => "DB connection failed"
    ]);
}
?>
