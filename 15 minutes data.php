<?php
require 'vendor/autoload.php';

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: http://127.0.0.1:3000"); // Adjust to match your frontend origin
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

// Connect to MongoDB
function connectDB() {
    try {
        $client = new MongoDB\Client("mongodb://Jamal:rVl8O8iMN@43.204.118.114:57019/CBL?authSource=admin&readPreference=primary&appname=MongoDB%20Compass&ssl=false");
        return $client->CBL;
    } catch (Exception $e) {
        echo json_encode(["error" => "Failed to connect to MongoDB: " . $e->getMessage()]);
        exit;
    }
}

$db = connectDB();
$collection = $db->GCL_new;
$collection->createIndex(['timestamp' => 1, 'meterId' => 1]);

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    // Log the input for debugging
    file_put_contents('php://stderr', "Received POST data: " . print_r($input, true), FILE_APPEND);

    $startDate = $input['start_date'] ?? null;
    $endDate = $input['end_date'] ?? null;
    $meterIds = $input['meterIds'] ?? []; // Expecting an array of meter IDs
    $suffixes = isset($input['suffixes']) ? explode(',', $input['suffixes']) : [];

    if (!$startDate || !$endDate || empty($meterIds)) {
        echo json_encode(["error" => "Missing required parameters: start_date, end_date, or meterIds."]);
        exit;
    }

    // Format the dates for MongoDB
    $startDate = $startDate . 'T00:00:00.000+05:00';
    $endDate = $endDate . 'T23:59:59.999+05:00';

    // Build the projection dynamically based on meterIds and suffixes
    $projection = ['timestamp' => 1]; // Always include timestamp
    foreach ($meterIds as $meterId) {
        foreach ($suffixes as $suffix) {
            $projection["{$meterId}_{$suffix}"] = 1;
        }
    }

    try {
        // MongoDB aggregation pipeline
        $pipeline = [
            [
                '$match' => [
                    'timestamp' => [
                        '$gte' => $startDate,
                        '$lte' => $endDate
                    ]
                ]
            ],
            [
                '$project' => $projection
            ]
        ];

        // Execute the aggregation query
        $data = $collection->aggregate($pipeline)->toArray();

        // Format the response
        $filteredData = array_map(function($document) use ($meterIds) {
            $meterData = [];
            foreach ($document as $key => $value) {
                if ($key !== 'timestamp') {
                    $meterData[$key] = $value;
                }
            }
            return ['timestamp' => $document['timestamp'], 'data' => $meterData];
        }, $data);

        echo json_encode($filteredData);
    } catch (Exception $e) {
        echo json_encode(["error" => "Error fetching data: " . $e->getMessage()]);
    }
} else {
    echo json_encode(["error" => "Invalid request method. POST expected."]);
}
?>