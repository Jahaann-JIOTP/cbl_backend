<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Accept, Authorization");

// URL to fetch data
$url = "http://43.204.118.114:6881/latestnaubahar1";

// Initialize cURL session
$ch = curl_init($url);

// Set options to return the response and follow redirects
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

// Execute cURL request
$url_response = curl_exec($ch);

// Get the HTTP status1 code
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

// Initialize response array
$response = [];

// Check if the request was successful
if ($httpCode == 200) {
    $url_data = json_decode($url_response, true); // Decode the JSON response from URL
    $response['url_data'] = $url_data; // Add URL data to response array
} else {
    $response['url_error'] = "Failed to fetch data from URL. HTTP status1 Code: " . $httpCode;
}

// Close cURL session
curl_close($ch);

// Database connection
$con = mysqli_connect("127.0.0.1", "root", "", "gcl");

// Check if the database connection is successful
if ($con) {
    // Fetch data from meterdata table
    $meter_data = [];
    $sql_meter_data = "SELECT * FROM meterdata";  // Adjust this query if necessary
    $meter_result = mysqli_query($con, $sql_meter_data);

    if ($meter_result) {
        while ($row = mysqli_fetch_assoc($meter_result)) {
            $meter_data[] = $row; // Collect all rows from the meter_data table
        }
        $response['meter_data'] = $meter_data; // Add DB meter data to response array
    } else {
        $response['meter_data_error'] = "Error fetching data from meter_data table: " . mysqli_error($con);
    }

    // Fetch data from alarms table to ensure we avoid duplicate alarms
    $db_data = [];
    $sql = "SELECT * FROM alarms";
    $result = mysqli_query($con, $sql);

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $db_data[] = $row; // Collect all rows from the alarms table
        }
        $response['db_data'] = $db_data; // Add DB alarms data to response array
    } else {
        $response['db_error'] = "Error fetching data from alarms table: " . mysqli_error($con);
    }

} else {
    $response['db_connection_error'] = "Database connection failed.";
}

// Threshold value for alarm triggering
$threshold = 3;

$alarms = [];

// Define a mapping of meters to URL keys (adjust this mapping as needed)
$meter_to_url_mapping = [ 
    "Solar 1" => "U_1_Current_Avg_Amp",
    "Solar 2" => "U_2_Current_Avg_Amp",
    // Add other meters and their corresponding URL keys as necessary
];

// Compare URL data with DB data for High Voltage, Low Voltage, High Current, etc.
foreach ($meter_data as $db_value) {
    // Extract the meter name, option_selected, and value from the database
    $meter_id = $db_value['Source'];  // Example: "Meter 1"
    $option_selected = $db_value['Status'];  // Extract the option selected
    $db_meter_value = (float)$db_value['Value'];  // Convert to float for comparison

    // Initialize value from URL data (assuming this value is fetched from the URL data mapping)
    $url_value = null;  // Default to null if no matching value is found in URL data
    if (array_key_exists($meter_id, $meter_to_url_mapping)) {
        $url_key = $meter_to_url_mapping[$meter_id]; // Get the URL key for this meter
        if (isset($url_data[$url_key])) {
            $url_value = (float)$url_data[$url_key]; // Assign the corresponding value from URL data
        }
    }

    // Initialize alarm for this meter
    $alarm = [
        "meter_id" => $meter_id,
        "option_selected" => $option_selected,
        "db_value" => $db_meter_value, // Ensure db_value is used here
        "url_value" => $url_value,
        "status1" => "Within Threshold"
    ];

    // Fetch the alarm count and Time from the alarms table
    $sql_alarm_data = "SELECT * FROM alarms WHERE Source = '$meter_id' AND Status = '$option_selected' ORDER BY Time DESC LIMIT 1";
    $alarm_result = mysqli_query($con, $sql_alarm_data);

    if ($alarm_result && mysqli_num_rows($alarm_result) > 0) {
        $alarm_row = mysqli_fetch_assoc($alarm_result);
        $alarm['alarm_count'] = isset($alarm_row['alarm_count']) ? $alarm_row['alarm_count'] : 0; // Set alarm count (default to 0 if not found)
        $alarm['Time'] = isset($alarm_row['Time']) ? $alarm_row['Time'] : 'N/A'; // Set Time (default to 'N/A' if not found)

        // Calculate the exact time difference between now and the Time timestamp
        if ($alarm['Time'] != 'N/A') {
            try {
                // Set timezone to Asia/Karachi
                $timezone = new DateTimeZone('Asia/Karachi');
                $current_time = new DateTime(null, $timezone);  // Get the current time
                $created_time = new DateTime($alarm['Time'], $timezone);  // Time when alarm was triggered
                
                // Calculate the interval
                $interval = $current_time->diff($created_time);  // Get the difference between now and the timestamp

                // If the difference is less than an hour, show the difference in minutes and seconds
                if ($interval->h == 0 && $interval->i < 60) {
                    $alarm['state'] = $interval->format("%i minutes %s seconds ago");
                } else {
                    // Otherwise, show hours, minutes, and seconds
                    $alarm['state'] = $interval->format("%h hours %i minutes %s seconds ago");
                }
            } catch (Exception $e) {
                $alarm['state'] = 'Error calculating time difference';
            }
        } else {
            $alarm['state'] = 'N/A'; // No valid Time
        }
    } else {
        $alarm['alarm_count'] = 0; // No alarm found, default to 0
        $alarm['Time'] = 'N/A'; // No alarm Time found, default to 'N/A'
        $alarm['state'] = 'N/A'; // Default state when no alarm found
    }

    // Check if the option_selected matches any of the predefined options (High Current, Low Voltage, etc.)
    if (in_array($option_selected, ['High Current', 'Low Voltage', 'High Voltage'])) {
        // If both DB and URL values exceed the threshold, trigger an alarm
        if ($db_meter_value > $threshold && $url_value > $threshold) {
            $alarm['status1'] = "Alarm: Both DB and URL values exceed the threshold.";

            // Check if the record already exists in the database (prevent duplicates)
            $check_sql = "SELECT * FROM alarms WHERE Source = '$meter_id' AND Status = '$option_selected' AND Value = '$db_meter_value' AND db_value = '$db_meter_value'";
            $check_result = mysqli_query($con, $check_sql);
            
            if (mysqli_num_rows($check_result) == 0) { // If the record does not exist
                // Insert the correct db_value into the database(instead of url_value)
                $insert_sql = "INSERT INTO alarms (Source, Status, value, db_value, url_value, status1) 
                               VALUES ('$meter_id', '$option_selected', '$db_meter_value', '$db_meter_value', '$url_value', '{$alarm['status1']}')";
                if (!mysqli_query($con, $insert_sql)) {
                    $response['db_insert_error'] = "Error inserting alarm into database: " . mysqli_error($con);
                }
            }
        }
    }

    // Add the alarm to the list if the status1 is not "Within Threshold"
    if ($alarm['status1'] !== "Within Threshold") {
        $alarms[] = $alarm;
    }
}

// Add alarms to the response if any
if (!empty($alarms)) {
    $response['alarms'] = $alarms;
} else {
    $response['no_alarms'] = "No alarms triggered.";
}

// Set the content type to JSON and output the response
header("Content-Type: application/json");
echo json_encode($response, JSON_PRETTY_PRINT);

// Close the database connection
mysqli_close($con);
?>
