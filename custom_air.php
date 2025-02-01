<?php
require 'vendor/autoload.php';
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

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
        $valuesForDeviation = [];
        $lastNonZeroValues = []; // To retain the last non-zero value for each key

        foreach ($dataCBL as $document) {
            $timestamp = $document['timestamp'];
            $meterData = [];

            foreach ($meterIds as $meterId) {
                foreach ($suffixes as $suffix) {
                    $key = "{$meterId}_{$suffix}";
                    if (isset($document[$key])) {
                        $value = $document[$key];

                        // Collect values for deviation calculation
                        $valuesForDeviation[$key][] = $value;
                        // Apply division and multiplication
                        $transformedValue = ($value);

                        // Check if value is zero
                        if ($value === 0 && isset($lastNonZeroValues[$key])) {
                            $meterData[$key] = $lastNonZeroValues[$key]; // Retain the previous non-zero value
                        } else {
                            // Update the last non-zero value if it's valid
                            if ($value !== 0) {
                                $lastNonZeroValues[$key] = $value;
                            }
                            $meterData[$key] = $value;
                        }
                    }
                }
            }

            $output[] = [
                'timestamp' => $timestamp,
                'data' => $meterData
            ];
        }

        // Calculate mean and standard deviation for each key
        $meanStdDev = [];
        foreach ($valuesForDeviation as $key => $values) {
            $mean = array_sum($values) / count($values);
            $sumOfSquares = array_reduce($values, function ($carry, $item) use ($mean) {
                return $carry + pow($item - $mean, 2);
            }, 0);
            $stdDev = sqrt($sumOfSquares / count($values));
            $meanStdDev[$key] = ['mean' => $mean, 'stdDev' => $stdDev];
        }

        // Filter outliers based on 2 standard deviations
        foreach ($output as &$entry) {
            foreach ($entry['data'] as $key => $value) {
                if (isset($meanStdDev[$key])) {
                    $mean = $meanStdDev[$key]['mean'];
                    $stdDev = $meanStdDev[$key]['stdDev'];

                    // Remove values outside mean ± 2*stdDev
                    if ($value < ($mean - 2 * $stdDev) || $value > ($mean + 2 * $stdDev)) {
                        $entry['data'][$key] = null; // Replace outliers with null
                    }
                }
            }
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
