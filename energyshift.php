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
    file_put_contents('php://stderr', "Received POST data: " . print_r($input, true), FILE_APPEND);

    $startDate = $input['start_date'] ?? null;
    $endDate = $input['end_date'] ?? null;
    $meterIds = $input['meterIds'] ?? []; // Expecting an array of meter IDs
    $suffixes = isset($input['suffixes']) ? explode(',', $input['suffixes']) : [];
    $shifts = $input['shifts'] ?? []; // Shifts parameter

    if (!$startDate || !$endDate || empty($meterIds) || empty($shifts)) {
        echo json_encode(["error" => "Missing required parameters: start_date, end_date, meterIds, or shifts."]);
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
            $consumptionData[$currentDate] = []; // Initialize the date group

            foreach ($meterIds as $meterId) {
                 $suffix = $suffixes[array_search($meterId, $meterIds)] ?? "TotalActiveEnergy_kWh"; // Assuming only one suffix for simplicity
                $consumptionData[$currentDate][$meterId] = []; // Initialize meter data

                foreach ($shifts as $shift) {
                    $startOfShift = $currentDate.'T'.$shift['startTime'].':00.000+05:00';
                    $endOfShift = $currentDate.'T'.$shift['endTime'].':59.999+05:00';
                
                    // Check if the shift spans over two days (e.g., 11:00 PM to 7:00 AM)
                    if (strtotime($shift['endTime']) < strtotime($shift['startTime'])) {
                        // Shift ends on the next day, so we need to query for two separate periods
                        // First period: From the start time to midnight
                        $startOfNextDay = date('Y-m-d', strtotime($currentDate . ' +1 day')) . 'T00:00:00.000+05:00';
                        $endOfShift = $currentDate.'T23:59:59.999+05:00'; // The end of the current day
                
                        // Find first and last documents for the first part of the shift (current day)
                        $firstDoc = $collection->findOne(
                            [
                                'timestamp' => ['$gte' => $startOfShift, '$lte' => $endOfShift]
                            ],
                            [
                                'projection' => ["{$meterId}_{$suffix}" => 1, 'timestamp' => 1],
                                'sort' => ['timestamp' => 1]
                            ]
                        );
                        $lastDoc = $collection->findOne(
                            [
                                'timestamp' => ['$gte' => $startOfShift, '$lte' => $endOfShift]
                            ],
                            [
                                'projection' => ["{$meterId}_{$suffix}" => 1, 'timestamp' => 1],
                                'sort' => ['timestamp' => -1]
                            ]
                        );
                
                        // Second period: From midnight to the end time of the shift on the next day
                        $startOfShift = $startOfNextDay; // Set the start of the shift to midnight on the next day
                        $endOfShift = date('Y-m-d', strtotime($currentDate . ' +1 day')) . 'T' . $shift['endTime'] . ':59.999+05:00'; // Set the end time to the shift's end time on the next day
                
                        // Find first and last documents for the second part of the shift (next day)
                        $firstDocNext = $collection->findOne(
                            [
                                'timestamp' => ['$gte' => $startOfShift, '$lte' => $endOfShift]
                            ],
                            [
                                'projection' => ["{$meterId}_{$suffix}" => 1, 'timestamp' => 1],
                                'sort' => ['timestamp' => 1]
                            ]
                        );
                        $lastDocNext = $collection->findOne(
                            [
                                'timestamp' => ['$gte' => $startOfShift, '$lte' => $endOfShift]
                            ],
                            [
                                'projection' => ["{$meterId}_{$suffix}" => 1, 'timestamp' => 1],
                                'sort' => ['timestamp' => -1]
                            ]
                        );
                
                        // Handle both periods and calculate consumption separately
                        $startValue = $firstDoc["{$meterId}_{$suffix}"] ?? 0;
                        $endValue = $lastDoc["{$meterId}_{$suffix}"] ?? 0;
                        $consumptionDay = $endValue - $startValue;
                
                        $startValueNext = $firstDocNext["{$meterId}_{$suffix}"] ?? 0;
                        $endValueNext = $lastDocNext["{$meterId}_{$suffix}"] ?? 0;
                        $consumptionNight = $endValueNext - $startValueNext;
                
                        // Combine the consumptions for both periods (current day and next day)
                        $consumptionData[$currentDate][$meterId][$shift['name']] = $consumptionDay + $consumptionNight;
                    } else {
                        // Normal shift that doesn't span over two days
                        // Find first and last documents for the regular shift
                        $firstDoc = $collection->findOne(
                            [
                                'timestamp' => ['$gte' => $startOfShift, '$lte' => $endOfShift]
                            ],
                            [
                                'projection' => ["{$meterId}_{$suffix}" => 1, 'timestamp' => 1],
                                'sort' => ['timestamp' => 1]
                            ]
                        );
                        $lastDoc = $collection->findOne(
                            [
                                'timestamp' => ['$gte' => $startOfShift, '$lte' => $endOfShift]
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
                            $consumptionData[$currentDate][$meterId][$shift['name']] = $consumption;
                        } else {
                            $consumptionData[$currentDate][$meterId][$shift['name']] = 0;
                        }
                    }
                }
            }
        }

        echo json_encode(array_values(array_map(function($date, $data) {
            return ['date' => $date] + $data;
        }, array_keys($consumptionData), $consumptionData))); // Convert to array format
    } catch (Exception $e) {
        echo json_encode(["error" => "Error fetching data: " . $e->getMessage()]);
    }
} else {
    echo json_encode(["error" => "Invalid request method. POST expected."]);
}
?>
