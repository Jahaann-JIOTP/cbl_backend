<?php
require 'vendor/autoload.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

function connectDB()
{
    try {
        $client = new MongoDB\Client("mongodb://admin:cisco123@13.234.241.103:27017/?authSource=iotdb&readPreference=primary&appname=MongoDB%20Compass&ssl=false");
        return $client->iotdb;
    } catch (Exception $e) {
        echo json_encode(["error" => "Failed to connect to MongoDB: " . $e->getMessage()]);
        exit;
    }
}

$db = connectDB();
$collection = $db->CBL_b;
$collection->createIndex(['timestamp' => 1, 'meterId' => 1]);

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    // Log the input for debugging
    file_put_contents('php://stderr', "Debug Info: " . print_r($_SERVER, true), FILE_APPEND);

    $startDate = $input['start_date'] ?? null;
    $endDate = $input['end_date'] ?? null;
    $meterIds = $input['meterIds'] ?? []; // Expecting an array of meter IDs
    $suffixes = isset($input['suffixes']) ? explode(',', $input['suffixes']) : [];

    if (!$startDate || !$endDate || empty($meterIds)) {
        echo json_encode(["error" => "Missing required parameters: start_date, end_date, or meterIds."]);
        exit;
    }

    // Format the dates for MongoDB
    $startOfRange = $startDate . 'T00:00:00.000+05:00';
    $endOfRange = $endDate . 'T23:59:59.999+05:00';

    $consumptionData = [];

    try {
        foreach ($meterIds as $meterId) {
            $suffix = $suffixes[0];

            // Find the first document in the range
            $firstDoc = $collection->findOne(
                [
                    'timestamp' => ['$gte' => $startOfRange, '$lte' => $endOfRange]
                ],
                [
                    'projection' => ["{$meterId}_{$suffix}" => 1, 'timestamp' => 1],
                    'sort' => ['timestamp' => 1]
                ]
            );

            // Find the last document in the range
            $lastDoc = $collection->findOne(
                [
                    'timestamp' => ['$gte' => $startOfRange, '$lte' => $endOfRange]
                ],
                [
                    'projection' => ["{$meterId}_{$suffix}" => 1, 'timestamp' => 1],
                    'sort' => ['timestamp' => -1]
                ]
            );

            if ($firstDoc && $lastDoc) {
                $startValue = $firstDoc["{$meterId}_{$suffix}"] ?? 0;
                $endValue = $lastDoc["{$meterId}_{$suffix}"] ?? 0;
                $consumption = $endValue - $startValue;

                $consumptionData[] = [
                    'meterId' => $meterId,
                    'startValue' => $startValue,
                    'endValue' => $endValue,
                    'consumption' => $consumption
                ];
            }
        }

        echo json_encode($consumptionData);
    } catch (Exception $e) {
        echo json_encode(["error" => "Error fetching data: " . $e->getMessage()]);
    }
} else {
    echo json_encode(["error" => "Invalid request method. POST expected."]);
}
?>
