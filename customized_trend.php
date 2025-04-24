<?php
require 'vendor/autoload.php';
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

function connectDB() {
    try {
        $client = new MongoDB\Client("mongodb://65.0.16.20:27017/");
        return $client->iotdb;
    } catch (Exception $e) {
        echo json_encode(["error" => "Failed to connect to MongoDB: " . $e->getMessage()]);
        exit;
    }
}

$db = connectDB();

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['start_date']) && isset($_GET['end_date'])) {
    $meterIds = isset($_GET['meterId']) ? explode(',', $_GET['meterId']) : [];
    $suffixes = isset($_GET['suffixes']) ? explode(',', $_GET['suffixes']) : [];

    // Format the start and end dates
    $startDate = $_GET['start_date'] . 'T00:00:00.000+05:00';
    $endDate = $_GET['end_date'] . 'T23:59:59.999+05:00';

    $projection = ['timestamp' => 1]; // Always include timestamp

    if (!empty($meterIds) && !empty($suffixes)) {
        foreach ($meterIds as $meterId) {
            foreach ($suffixes as $suffix) {
                $projection["{$meterId}_{$suffix}"] = 1;
            }
        }
    } else {
        echo json_encode(["error" => "Please provide valid meterId and suffixes parameters."]);
        exit;
    }

    try {
        // Determine the collection based on the suffix
        $collectionName = in_array("Consumption", $suffixes) ? 'gcl_active' : 'gcl_all';
        $collection = $db->$collectionName;

        // Build the aggregation pipeline
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

        // Fetch the data
        $data = $collection->aggregate($pipeline)->toArray();

        // Format the data
        $filteredData = array_map(function ($document) use ($meterIds, $suffixes) {
            $meterData = [];
            foreach ($document as $key => $value) {
                foreach ($meterIds as $meterId) {
                    foreach ($suffixes as $suffix) {
                        if ($key === "{$meterId}_{$suffix}") {
                            $meterData[$key] = $value;
                        }
                    }
                }
            }
            return ['timestamp' => $document['timestamp'], 'data' => $meterData];
        }, $data);

        // Return the data as JSON
        echo json_encode($filteredData);
    } catch (Exception $e) {
        echo json_encode(["error" => "Error fetching data: " . $e->getMessage()]);
    }
} else {
    echo json_encode(["error" => "Invalid request. Please provide start_date, end_date, meterId, and suffixes parameters."]);
}
?>
