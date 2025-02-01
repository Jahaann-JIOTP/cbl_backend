<?php
require 'vendor/autoload.php';
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
date_default_timezone_set('Asia/Karachi');

// Validate and fetch query parameters
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] . 'T00:00:00.000+05:00' : null;
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] . 'T23:59:59.999+05:00' : null;
$meters = isset($_GET['meter']) ? explode(',', $_GET['meter']) : null; // Fetch and split multiple meter IDs

if (!$start_date || !$end_date || !$meters || empty($meters)) {
    echo json_encode(['error' => 'start_date, end_date, and at least one meter are required.']);
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
        $client = new MongoDB\Client("mongodb://admin:cisco123@13.234.241.103:27017/?authSource=iotdb&readPreference=primary&appname=MongoDB%20Compass&ssl=false");
        return $client->iotdb;
    } catch (Exception $e) {
        echo json_encode(['error' => 'Failed to connect to MongoDB: ' . $e->getMessage()]);
        exit;
    }
}

$db = connectDB();

// Function to fetch cycle-based on/off data for a single meter
function fetchCycles($collection, $start_date, $end_date, $tag) {
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
                    'state' => [
                        '$cond' => [
                            'if' => ['$gt' => ['$' . $tag, 1]], // Dynamically use the meter tag
                            'then' => 1,
                            'else' => 0
                        ]
                    ]
                ]
            ],
            [
                '$sort' => ['timestamp' => 1] // Sort by time
            ]
        ];

        $result = $collection->aggregate($pipeline)->toArray();

        // Initialize variables for cycles
        $cycles = [];
        $cycleCount = 0;
        $onStartTime = null;
        $offStartTime = null;

        foreach ($result as $entry) {
            $currentState = $entry['state'];
            $currentTimestamp = strtotime($entry['timestamp']);

            if ($currentState === 1) {
                // Handle the start of an "On" cycle
                if (!$onStartTime) {
                    $onStartTime = $currentTimestamp;
                    $cycleCount++;
                    $cycles[$cycleCount] = [
                        'cycle' => $cycleCount,
                        'on_time_start' => date('Y-m-d H:i:00', $onStartTime),
                        'on_time_end' => null,
                        'on_duration' => null,
                        'off_time_start' => null,
                        'off_time_end' => null,
                        'off_duration' => null
                    ];
                }

                // End the "Off Cycle" when transitioning to "On"
                if ($offStartTime) {
                    $offEndTime = $currentTimestamp;
                    $offDurationSeconds = $offEndTime - $offStartTime;

                    $cycles[$cycleCount - 1]['off_time_end'] = date('Y-m-d H:i:00', $offEndTime);
                    $cycles[$cycleCount - 1]['off_duration'] = gmdate('H:i:00', $offDurationSeconds);

                    $offStartTime = null; // Reset Off Start Time
                }
            } elseif ($currentState === 0 && $onStartTime) {
                // Handle the end of an "On" cycle
                $onEndTime = $currentTimestamp;
                $onDurationSeconds = $onEndTime - $onStartTime;

                $cycles[$cycleCount]['on_time_end'] = date('Y-m-d H:i:00', $onEndTime);
                $cycles[$cycleCount]['on_duration'] = gmdate('H:i:00', $onDurationSeconds);

                // Start a new "Off Cycle"
                $offStartTime = $onEndTime;
                $onStartTime = null;
                $cycles[$cycleCount]['off_time_start'] = date('Y-m-d H:i:00', $offStartTime);
            }
        }

        // Handle ongoing "On" cycle at the end of the range
        if ($onStartTime !== null) {
            $endTimestamp = strtotime($end_date);
            $onDurationSeconds = $endTimestamp - $onStartTime;

            $cycles[$cycleCount]['on_time_end'] = "Ongoing";
            $cycles[$cycleCount]['on_duration'] = gmdate('H:i:00', $onDurationSeconds);

            // Ensure no "Off Time" is recorded for an ongoing "On" cycle
            $cycles[$cycleCount]['off_time_start'] = null;
            $cycles[$cycleCount]['off_time_end'] = null;
            $cycles[$cycleCount]['off_duration'] = null;
        }

        // Handle ongoing "Off" cycle at the end of the range
        if ($offStartTime !== null) {
            $endTimestamp = strtotime($end_date);
            $offDurationSeconds = $endTimestamp - $offStartTime;

            $cycles[$cycleCount]['off_time_end'] = "Ongoing";
            $cycles[$cycleCount]['off_duration'] = gmdate('H:i:00', $offDurationSeconds);

            // Do not create a new cycle for an ongoing "Off" state
        }

        return $cycles;
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}
try {
    // Connect to the collection
    $collection = $db->CBL_Power;

    // Fetch cycle-based data for each meter
    $cyclesData = [];
    foreach ($meters as $meter_id) {
        $tag = $meter_id; // Construct tag dynamically
        $cyclesData[$meter_id] = fetchCycles($collection, $start_date, $end_date, $tag);
    }

    // Return the result as JSON
    echo json_encode([
        'start_date' => $_GET['start_date'],
        'end_date' => $end_date, // Adjusted end_date
        'meters' => $cyclesData // Data for all selected meters
    ]);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
