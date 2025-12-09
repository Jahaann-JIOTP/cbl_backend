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

// Helper function to calculate total flow difference
function calculateFlowDifference($data, $key) {
    if (empty($data)) {
        error_log("No data available for key: $key");
        return 0;
    }
    $values = array_filter(array_column($data, $key));
    if (empty($values)) {
        error_log("No valid values found for key: $key");
        return 0;
    }

    $first = reset($values);
    $last = end($values);
    $difference = max(0, $last - $first);

    error_log("Key: $key | First: $first | Last: $last | Difference: $difference");
    return $difference;
}

// Helper function to fetch data
function fetchData($collection, $start_date, $end_date, $fields) {
    $query = ['timestamp' => ['$gte' => $start_date, '$lte' => $end_date]];
    $options = ['projection' => $fields];
    $cursor = $collection->find($query, $options);
    return $cursor->toArray();
}

// Main Logic
try {
    $collection = $db->CBL_b;

    $fields = [
        'F1_GWP_TotalFlow' => 1,
        'F2_Airjet_TotalFlow' => 1,
        'F3_MainLine_TotalFlow' => 1,
        'F4_Sewing2_TotalFlow' => 1,
        'F5_Textile_TotalFlow' => 1,
        'F6_Sewing1_TotalFlow' => 1,
        'F7_PG_TotalFlow' => 1
    ];

    // Fetch data from MongoDB
    $data = fetchData($collection, $start_date, $end_date, $fields);

    // Calculate flow differences for each field
    $F_1 = calculateFlowDifference($data, 'F1_GWP_TotalFlow');
    $F_2 = calculateFlowDifference($data, 'F2_Airjet_TotalFlow');
    $F_3 = calculateFlowDifference($data, 'F3_MainLine_TotalFlow');
    $F_4 = calculateFlowDifference($data, 'F4_Sewing2_TotalFlow');
    $F_5 = calculateFlowDifference($data, 'F5_Textile_TotalFlow');
    $F_6 = calculateFlowDifference($data, 'F6_Sewing1_TotalFlow');
    $F_7 = calculateFlowDifference($data, 'F7_PG_TotalFlow');

    // Calculate total and convert units
    $total_flow = round($F_3 , 2);

    // Return total flow as JSON response
    echo json_encode($total_flow);
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    echo json_encode(['error' => $e->getMessage()]);
}
?>
