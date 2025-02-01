<?php
// Function to calculate elapsed time in hours, minutes, seconds
function timeElapsed($lastOccurrence) {
    $timezone = new DateTimeZone('Asia/Karachi'); // Adjust to your timezone
    $current_time = new DateTime('now', $timezone);
    $occurrence_time = new DateTime($lastOccurrence, $timezone);
    $interval = $current_time->diff($occurrence_time);

    if ($interval->days > 0) {
        return $interval->format('%a days %h hours %i minutes %s seconds ago');
    } elseif ($interval->h > 0) {
        return $interval->format('%h hours %i minutes %s seconds ago');
    } elseif ($interval->i > 0) {
        return $interval->format('%i minutes %s seconds ago');
    } else {
        return $interval->format('%s seconds ago');
    }
}

// Function to fetch data from URL using cURL
function fetchUrlData($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $response = curl_exec($ch);
    if ($response === false) {
        die("Error fetching data: " . curl_error($ch));
    }
    curl_close($ch);
    return json_decode($response, true);
}

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

// Fetch data from the URL
$url = "http://13.234.241.103:1880/latestcbl";
$url_data = fetchUrlData($url);

if (!$url_data) {
    die("Error: No data fetched from URL.");
}

// Database connection
$con = mysqli_connect("15.206.128.214", "jahaann", "Jahaann#321", "cbl_alarms");
if (!$con) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Define meter-to-url mappings

    $meter_to_url_mapping = [
        "New Centac Comp#2 High Voltage" => "U_7_EM7_VoltageLL_V",
        "Compressor Aux High Voltage" => "U_5_EM5_AvgVoltageLL_V",
        "DSD 281(Kaeser)+ ML-15 High Voltage"=>  "U_21_Voltage_LL_V",
        "Kaeser Compressor High Voltage"=>  "U_10_EM10_AvgVoltageLL_V",
        "Dryer High Voltage"=>  "U_15_Voltage_LL_V",
        "Ozen 350 High Voltage"=>  "U_3_EM3_AvgVoltageLL_V",
        "Atlas Copco High Voltage"=>  "U_4_EM4_AvgVoltageLL_V",
        "Ganzair Compressor High Voltage"=>  "U_6_EM6_AvgVoltageLL_V",
        // "Solar Hostels High Voltage"=>  "U_22_Voltage_LL_V",
        "new cantac compressor#1 High Voltage"=>  "U_9_EM9_VoltageLL_V",
        "ML-132 compressor#1 High Voltage"=>  "U_8_EM8_AvgVoltageLL_V",
        
        "New Centac Comp#2 Low Voltage" => "U_7_EM7_VoltageLL_V",
        "Compressor Aux Low Voltage" => "U_5_EM5_AvgVoltageLL_V",
        "DSD 281(Kaeser)+ ML-15 Low Voltage"=>  "U_21_Voltage_LL_V",
        "Kaeser Compressor Low Voltage"=>  "U_10_EM10_AvgVoltageLL_V",
        "Dryer Low Voltage"=>  "U_15_Voltage_LL_V",
        "Ozen 350 Low Voltage"=>  "U_3_EM3_AvgVoltageLL_V",
        "Atlas Copco Low Voltage"=>  "U_4_EM4_AvgVoltageLL_V",
        "Ganzair Compressor Low Voltage"=>  "U_6_EM6_AvgVoltageLL_V",
        // "Solar Hostels Low Voltage"=>  "U_22_Voltage_LL_V",
        "new cantac compressor#1 Low Voltage"=>  "U_9_EM9_VoltageLL_V",
        "ML-132 compressor#1 Low Voltage"=>  "U_8_EM8_AvgVoltageLL_V",
        
        "New Centac Comp#2 High Current" => "U_7_EM7_CurrentAvg_A",
        "Compressor Aux High Current" => "U_5_EM5_CurrentAvg_A",
        "DSD 281(Kaeser)+ ML-15 High Current"=>  "U_21_Current_Total_Amp",
        "Kaeser Compressor High Current"=>  "U_10_EM10_CurrentAvg_A",
        "Dryer High Current"=>  "U_15_Current_Total_Amp",
        "Ozen 350 High Current"=>  "U_3_EM3_CurrentAvg_A",
        "Atlas Copco High Current"=>  "U_4_EM4_CurrentAvg_A",
        "Ganzair Compressor High Current"=>  "U_6_EM6_CurrentAvg_A",
        // "Solar Hostels High Current"=>  "U_22_Current_Total_Amp",
        "new cantac compressor#1 High Current"=>  "U_9_EM9_CurrentAvg_A",
        "ML-132 compressor#1 High Current"=>  "U_8_EM8_CurrentAvg_A"
        
        
            
            
        ];

// Fetch meter data
$sql_meter_data = "SELECT * FROM meter_data";
$meter_result = mysqli_query($con, $sql_meter_data);

$meter_data = [];
if ($meter_result) {
    while ($row = mysqli_fetch_assoc($meter_result)) {
        $meter_data[] = $row;
    }
} else {
    die("Error fetching meter data: " . mysqli_error($con));
}


    

// Define alarm conditions dynamically
$alarm_conditions = [
    'Low Voltage' => function($db_value, $url_value) { return $url_value <= $db_value; },     
    'High Voltage' => function($db_value, $url_value) { return $url_value >= $db_value; },
    'High Current' => function($db_value, $url_value) { return $url_value >= $db_value; },
];

// Process alarms
foreach ($meter_data as $db_row) {
    $meter_id = $db_row['Source'];
    $status = $db_row['Status'];
    $db_value = (float)$db_row['Value'];

    // Get the corresponding URL key for the current meter and status
    $url_key = $meter_to_url_mapping["$meter_id $status"] ?? null;
    if (!$url_key || !isset($url_data[$url_key])) {
        continue; // Skip if mapping or URL value is missing
    }

    $url_value = (float)$url_data[$url_key];
    $is_condition_met = isset($alarm_conditions[$status]) && $alarm_conditions[$status]($db_value, $url_value);

    // Fetch existing alarm for this meter and status
    $check_query = "
        SELECT * FROM alarms 
        WHERE Source = '$meter_id' 
        AND Status = '$status'
    ";
    $check_result = mysqli_query($con, $check_query);

    if ($check_result && mysqli_num_rows($check_result) > 0) {
        // Alarm exists
        $existing_alarm = mysqli_fetch_assoc($check_result);

        if ($is_condition_met) {
            // Condition met: Increment alarm_count only if the condition was previously inactive
            $new_alarm_count = $existing_alarm['alarm_count'];

            // Check if the alarm condition was previously inactive or triggered again
            if ($existing_alarm['db_value'] != $db_value || strtotime($existing_alarm['Time']) < strtotime('-1 second')) {
                $new_alarm_count++; // Increment occurrence only on a new trigger

                // Update alarm with new occurrence and reset the Time field
                $update_query = "
                    UPDATE alarms 
                    SET alarm_count = '$new_alarm_count', 
                        Time = NOW(), 
                        url_value = '$url_value', 
                        db_value = '$db_value',
                        end_time = NULL
                    WHERE id = {$existing_alarm['id']}
                ";
            } else {
                // Update only the db_value and url_value if no new trigger
                $update_query = "
                    UPDATE alarms 
                    SET url_value = '$url_value', 
                        db_value = '$db_value',
                        end_time = NULL
                    WHERE id = {$existing_alarm['id']}
                ";
            }
            mysqli_query($con, $update_query);
        } else {
            // Condition no longer met: Alarm ended
            $update_query = "
                UPDATE alarms 
                SET url_value = '$url_value', 
                    db_value = '$db_value',
                    end_time = NOW()
                WHERE id = {$existing_alarm['id']}
            ";
            mysqli_query($con, $update_query);
        }
    } else {
        // No existing alarm: Create a new one if the condition is met
        if ($is_condition_met) {
            $insert_query = "
                INSERT INTO alarms (Source, Status, Value, Time, db_value, url_value, status1, alarm_count, end_time)
                VALUES ('$meter_id', '$status', '$db_value', NOW(), '$db_value', '$url_value', '$status', 1, NULL)
            ";
            mysqli_query($con, $insert_query);
        }
    }
}







// Set the default timezone
date_default_timezone_set('Asia/Karachi');

// Fetch alarms for response
$sql_fetch_alarms = "
    SELECT id, Source, Status, Value, Time, db_value, url_value, status1, alarm_count
    FROM alarms
    ORDER BY Time DESC
";
$result_alarms = mysqli_query($con, $sql_fetch_alarms);

$alarms = [];
if ($result_alarms) {
    while ($alarm = mysqli_fetch_assoc($result_alarms)) {
        // Always calculate elapsed time from the Time field
        if (!empty($alarm['Time'])) {
            $alarm['state'] = timeElapsed($alarm['Time']);
            $alarm['last_occurrence'] = date('Y-m-d H:i:s', strtotime($alarm['Time']));

            // Check if the condition is currently met
            $meter_id = $alarm['Source'];
            $status = $alarm['Status'];
            $db_value = (float)$alarm['db_value'];
            $url_key = $meter_to_url_mapping["$meter_id $status"] ?? null;
            $url_value = isset($url_data[$url_key]) ? (float)$url_data[$url_key] : null;

            if ($url_key && $url_value !== null && isset($alarm_conditions[$status])) {
                $is_condition_met = $alarm_conditions[$status]($db_value, $url_value);

                if (!$is_condition_met) {
                    // Condition not met: Calculate the end time dynamically
                    $current_time = new DateTime('now', new DateTimeZone('Asia/Karachi'));
                    $alarm['end_time'] = $current_time->format('Y-m-d H:i:s');
                } else {
                    // Condition met: No end time
                    $alarm['end_time'] = "Condition Active";
                }
            } else {
                $alarm['end_time'] = "N/A";
            }
        } else {
            $alarm['state'] = "N/A";
            $alarm['last_occurrence'] = "N/A";
            $alarm['end_time'] = "N/A";
        }

        $alarms[] = $alarm;
    }
} else {
    die("Error fetching alarms from database: " . mysqli_error($con));
}

// Return alarms as JSON
echo json_encode(['alarms' => $alarms]);


?>