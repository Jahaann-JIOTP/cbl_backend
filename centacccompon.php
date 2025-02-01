<?php
require 'vendor/autoload.php';
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
date_default_timezone_set('Asia/Karachi');

// Validate and fetch query parameters
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] . 'T00:00:00.000+05:00' : null;
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] . 'T23:59:59.999+05:00' : null;

if (!$start_date || !$end_date) {
    echo json_encode(['error' => 'start_date and end_date are required.']);
    exit;
}

// Adjust end_date to current time if it exceeds the present
$current_time = date('Y-m-d\TH:i:s.000P'); // Current ISO 8601 timestamp
if ($end_date > $current_time) {
    $end_date = $current_time;
}

// MongoDB connection function
function connectDB() {
    try {
        $client = new MongoDB\Client("mongodb://admin:cisco123@13.234.241.103:27017/?authSource=iotdb&readPreference=primary&appname=MongoDB%20Compass&ssl=false");
        return $client->iotdb;
    } catch (Exception $e) {
        echo json_encode(['error' => 'Failed to connect to MongoDB: ' . $e->getMessage()]);
        exit;
    }
}

$db = connectDB();

// Function to calculate compressor on and off time dynamically
function calculateCompressorOnOffTime($collection, $start_date, $end_date) {
    try {
        $pipeline = [
            [
                '$match' => [
                    'timestamp' => [
                        '$gte' => $start_date,
                        '$lte' => $end_date
                    ]
                ]
            ],
            [
                '$project' => [
                    'timestamp' => 1,
                    'is_on' => [
                        '$cond' => [
                            'if' => ['$gt' => ['$U_9_EM9_ActivePowerTotal_kW', 1]],
                            'then' => 1,
                            'else' => 0
                        ]
                    ]
                ]
            ],
            [
                '$sort' => ['timestamp' => 1] // Sort by timestamp
            ]
        ];

        $results = $collection->aggregate($pipeline)->toArray();

        if (empty($results)) {
            return ['on_time' => 0, 'off_time' => 0, 'on_count' => 0];
        }

        $on_time_seconds = 0;
        $off_time_seconds = 0;
        $on_count = 0;

        $previous_time = null;
        $previous_state = null;

        foreach ($results as $document) {
            $current_time = strtotime($document['timestamp']);
            $current_state = $document['is_on'];

            if ($previous_time !== null) {
                $interval_seconds = $current_time - $previous_time;

                if ($previous_state === 1) {
                    // Add to "on" time if the previous state was "on"
                    $on_time_seconds += $interval_seconds;
                    $on_count++;
                } else {
                    // Add to "off" time if the previous state was "off"
                    $off_time_seconds += $interval_seconds;
                }
            }

            // Update the previous state and time for the next iteration
            $previous_time = $current_time;
            $previous_state = $current_state;
        }

        return [
            'on_time' => secondsToHMS($on_time_seconds),
            'off_time' => secondsToHMS($off_time_seconds),
            'on_count' => $on_count,
            'total_seconds' => $on_time_seconds
        ];
    } catch (Exception $e) {
        error_log("Error during aggregation: " . $e->getMessage());
        return ['on_time' => 0, 'off_time' => 0, 'on_count' => 0];
    }
}

// Function to get energy consumption
function fetchConsumption($collection, $start_date, $end_date) {
    try {
        $pipeline = [
            [
                '$match' => [
                    'timestamp' => [
                        '$gte' => $start_date,
                        '$lte' => $end_date
                    ]
                ]
            ],
            [
                '$sort' => ['timestamp' => 1] // Sort by timestamp ascending
            ],
            [
                '$project' => [
                    'U_9_EM9_ActiveEnergyDelivered_Wh' => 1,
                    'timestamp' => 1
                ]
            ]
        ];

        $results = $collection->aggregate($pipeline)->toArray();

        if (empty($results)) {
            return 0; // No data
        }

        $first_value = $results[0]['U_9_EM9_ActiveEnergyDelivered_Wh'];
        $last_value = $results[count($results) - 1]['U_9_EM9_ActiveEnergyDelivered_Wh'];

        // Calculate consumption as the difference between the last and first values
        return max(0, $last_value - $first_value); // Ensure non-negative value
    } catch (Exception $e) {
        error_log("Error during consumption calculation: " . $e->getMessage());
        return 0;
    }
}

// Function to convert seconds to HH:MM:SS format
function secondsToHMS($seconds) {
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    return sprintf('%02d:%02d', $hours, $minutes);
}

try {
    // Connect to the collection
    $collection = $db->CBL_b;

    // Calculate total on time, off time, and on counter
    $result = calculateCompressorOnOffTime($collection, $start_date, $end_date);

    // Fetch energy consumption
    $consumption = fetchConsumption($collection, $start_date, $end_date);

    // Return the result as JSON
    echo json_encode([
        'start_date' => $_GET['start_date'],
        'end_date' => $end_date,
        'total_on_time' => $result['on_time'],
        'on_counter' => $result['on_count'],
        'total_off_time' => $result['off_time'],
        'energy_consumption_kWh' => round($consumption, 2)
    ]);
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    echo json_encode(['error' => $e->getMessage()]);
}
?>
