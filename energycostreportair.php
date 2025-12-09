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
        $client = new MongoDB\Client("mongodb://Jamal:rVl8O8iMN@43.204.118.114:57019/CBL?authSource=admin&readPreference=primary&appname=MongoDB%20Compass&ssl=false");
        return $client->CBL;
    } catch (Exception $e) {
        echo json_encode(["error" => "Failed to connect to MongoDB: " . $e->getMessage()]);
        exit;
    }
}

$db = connectDB();
$collection = $db->CBL_b;
$collection->createIndex(['timestamp' => 1, 'meterId' => 1]);

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
            $suffix = $suffixes[0]; // Assuming only one suffix for simplicity

            // Fetch all relevant data for this meter
            $cursor = $collection->find(
                [
                    'timestamp' => ['$gte' => $startOfRange, '$lte' => $endOfRange],
                    "{$meterId}_{$suffix}" => ['$exists' => true]
                ],
                [
                    'projection' => ["{$meterId}_{$suffix}" => 1, 'timestamp' => 1],
                ]
            );

            $values = [];
            foreach ($cursor as $doc) {
                $values[] = $doc["{$meterId}_{$suffix}"] ?? 0;
            }

            if (empty($values)) {
                continue;
            }

            // Calculate mean and standard deviation
            $mean = array_sum($values) / count($values);
            $squaredDiffs = array_map(function ($value) use ($mean) {
                return pow($value - $mean, 2);
            }, $values);
            $stdDev = sqrt(array_sum($squaredDiffs) / count($values));

            $filteredValues = array_filter($values, function ($value) use ($mean, $stdDev) {
                return abs($value - $mean) <= 2 * $stdDev;
            });
            

            // Find the first and last value from filtered data
            if (!empty($filteredValues)) {
                $startValue = reset($filteredValues);
                $endValue = end($filteredValues);
                $consumption = $endValue - $startValue;
                $adjustedConsumption = $consumption;

                $consumptionData[] = [
                    'meterId' => $meterId,
                    'startValue' => $startValue,
                    'endValue' => $endValue,
                    'consumption' => $adjustedConsumption
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
