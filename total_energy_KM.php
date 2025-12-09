<?php
session_start();
error_reporting(E_ERROR | E_PARSE);
require 'vendor/autoload.php';
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
date_default_timezone_set('Asia/Karachi');

// Helper function to calculate the energy flow difference
function calculateDifference($data, $key, $min_value = null)
{
    if (empty($data)) return 0;

    $values = array_filter(array_column($data, $key));
    if (empty($values)) return 0;

    $first = reset($values);
    $last = end($values);

    if ($min_value !== null && $first < $min_value) {
        $first = $min_value;
    }

    return max(0, $last - $first);
}

// Helper function to fetch data from MongoDB
function fetchData($collection, $start_date, $end_date, $fields)
{
    $query = ['timestamp' => ['$gte' => $start_date, '$lte' => $end_date]];
    $options = ['projection' => $fields];
    $cursor = $collection->find($query, $options);
    return $cursor->toArray();
}

// Main processing logic
try {
    $start_date = isset($_GET['start_date']) ? $_GET['start_date'] . 'T00:00:00.000+05:00' : null;
    $end_date = isset($_GET['end_date']) ? $_GET['end_date'] . 'T23:59:59.999+05:00' : null;

    if (!$start_date || !$end_date) {
        echo json_encode(['error' => 'start_date and end_date are required.']);
        exit;
    }

    // Connect to MongoDB
    function connectDB()
    {
        try {
            $client = new MongoDB\Client("mongodb://Jamal:rVl8O8iMN@43.204.118.114:57019/CBL?authSource=admin&readPreference=primary&appname=MongoDB%20Compass&ssl=false");
            return $client->CBL;
        } catch (Exception $e) {
            echo json_encode(['error' => 'Failed to connect to MongoDB: ' . $e->getMessage()]);
            exit;
        }
    }

    $db = connectDB();

    // Define the collection
    $collection = $db->CBL_b; // Replace 'CBL_b' with the actual collection name

    // Define the fields to fetch from the database
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
        'U_21_ActiveEnergy_Total_kWh' => 1,
        'F3_MainLine_TotalFlow' => 1
    ];

    // Fetch data from MongoDB
    $data = fetchData($collection, $start_date, $end_date, $fields);

    // Calculate differences for each key
    $U_3 = calculateDifference($data, 'U_3_EM3_TotalActiveEnergy_kWh');
    $U_4 = calculateDifference($data, 'U_4_EM4_TotalActiveEnergy_kWh');
    $U_5 = calculateDifference($data, 'U_5_EM5_TotalActiveEnergy_kWh');
    $U_6 = calculateDifference($data, 'U_6_EM6_TotalActiveEnergy_kWh');
    $U_7 = calculateDifference($data, 'U_7_EM7_ActiveEnergyDelivered_Wh');
    $U_8 = calculateDifference($data, 'U_8_EM8_TotalActiveEnergy_kWh');
    $U_9 = calculateDifference($data, 'U_9_EM9_ActiveEnergyDelivered_Wh');
    $U_10 = calculateDifference($data, 'U_10_EM10_TotalActiveEnergy_kWh');
    $U_15 = calculateDifference($data, 'U_15_ActiveEnergy_Total_kWh');
    $U_22 = calculateDifference($data, 'U_22_ActiveEnergy_Delivered_kWh');
    $F_3 = calculateDifference($data, 'F3_MainLine_TotalFlow');

    // Calculate the final flow
    $total_energy = $U_3 + $U_4 + $U_5 + $U_6 + $U_7 + $U_8+ $U_9 + $U_10 + $U_15+$U_22;
    $main_flow = ($F_3);
    $flow = $main_flow > 0 ? round($main_flow / $total_energy, 2) : 0;

    // Return the result as a JSON response
    echo json_encode($flow);
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    echo json_encode(['error' => "Unable to process the request."]);
}
?>