<?php
require 'vendor/autoload.php';
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: http://65.0.16.20:3000"); // Allow requests from your frontend's origin
header("Access-Control-Allow-Methods: POST, OPTIONS"); // Allow POST and preflight OPTIONS
header("Access-Control-Allow-Headers: Content-Type, Authorization"); // Allow required headers

function connectDB() {
    try {
        $client = new MongoDB\Client("mongodb://admin:cisco123@13.234.241.103:27017/?authSource=iotdb&readPreference=primary&appname=MongoDB%20Compass&ssl=false");
        return $client->iotdb;
    } catch (Exception $e) {
        echo json_encode(["error" => "Failed to connect to MongoDB: " . $e->getMessage()]);
        exit;
    }
}

$db = connectDB();
$collection = $db->GCL_new;
$collection->createIndex(['timestamp' => 1, 'meterId' => 1]);

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['start_date']) && isset($_GET['end_date'])) {
    $meterId = $_GET['meterId'] ?? null;
    $suffixes = isset($_GET['suffixes']) ? explode(',', $_GET['suffixes']) : [];

    // Format the start and end dates
    $startDate = $_GET['start_date'] . 'T00:00:00.000+05:00';
    $endDate = $_GET['end_date'] . 'T23:59:59.999+05:00';

    $projection = ['timestamp' => 1]; // Always include timestamp

    if ($meterId && !empty($suffixes)) {
        foreach ($suffixes as $suffix) {
            $projection["{$meterId}_{$suffix}"] = 1;
        }
    } elseif ($meterId) {
        $sampleDocument = $collection->findOne([], ['projection' => ['_id' => 0]]);
        if ($sampleDocument) {
            foreach ($sampleDocument as $key => $value) {
                if (strpos($key, "{$meterId}_") === 0) {
                    $projection[$key] = 1;
                }
            }
        }
    } else {
        // If no meterId, fetch all fields for the date range
        $sampleDocument = $collection->findOne([], ['projection' => ['_id' => 0]]);
        if ($sampleDocument) {
            foreach ($sampleDocument as $key => $value) {
                $projection[$key] = 1;
            }
        }
    }

    try {
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

        $data = $collection->aggregate($pipeline)->toArray();

        $filteredData = array_map(function($document) use ($meterId) {
            $meterData = [];
            foreach ($document as $key => $value) {
                // Add meterId prefix to each key in the result if meterId is specified
                if ($meterId) {
                    if (strpos($key, "{$meterId}_") === 0) {
                        $meterData[$key] = $value;
                    }
                } else {
                    $meterData[$key] = $value; // Include all fields if no meterId is specified
                }
            }
            return ['timestamp' => $document['timestamp'], 'data' => $meterData];
        }, $data);

        echo json_encode($filteredData);
    } catch (Exception $e) {
        echo json_encode(["error" => "Error fetching data: " . $e->getMessage()]);
    }
} else {
    echo json_encode(["error" => "Invalid request. Please provide start_date and end_date parameters."]);
}
?>
