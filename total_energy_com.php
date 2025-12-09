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
        $client = new MongoDB\Client("mongodb://Jamal:rVl8O8iMN@43.204.118.114:57019/CBL?authSource=admin&readPreference=primary&appname=MongoDB%20Compass&ssl=false");
        return $client->CBL;
    } catch (Exception $e) {
        echo json_encode(['error' => 'Failed to connect to MongoDB: ' . $e->getMessage()]);
        exit;
    }
}

$db = connectDB();
$collection = $db->CBL_b;

// Function to calculate energy differences
function calculateEnergyDifference($data, $key, $min_value = 0) {
    $values = array_filter(array_column($data, $key));

    if (empty($values)) {
        return 0;
    }

    $first = reset($values);
    $last = end($values);

    if ($first < $min_value) {
        $first = $min_value;
    }

    return max(0, $last - $first);
}

// Fetch data from the database
function fetchEnergyData($collection, $start_date, $end_date, $fields) {
    $query = ['timestamp' => ['$gte' => $start_date, '$lte' => $end_date]];
    $options = ['projection' => $fields];
    return $collection->find($query, $options)->toArray();
}

try {
    // Define fields to fetch
    $fields = [
        'U_3_EM3_TotalActiveEnergy_kWh' => 1,
        'U_4_EM4_TotalActiveEnergy_kWh' => 1,
        'U_5_EM5_TotalActiveEnergy_kWh' => 1,
        'U_6_EM6_TotalActiveEnergy_kWh' => 1,
        'U_7_EM7_ActiveEnergyDelivered_Wh' => 1,
        'U_8_EM8_TotalActiveEnergy_kWh' => 1,
        'U_9_EM9_ActiveEnergyDelivered_Wh' => 1,
        'U_10_EM10_TotalActiveEnergy_kWh' => 1,
        'U_15_ActiveEnergy_Total_kWh' => 1,
        // 'U_21_ActiveEnergy_Total_kWh' => 1,
        'U_22_ActiveEnergy_Delivered_kWh' => 1,
    ];

    // Fetch data from MongoDB
    $data = fetchEnergyData($collection, $start_date, $end_date, $fields);

    // Calculate energy differences
    $U_3 = calculateEnergyDifference($data, 'U_3_EM3_TotalActiveEnergy_kWh');
    $U_4 = calculateEnergyDifference($data, 'U_4_EM4_TotalActiveEnergy_kWh');
    $U_5 = calculateEnergyDifference($data, 'U_5_EM5_TotalActiveEnergy_kWh');
    $U_6 = calculateEnergyDifference($data, 'U_6_EM6_TotalActiveEnergy_kWh');
    $U_7 = calculateEnergyDifference($data, 'U_7_EM7_ActiveEnergyDelivered_Wh');
    $U_8 = calculateEnergyDifference($data, 'U_8_EM8_TotalActiveEnergy_kWh');
    $U_9 = calculateEnergyDifference($data, 'U_9_EM9_ActiveEnergyDelivered_Wh');
    $U_10 = calculateEnergyDifference($data, 'U_10_EM10_TotalActiveEnergy_kWh');
    $U_15 = calculateEnergyDifference($data, 'U_15_ActiveEnergy_Total_kWh');
    $U_22 = calculateEnergyDifference($data, 'U_22_ActiveEnergy_Delivered_kWh');

    // Calculate total energy
    $total_energy = round($U_3 + $U_4 + $U_5 + $U_6 + $U_7 + $U_8+ $U_9 + $U_10 + $U_15+$U_22, 2);

    // Return only total energy as JSON
    echo json_encode( $total_energy);
} catch (Exception $e) {
    echo json_encode(['error' => 'An error occurred: ' . $e->getMessage()]);
}
?>
