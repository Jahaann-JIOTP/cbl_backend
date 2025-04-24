<?php
require 'vendor/autoload.php';
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
date_default_timezone_set('Asia/Karachi');

// Threshold values
$thresholds = [
    "New Centac Comp#1" => 60,
    "New Centac Comp#2" => 60,
    "Atlas Copco" => 25,
    "ML-132" => 15,
    "DSD281(Kaeser)+ML-15" => 31,
    "Ozen 350" => 25,
    "Ganzair Compressor" => 9,
    "Dryer"  => 10,
    "Compressor Aux"  => 4

];

// Mapping of meter tags to machine names
$meterMapping = [
    "U_3_EM3_Activepower_Total_W" => "Ozen 350",
    "U_4_EM4_Activepower_Total_W" => "Atlas Copco",
    "U_6_EM6_Activepower_Total_W" => "Ganzair Compressor",
    "U_7_EM7_ActivePowerTotal_kW" => "New Centac Comp#2",
    "U_8_EM8_Activepower_Total_W" => "ML-132",
    "U_9_EM9_ActivePowerTotal_kW" => "New Centac Comp#1",
    "U_21_ActivePower_Total_kW" => "DSD281(Kaeser)+ML-15",
    "U_15_ActivePower_Total_kW"  => "Dryer",
    "U_5_EM5_Activepower_Total_W"  => "Compressor Aux"
];

// Validate input parameters
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] . 'T00:00:00.000+05:00' : null;
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] . 'T23:59:59.999+05:00' : null;
$meters = isset($_GET['meter']) ? explode(',', $_GET['meter']) : null;

if (!$start_date || !$end_date || !$meters || empty($meters)) {
    echo json_encode(['error' => 'start_date, end_date, and at least one meter are required.']);
    exit;
}

// MongoDB connection
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

// Fetch cycle-based on/off data for a single meter with threshold comparison
function fetchCycles($collection, $start_date, $end_date, $meterTag, $machineName, $threshold) {
    try {
        $pipeline = [
            ['$match' => ['timestamp' => ['$gte' => $start_date, '$lte' => $end_date]]],
            ['$project' => [
                'timestamp' => 1,
                // Convert very small values (< 1e-10) to zero
                $meterTag => ['$cond' => [
                    'if' => ['$lt' => [['$abs' => ['$' . $meterTag]], 1e-10]],
                    'then' => 0, 
                    'else' => ['$' . $meterTag]
                ]],
                'state' => ['$cond' => ['if' => ['$gt' => ['$' . $meterTag, $threshold]], 'then' => 1, 'else' => 0]]
            ]],
            ['$sort' => ['timestamp' => 1]]
        ];

        $result = $collection->aggregate($pipeline)->toArray();

        $cycles = [];
        $cycleCount = 0;
        $onStartTime = null;
        $offStartTime = null;

        foreach ($result as $entry) {
            $currentState = $entry['state'];
            $currentTimestamp = strtotime($entry['timestamp']);

            if ($currentState === 1) {
                if (!$onStartTime) {
                    $onStartTime = $currentTimestamp;
                    $cycleCount++;
                    $cycles[$cycleCount] = ['cycle' => $cycleCount, 'on_time_start' => date('Y-m-d H:i:00', $onStartTime)];
                }
                if ($offStartTime) {
                    $offEndTime = $currentTimestamp;
                    $cycles[$cycleCount - 1]['off_time_end'] = date('Y-m-d H:i:00', $offEndTime);
                    $cycles[$cycleCount - 1]['off_duration'] = gmdate('H:i:00', $offEndTime - $offStartTime);
                    $offStartTime = null;
                }
            } elseif ($currentState === 0 && $onStartTime) {
                $onEndTime = $currentTimestamp;
                $cycles[$cycleCount]['on_time_end'] = date('Y-m-d H:i:00', $onEndTime);
                $cycles[$cycleCount]['on_duration'] = gmdate('H:i:00', $onEndTime - $onStartTime);
                $offStartTime = $onEndTime;
                $onStartTime = null;
                $cycles[$cycleCount]['off_time_start'] = date('Y-m-d H:i:00', $offStartTime);
            }
        }

        $currentTimestamp = date('Y-m-d H:i:s');

        if ($onStartTime !== null) {
            $cycles[$cycleCount]['on_time_end'] = $currentTimestamp;
            $cycles[$cycleCount]['on_duration'] = gmdate('H:i:00', strtotime($currentTimestamp) - $onStartTime);
        }

        if ($offStartTime !== null) {
            $cycles[$cycleCount]['off_time_end'] = $currentTimestamp;
            $cycles[$cycleCount]['off_duration'] = gmdate('H:i:00', strtotime($currentTimestamp) - $offStartTime);
        }

        return $cycles;
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

try {
    $collection = $db->CBL_Power;
    $cyclesData = [];

    foreach ($meters as $meterTag) {
        if (isset($meterMapping[$meterTag])) {
            $machineName = $meterMapping[$meterTag];
            $threshold = $thresholds[$machineName] ?? null;
            if ($threshold !== null) {
                $cyclesData[$meterTag] = fetchCycles($collection, $start_date, $end_date, $meterTag, $machineName, $threshold);
            }
        }
    }

    echo json_encode([ 'start_date' => $_GET['start_date'], 'end_date' => $end_date, 'meters' => $cyclesData ]);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
