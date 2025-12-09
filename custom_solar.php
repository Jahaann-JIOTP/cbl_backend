<?php
require 'vendor/autoload.php';
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

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
$collectionCBL = $db->CBL_b;

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['start_date']) && isset($_GET['end_date']) && isset($_GET['meterId']) && isset($_GET['suffixes'])) {
    $startDate = $_GET['start_date'] . 'T00:00:00.000+05:00';
    $endDate = $_GET['end_date'] . 'T23:59:59.999+05:00';
    $meterIds = explode(',', $_GET['meterId']);
    $suffixes = explode(',', $_GET['suffixes']);

    // Validate input
    if (empty($meterIds) || empty($suffixes)) {
        echo json_encode(["error" => "Please provide valid meterId and suffixes parameters."]);
        exit;
    }

    // Initialize projection fields
    $projectionCBL = ['timestamp' => 1];

    // Build projection fields dynamically based on meter IDs and suffixes
    foreach ($meterIds as $meterId) {
        foreach ($suffixes as $suffix) {
            $projectionCBL["{$meterId}_{$suffix}"] = 1;
        }
    }

    try {
        // MongoDB aggregation pipeline
        $pipelineCBL = [
            [
                '$match' => [
                    'timestamp' => [
                        '$gte' => $startDate,
                        '$lte' => $endDate
                    ]
                ]
            ],
            [
                '$project' => $projectionCBL
            ]
        ];

        $dataCBL = $collectionCBL->aggregate($pipelineCBL)->toArray();

        // Format the output to match frontend requirements
        $output = [];
        foreach ($dataCBL as $document) {
            $timestamp = $document['timestamp'];
            $meterData = [];

            foreach ($meterIds as $meterId) {
                foreach ($suffixes as $suffix) {
                    $key = "{$meterId}_{$suffix}";

                    // Apply abs() only for "New Centac Comp#2" and Active Power tags
                    if ($meterId === 'U_7_EM7' && strpos($suffix, 'ActivePowerTotal_kW') !== false) {
                        if (isset($document[$key])) {
                            $meterData[$key] = abs($document[$key]);
                        }
                    } else {
                        if (isset($document[$key])) {
                            $meterData[$key] = $document[$key];
                        }
                    }
                }
            }

            $output[] = [
                'timestamp' => $timestamp,
                'data' => $meterData
            ];
        }

        // Sort the output by timestamp
        usort($output, function ($a, $b) {
            return strcmp($a['timestamp'], $b['timestamp']);
        });

        // Respond with the formatted output
        echo json_encode($output);
    } catch (Exception $e) {
        echo json_encode(["error" => "Error fetching data: " . $e->getMessage()]);
    }
} else {
    echo json_encode(["error" => "Invalid request. Please provide start_date, end_date, meterId, and suffixes parameters."]);
}
?>
