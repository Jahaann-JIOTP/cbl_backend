<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Accept, Authorization");

// Database connection
$con = mysqli_connect("localhost", "root", "", "gcl");

// Check database connection
if (!$con) {
    $response = ["error" => "Database connection failed."];
    echo json_encode($response, JSON_PRETTY_PRINT);
    exit();
}

// Fetch all data from the alarms table, ordered by creation time
$alarms = [];
$sql_alarms = "SELECT * FROM alarms ORDER BY created_at DESC"; 
$result = mysqli_query($con, $sql_alarms);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        // Compare db_value with url_value and trigger an alarm if url_value exceeds db_value
        if (floatval($row['url_value']) > floatval($row['db_value'])) {
            // Update alarm status to "exceeded" if url_value exceeds db_value
            $status = 'exceeded';
        } else {
            // Set status as 'normal' if no issue
            $status = $row['status'];
        }

        $alarms[] = [
            "meter" => $row['meter'],
            "option_selected" => $row['option_selected'],
            "value" => $row['value'],
            "db_value" => $row['db_value'],
            "url_value" => $row['url_value'],
            "status" => $status, // Update the status field based on comparison
            "created_at" => $row['created_at']
        ];
    }
} else {
    $response = ["error" => "Error fetching alarms: " . mysqli_error($con)];
    echo json_encode($response, JSON_PRETTY_PRINT);
    exit();
}

// Check if there are any alarms that have triggered (status is 'exceeded')
$new_alarm_triggered = false;
foreach ($alarms as $alarm) {
    if ($alarm['status'] === 'exceeded') { // Assuming 'exceeded' status indicates a triggered alarm
        $new_alarm_triggered = true;
        break;
    }
}

// Bell status logic
if ($new_alarm_triggered) {
    // $response = [
    //     "bell_status" => "red", // Bell turns red if a new alarm is triggered
    //     "alarms" => $alarms // Send all alarms to the frontend
    // ];

    $response = [
        "bell_status" => $new_alarm_triggered ? "red" : "blue", // Red if alarm triggered, blue otherwise
        "alarms" => $alarms // Send the alarms list
    ];
    
} else {
    $response = [
        "bell_status" => "default", // Bell stays default if no new alarm
        "message" => "No new alarms.",
        "alarms" => $alarms // Send all alarms to the frontend
    ];
}

// Set the content type to JSON and output the response
header("Content-Type: application/json");
echo json_encode($response, JSON_PRETTY_PRINT);

// Close the database connection
mysqli_close($con);
?>
