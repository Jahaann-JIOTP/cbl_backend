<?php
require 'vendor/autoload.php';

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

// Connect to MongoDB

function connectDB() {
    try {
        $client = new MongoDB\Client("mongodb://admin:cisco123@13.234.241.103:27017/?authSource=iotdb&readPreference=primary&ssl=false");
        return $client->iotdb;
    } catch (Exception $e) {
        echo json_encode(["error" => "Failed to connect to MongoDB: " . $e->getMessage()]);
        exit;
    }
}

$db = connectDB();
$collection = $db->CBL_b;

// Fetch and validate parameters
$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'GET') {
    $val = isset($_GET['val']) ? $_GET['val'] : null;
    $start_date = isset($_GET['s']) ? $_GET['s'] : null;
    $end_date = isset($_GET['e']) ? $_GET['e'] : null;
} elseif ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $val = isset($input['val']) ? $input['val'] : null;
    $start_date = isset($input['s']) ? $input['s'] : null;
    $end_date = isset($input['e']) ? $input['e'] : null;
} else {
    echo json_encode(["error" => "Invalid request method. Only GET and POST are allowed."]);
    exit;
}

// Log received parameters for debugging
file_put_contents("debug.log", "Received parameters: val={$val}, start_date={$start_date}, end_date={$end_date}\n", FILE_APPEND);

// Validate parameters
if (!$val || !$start_date || !$end_date) {
    echo json_encode([
        "error" => "Missing required parameters.",
        "debug" => [
            "val" => $val,
            "start_date" => $start_date,
            "end_date" => $end_date
        ]
    ]);
    exit;
}

// Normalize and validate 'val'
$val = strtoupper($val);
if (!in_array($val, ['F_1', 'F_2'])) {
    echo json_encode([
        "error" => "Invalid val parameter. Allowed values are F_1 and F_2.",
        "received_val" => $val
    ]);
    exit;
}

// Format dates
try {
    $start_date = date('Y-m-d', strtotime($start_date));
    $start_date1 = 'DT#' . $start_date;

    $end_date = date('Y-m-d', strtotime($end_date));
    $end_date1 = 'DT#' . $end_date . 'T23:59:59'; // Include full day in the range
} catch (Exception $e) {
    echo json_encode(["error" => "Invalid date format.", "details" => $e->getMessage()]);
    exit;
}

// Construct MongoDB query
$where = [
    'PLC_Date_Time' => [
        '$gte' => $start_date1,
        '$lte' => $end_date1
    ]
];

// Determine tags and multiplier
$tags = [];
$multiplier = 1;

if ($val === 'F_1') {
    $tags = [
        'F1_GWP_Flowrate',
        'F2_Airjet_Flowrate',
        'F3_MainLine_Flowrate',
        'F4_Sewing2_Flowrate',
        'F5_Textile_Flowrate',
        'F6_Sewing1_Flowrate',
        'F7_PG_Flowrate'
    ];
    $multiplier = 1.7;
} elseif ($val === 'F_2') {
    $tags = [
        'F1_GWP_TotalFlow',
        'F2_Airjet_TotalFlow',
        'F3_MainLine_TotalFlow',
        'F4_Sewing2_TotalFlow',
        'F5_Textile_TotalFlow',
        'F6_Sewing1_TotalFlow',
        'F7_PG_TotalFlow'
    ];
    $multiplier = 0.283168;
}

// Select fields for the query
$select_fields = array_fill_keys($tags, 1);
$select_fields['PLC_Date_Time'] = 1;

$options = ['projection' => $select_fields];

// Fetch data from MongoDB
try {
    $cursor = $collection->find($where, $options);
    $docs = $cursor->toArray();

    if (empty($docs)) {
        echo json_encode(["error" => "No data found for the given query."]);
        exit;
    }

    // Process the response
    $response = [];
    foreach ($docs as $document) {
        $entry = [];
        $entry['PLC_Date_Time'] = isset($document['PLC_Date_Time']) ? str_replace("DT#", "", $document['PLC_Date_Time']) : null;
        foreach ($tags as $tag) {
            $entry[$tag] = isset($document[$tag]) ? round($document[$tag] * $multiplier, 2) : null;
        }
        $response[] = $entry;
    }

    echo json_encode(["success" => true, "data" => $response], JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo json_encode(["error" => "Failed to query MongoDB", "details" => $e->getMessage()]);
}
?>