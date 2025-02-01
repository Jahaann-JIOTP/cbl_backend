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

// Connect to MongoDB
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
// $collection = $db->CBL_b;

// Function to fetch and calculate power yield
function calculatePowerYield($collection, $start_date, $end_date) {
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
                'U11_SM11_PowerYield_EXP_Total_kWh' => 1,
                'U12_SM12_PowerYield_EXP_Total_kWh' => 1
            ]
        ],
        [
            '$group' => [
                '_id' => null,
                'U11_First' => ['$first' => '$U11_SM11_PowerYield_EXP_Total_kWh'],
                'U11_Last' => ['$last' => '$U11_SM11_PowerYield_EXP_Total_kWh'],
                'U12_First' => ['$first' => '$U12_SM12_PowerYield_EXP_Total_kWh'],
                'U12_Last' => ['$last' => '$U12_SM12_PowerYield_EXP_Total_kWh']
            ]
        ]
    ];

    $result = $collection->aggregate($pipeline)->toArray();
    if (empty($result)) {
        return 0;
    }

    $data = $result[0];
    $U_11 = (!empty($data['U11_Last']) && !empty($data['U11_First'])) ? $data['U11_Last'] - $data['U11_First'] : 0;
    $U_12 = (!empty($data['U12_Last']) && !empty($data['U12_First'])) ? $data['U12_Last'] - $data['U12_First'] : 0;

    return round($U_11 + $U_12, 2);
}

try {
    // Connect to the collection
    $collection = $db->CBL_b;

    // Calculate power yield
    $data = calculatePowerYield($collection, $start_date, $end_date);

    // Return the calculated power yield as JSON
    echo json_encode($data);
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    echo json_encode(['error' => $e->getMessage()]);
}
?>
