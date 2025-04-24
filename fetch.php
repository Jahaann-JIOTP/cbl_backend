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
    // Handling GET request to fetch data
    if ($_SERVER['REQUEST_METHOD'] == 'GET') {
        // Optionally you can add filters here (e.g., by `meter`, `date`, etc.)
        $sql = "SELECT * FROM meterdata";
        $result = mysqli_query($con, $sql);

        if ($result) {
            // Preparing response array to return as JSON
            $i = 0;
            while ($row = mysqli_fetch_assoc($result)) {
                $response[$i]['id'] = $row['id'];
                $response[$i]['meter'] = $row['Source'];
                $response[$i]['option_selected'] = $row['Status'];
                $response[$i]['value'] = $row['Value'];
                $response[$i]['created_at'] = $row['Time'];
                $i++;
            }
            // Returning response as JSON
            echo json_encode($response, JSON_PRETTY_PRINT);
        } else {
            // In case of error in the query
            echo json_encode([
                "success" => false,
                "message" => "Error fetching data: " . mysqli_error($con)
            ]);
        }
    } else {
        // Handling POST request (for inserting data, as you've already done)
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = json_decode(file_get_contents("php://input"), true);
            $meter = $data['meter'];
            $option_selected = $data['option_selected'];
            $value = $data['value'];
            $created_at = $data['created_at'];

            $sql = "INSERT INTO meterdata (meter, option_selected, value, created_at) 
                    VALUES ('$meter', '$option_selected', '$value', '$created_at')";

            if (mysqli_query($con, $sql)) {
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
    }
} else {
    // If the database connection failed
    echo json_encode([
        "success" => false,
        "message" => "DB connection failed"
    ]);
}
?>
