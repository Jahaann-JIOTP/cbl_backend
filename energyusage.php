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
    $startDate = new DateTime($startDate);
    $endDate = new DateTime($endDate);
    $endDate->modify('+1 day'); // Include the entire end day

    $consumptionData = [];

    try {
        // Iterate through each date in the range
        $interval = new DateInterval('P1D');
        $dateRange = new DatePeriod($startDate, $interval, $endDate);

        foreach ($dateRange as $date) {
            $currentDate = $date->format('Y-m-d');
            $startOfDay = $currentDate . 'T00:00:00.000+05:00';
            $endOfDay = $currentDate . 'T23:59:59.999+05:00';

            foreach ($meterIds as $meterId) {
                $suffix = $suffixes[array_search($meterId, $meterIds)] ?? "TotalActiveEnergy_kWh";

                // Find the first and last document for the day
                $firstDoc = $collection->findOne(
                    [
                        'timestamp' => ['$gte' => $startOfDay, '$lte' => $endOfDay]
                    ],
                    [
                        'projection' => ["{$meterId}_{$suffix}" => 1, 'timestamp' => 1],
                        'sort' => ['timestamp' => 1]
                    ]
                );

                $lastDoc = $collection->findOne(
                    [
                        'timestamp' => ['$gte' => $startOfDay, '$lte' => $endOfDay]
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
                        'date' => $currentDate,
                        'meterId' => $meterId,
                        'consumption' => $consumption,
                        'startValue' => $startValue,
                        'endValue' => $endValue
                    ];
                }
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
