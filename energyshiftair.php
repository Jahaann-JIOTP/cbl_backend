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

function filterOutliers($values)
{
    if (empty($values)) {
        return [];
    }

    // Filter out negative values first
    $values = array_filter($values, function($value) {
        return $value >= 0;
    });

    // Sort values
    sort($values);

    // Calculate quartiles (Q1 and Q3)
    $count = count($values);
    $Q1 = $values[intval($count * 0.25)];
    $Q3 = $values[intval($count * 0.75)];

    // Calculate the IQR
    $IQR = $Q3 - $Q1;

    // Define the upper and lower bounds for outliers
    $lowerBound = $Q1 - 1.5 * $IQR;
    $upperBound = $Q3 + 1.5 * $IQR;

    // Log intermediate values for debugging
    file_put_contents(
        'php://stderr',
        "Filtering values: " . json_encode($values) . "\nQ1: $Q1, Q3: $Q3, IQR: $IQR\nLower Bound: $lowerBound, Upper Bound: $upperBound\n",
        FILE_APPEND
    );

    // Return filtered values within the bounds
    return array_filter($values, function($value) use ($lowerBound, $upperBound) {
        return $value >= $lowerBound && $value <= $upperBound;
    });
}



$db = connectDB();
$collection = $db->CBL_b;
$collection->createIndex(['timestamp' => 1, 'meterId' => 1]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    $startDate = $input['start_date'] ?? null;
    $endDate = $input['end_date'] ?? null;
    $meterIds = $input['meterIds'] ?? [];
    $suffixes = isset($input['suffixes']) ? explode(',', $input['suffixes']) : [];
    $shifts = $input['shifts'] ?? [];

    if (!$startDate || !$endDate || empty($meterIds) || empty($shifts)) {
        echo json_encode(["error" => "Missing required parameters: start_date, end_date, meterIds, or shifts."]);
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
            $consumptionData[$currentDate] = [];

            foreach ($meterIds as $meterId) {
                $suffix = $suffixes[0];
                $consumptionData[$currentDate][$meterId] = [];

                foreach ($shifts as $shift) {
                    // Parse shift times
                    $shiftStartTime = new DateTime($shift['startTime']);
                    $shiftEndTime = new DateTime($shift['endTime']);
                
                    // Determine if the shift crosses midnight
                    $crossesMidnight = $shiftEndTime < $shiftStartTime;
                
                    // Construct start and end of shift timestamps
                    $startOfShift = $currentDate . 'T' . $shift['startTime'] . ':00.000+05:00';
                
                    if ($crossesMidnight) {
                        // If the shift crosses midnight, increment the date for the end time
                        $endOfShiftDate = (new DateTime($currentDate))->modify('+1 day')->format('Y-m-d');
                        $endOfShift = $endOfShiftDate . 'T' . $shift['endTime'] . ':59.999+05:00';
                    } else {
                        $endOfShift = $currentDate . 'T' . $shift['endTime'] . ':59.999+05:00';
                    }

                    // Fetch all values for the shift
                    $cursor = $collection->find(
                        [
                            'timestamp' => ['$gte' => $startOfShift, '$lte' => $endOfShift]
                        ],
                        [
                            'projection' => ["{$meterId}_{$suffix}" => 1, 'timestamp' => 1],
                            'sort' => ['timestamp' => 1]
                        ]
                    );

                    $values = [];
                    foreach ($cursor as $doc) {
                        $value = $doc["{$meterId}_{$suffix}"] ?? null;
                        if ($value !== null) {
                            $values[] = $value;
                        }
                    }

                    // Apply standard deviation filtering
                    $filteredValues = filterOutliers($values);

                    if (!empty($filteredValues)) {
                        $startValue = reset($filteredValues);
                        $endValue = end($filteredValues);
                        $consumption = $endValue - $startValue;

                        // Store consumption data
                        $consumptionData[$currentDate][$meterId][$shift['name']] = $consumption;
                    } else {
                        $consumptionData[$currentDate][$meterId][$shift['name']] = 0; // Default to 0 if no valid data
                    }
                }
            }
        }

        // Convert to a format suitable for output
        $response = array_map(function ($date, $data) {
            return ['date' => $date] + $data;
        }, array_keys($consumptionData), $consumptionData);

        echo json_encode($response);
    } catch (Exception $e) {
        echo json_encode(["error" => "Error fetching data: " . $e->getMessage()]);
    }
} else {
    echo json_encode(["error" => "Invalid request method. POST expected."]);
}
?>
