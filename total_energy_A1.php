<?php
require 'vendor/autoload.php';
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

function connectDB() {
    try {
        $client = new MongoDB\Client("mongodb://Jamal:rVl8O8iMN@43.204.118.114:57019/CBL?authSource=admin&readPreference=primary&ssl=false");
        return $client->CBL;
    } catch (Exception $e) {
        echo json_encode(["error" => "Failed to connect to MongoDB: " . $e->getMessage()]);
        exit;
    }
}

function getDateRange($value) {
    $current_date = date("Y-m-d");
    switch ($value) {
        case 'Today':
            $start_date = date('Y-m-d') . 'T00:00:00.000+05:00';
            $end_date = date('Y-m-d') . 'T23:59:59.999+05:00';
            break;
        case 'Yesterday':
            $start_date = date('Y-m-d', strtotime('-1 day')) . 'T00:00:00.000+05:00';
            $end_date = date('Y-m-d', strtotime('-1 day')) . 'T23:59:59.999+05:00';
            break;
        case 'Last 7 Days':
            $start_date = date('Y-m-d', strtotime('-7 days')) . 'T00:00:00.000+05:00';
            $end_date = date('Y-m-d', strtotime('-1 day')) . 'T23:59:59.999+05:00';
            break;
        case 'Last 30 Days':
            $start_date = date('Y-m-d', strtotime('-30 days')) . 'T00:00:00.000+05:00';
            $end_date = date('Y-m-d', strtotime('-1 day')) . 'T23:59:59.999+05:00';
            break;
        case 'This Week':
            $start_date = date('Y-m-d', strtotime('last Monday')) . 'T00:00:00.000+05:00';
            $end_date = date('Y-m-d') . 'T23:59:59.999+05:00';
            break;
        case 'Last Week':
            $start_date = date('Y-m-d', strtotime('last Monday -7 days')) . 'T00:00:00.000+05:00';
            $end_date = date('Y-m-d', strtotime('last Sunday')) . 'T23:59:59.999+05:00';
            break;
        case 'This Month':
            $start_date = date('Y-m-01') . 'T00:00:00.000+05:00';
            $end_date = date('Y-m-d') . 'T23:59:59.999+05:00';
            break;
        case 'This Year':
            $start_date = date('Y-01-01') . 'T00:00:00.000+05:00';
            $end_date = date('Y-m-d') . 'T23:59:59.999+05:00';
            break;
        default:
            throw new Exception("Invalid value provided: $value");
    }
    return [$start_date, $end_date];
}

function fetchData($collection, $start_date, $end_date, $fields) {
    $start_date = "DT#" . $start_date . "-00:00:00";
    $end_date = "DT#" . $end_date . "-23:59:59";

    $query = [
        'PLC_Date_Time' => [
            '$gte' => $start_date,
            '$lte' => $end_date
        ]
    ];

    $projection = ['_id' => 0];
    foreach ($fields as $field) {
        $projection[$field] = 1;
    }

    error_log("Query: " . json_encode($query)); // Log the query
    error_log("Projection: " . json_encode($projection)); // Log the projection

    $options = ['projection' => $projection];
    $data = $collection->find($query, $options)->toArray();

    if (empty($data)) {
        throw new Exception("No data found for the specified range.");
    }

    return $data;
}

function calculateDifference($data, $field) {
    $values = array_filter(array_column($data, $field));
    if (empty($values)) return 0;

    $first = reset($values);
    $last = end($values);
    return $last - $first;}


try {
    $db = connectDB();

    // Get parameters for date range and fields
    $value = $_GET['value'] ?? null; // For predefined ranges (Today, Yesterday, etc.)
    $start_date = $_GET['start_date'] ?? null;
    $end_date = $_GET['end_date'] ?? null;

    // Define fields for consumption calculation
    $fields = [
        'F3_MainLine_TotalFlow',
        'F1_GWP_TotalFlow',
        'F7_PG_TotalFlow',
        'F4_Sewing2_TotalFlow',
        'F5_Textile_TotalFlow',
        'F6_Sewing1_TotalFlow',
        'F2_Airjet_TotalFlow'
    ];

    // Determine date range
    if ($value) {
        [$start_date, $end_date] = getDateRange($value);
    } elseif (!$start_date || !$end_date) {
        throw new Exception("Either a predefined value or start_date and end_date must be provided.");
    }

    $collection = $db->CBL_b;
    $data = fetchData($collection, $start_date, $end_date, $fields);

// Calculate consumption for each field
$results = [];
foreach ($fields as $field) {
    // Calculate the difference for the field
    $difference = calculateDifference($data, $field);

    // Check if the difference is valid
    if ($difference !== null && $difference !== "No data available") {
        // Add the result to the results array
        $results[] = [
            'tag' => $field,
            'difference' => $difference
        ];
    } else {
        // Add a "no data available" entry
        $results[] = [
            'tag' => $field,
            'difference' => "No valid data available"
        ];
    }
}

// Output the results in JSON format
header("Content-Type: application/json");
echo json_encode([
    "start_date" => $start_date,
    "end_date" => $end_date,
    "tags" => $results
]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
?>