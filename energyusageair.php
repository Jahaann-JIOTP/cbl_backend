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

    file_put_contents('php://stderr', "Received POST data: " . print_r($input, true), FILE_APPEND);

    $startDate = $input['start_date'] ?? null;
    $endDate = $input['end_date'] ?? null;
    $meterIds = $input['meterIds'] ?? [];
    $suffixes = isset($input['suffixes']) ? explode(',', $input['suffixes']) : [];

    if (!$startDate || !$endDate || empty($meterIds)) {
        echo json_encode(["error" => "Missing required parameters: start_date, end_date, or meterIds."]);
        exit;
    }

    $startDate = new DateTime($startDate);
    $endDate = new DateTime($endDate);
    $endDate->modify('+1 day');

    $consumptionData = [];

    try {
        $interval = new DateInterval('P1D');
        $dateRange = new DatePeriod($startDate, $interval, $endDate);

        foreach ($dateRange as $date) {
            $currentDate = $date->format('Y-m-d');
            $startOfDay = $currentDate . 'T00:00:00.000+05:00';
            $endOfDay = $currentDate . 'T23:59:59.999+05:00';

            foreach ($meterIds as $meterId) {
                $suffix = $suffixes[0];

                // Fetch all documents for the meterId and day
                $cursor = $collection->find(
                    [
                        'timestamp' => ['$gte' => $startOfDay, '$lte' => $endOfDay]
                    ],
                    [
                        'projection' => ["{$meterId}_{$suffix}" => 1, 'timestamp' => 1]
                    ]
                );

                $values = [];
                foreach ($cursor as $doc) {
                    $value = $doc["{$meterId}_{$suffix}"] ?? null;
                    if ($value !== null) {
                        $values[] = $value;
                    }
                }

                // Calculate mean and standard deviation
                if (count($values) > 0) {
                    $mean = array_sum($values) / count($values);
                    $squaredDiffs = array_map(function ($v) use ($mean) {
                        return pow($v - $mean, 2);
                    }, $values);
                    $stdDev = sqrt(array_sum($squaredDiffs) / count($values));

                    // Filter out values outside mean ± 2 * stdDev
                    $filteredValues = array_filter($values, function ($v) use ($mean, $stdDev) {
                        return $v >= $mean - 2 * $stdDev && $v <= $mean + 2 * $stdDev;
                    });

                    if (count($filteredValues) > 0) {
                        $startValue = reset($filteredValues);
                        $endValue = end($filteredValues);
                        $consumption = $endValue - $startValue;
                        $adjustedConsumption = $consumption;

                        $consumptionData[] = [
                            'date' => $currentDate,
                            'meterId' => $meterId,
                            'consumption' => $adjustedConsumption,
                            'startValue' => $startValue,
                            'endValue' => $endValue
                        ];
                    }
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
