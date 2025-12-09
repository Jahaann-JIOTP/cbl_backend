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
$current_time = date('Y-m-d\TH:i:s.000P'); // ISO 8601 format
if ($end_date > $current_time) {
    $end_date = $current_time;
}

// MongoDB connection function
function connectDB() {
    try {
        $client = new MongoDB\Client("mongodb://Jamal:rVl8O8iMN@43.204.118.114:57019/CBL?authSource=admin&readPreference=primary&appname=MongoDB%20Compass&ssl=false");
        return $client->CBL;
    } catch (Exception $e) {
        echo json_encode(['error' => 'Failed to connect to MongoDB: ' . $e->getMessage()]);
        exit;
    }
}

$db = connectDB();

// Function to calculate compressor on time and counter
function calculateCompressorOnTime($collection, $start_date, $end_date) {
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
                    'is_on' => [
                        '$cond' => [
                            'if' => ['$gt' => ['$U_4_EM4_Activepower_Total_W', 1]],
                            'then' => 1,
                            'else' => 0
                        ]
                    ]
                ]
            ],
            [
                '$group' => [
                    '_id' => null,
                    'on_count' => ['$sum' => '$is_on']
                ]
            ]
        ];

        $result = $collection->aggregate($pipeline)->toArray();
        
        if (empty($result)) {
            return ['on_count' => 0, 'total_seconds' => 0];
        }

        // Since each record is 15 minutes, calculate total seconds as on_count × 900 seconds
        $interval_seconds = 15 * 60; // 900 seconds
        $on_count = $result[0]['on_count'];
        $total_seconds = $on_count * $interval_seconds;

        return ['on_count' => $on_count, 'total_seconds' => $total_seconds];
    } catch (Exception $e) {
        return ['on_count' => 0, 'total_seconds' => 0];
    }
}

// Function to calculate energy consumption
function calculateEnergyConsumption($collection, $start_date, $end_date) {
    try {
        $firstRecord = $collection->findOne(
            ['timestamp' => ['$gte' => $start_date]],
            ['sort' => ['timestamp' => 1], 'projection' => ['U_4_EM4_TotalActiveEnergy_kWh' => 1]]
        );

        $lastRecord = $collection->findOne(
            ['timestamp' => ['$lte' => $end_date]],
            ['sort' => ['timestamp' => -1], 'projection' => ['U_4_EM4_TotalActiveEnergy_kWh' => 1]]
        );

        if ($firstRecord && $lastRecord) {
            $start_energy = $firstRecord['U_4_EM4_TotalActiveEnergy_kWh'] ?? 0;
            $end_energy = $lastRecord['U_4_EM4_TotalActiveEnergy_kWh'] ?? 0;
            return max(0, $end_energy - $start_energy); // Avoid negative consumption
        }

        return 0;
    } catch (Exception $e) {
        return 0;
    }
}

// Function to calculate total intervals in a date range
function calculateTotalIntervals($start_date, $end_date) {
    $start = strtotime($start_date);
    $end = strtotime($end_date);
    $interval_seconds = 15 * 60; // 900 seconds
    return ($end - $start) / $interval_seconds;
}

// Function to convert seconds to HH:MM:SS format
function secondsToHM($seconds) {
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    return sprintf('%02d:%02d', $hours, $minutes);
}

try {
    // Connect to the collection
    $collection = $db->CBL_b;

    // Calculate total on time in seconds and on counter
    $on_time_result = calculateCompressorOnTime($collection, $start_date, $end_date);

    // Total intervals in the given range
    $total_intervals = calculateTotalIntervals($start_date, $end_date);

    // Calculate off count and off time
    $off_count = $total_intervals - $on_time_result['on_count'];
    $off_seconds = $off_count * 15 * 60; // Each interval is 900 seconds

    // Convert times to HH:MM:SS format
    $total_on_time = secondsToHM($on_time_result['total_seconds']);
    $total_off_time = secondsToHM($off_seconds);

    // Calculate energy consumption
    $energy_consumption = calculateEnergyConsumption($collection, $start_date, $end_date);

    // Return the result as JSON
    echo json_encode([
        'start_date' => $_GET['start_date'],
        'end_date' => $end_date, // Adjusted end_date
        'total_on_time' => $total_on_time,
        'on_counter' => $on_time_result['on_count'], // Include the on counter
        'total_off_time' => $total_off_time,
        'off_counter' => $off_count, // Include the off counter
        'energy_consumption_kWh' => $energy_consumption // Include energy consumption
    ]);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
