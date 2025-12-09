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
$collection = $db->GCL_new;
$collection->createIndex(['timestamp' => 1, 'meterId' => 1]);

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['start_date']) && isset($_GET['end_date'])) {
    $meterId = $_GET['meterId'] ?? null;
    $suffixes = isset($_GET['suffixes']) ? explode(',', $_GET['suffixes']) : [];

    $startDate = $_GET['start_date'] . 'T00:00:00.000+05:00';
    $endDate = $_GET['end_date'] . 'T23:59:59.999+05:00';

    $projection = ['timestamp' => 1];

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
            ],
            [
                '$sort' => ['timestamp' => 1]
            ]
        ];

        $data = $collection->aggregate($pipeline)->toArray();

        $filteredData = array_map(function($document) use ($meterId) {
            $meterData = [];
            foreach ($document as $key => $value) {
                if ($meterId) {
                    if (strpos($key, "{$meterId}_") === 0) {
                        $meterData[$key] = $value;
                    }
                } else {
                    $meterData[$key] = $value;
                }
            }
            return ['timestamp' => $document['timestamp'], 'data' => $meterData];
        }, $data);

        // Calculate daily consumption
        $dailyConsumption = [];
        $firstValuesByDay = [];
        $lastValuesByDay = [];
        $previousDate = null;

        foreach ($filteredData as $document) {
            $currentDate = (new DateTime($document['timestamp']))->format('Y-m-d');

            foreach ($suffixes as $suffix) {
                $key = "{$meterId}_{$suffix}";

                if (isset($document['data'][$key])) {
                    if (!isset($firstValuesByDay[$currentDate])) {
                        $firstValuesByDay[$currentDate] = $document['data'][$key];
                    }
                    $lastValuesByDay[$currentDate] = $document['data'][$key];
                }
            }
        }

        $dates = array_keys($firstValuesByDay);
        for ($i = 0; $i < count($dates) - 1; $i++) {
            $currentDate = $dates[$i];
            $nextDate = $dates[$i + 1];
            foreach ($suffixes as $suffix) {
                $key = "{$meterId}_{$suffix}";
                $currentValue = $firstValuesByDay[$currentDate];
                $nextValue = $firstValuesByDay[$nextDate];
                $dailyConsumption[$currentDate][$key . '_consumption'] = $nextValue - $currentValue;
            }
        }

        // For the last date, calculate consumption as last value - first value for that day
        $lastDate = end($dates);
        foreach ($suffixes as $suffix) {
            $key = "{$meterId}_{$suffix}";
            $firstValue = $firstValuesByDay[$lastDate];
            $lastValue = $lastValuesByDay[$lastDate];
            $dailyConsumption[$lastDate][$key . '_consumption'] = $lastValue - $firstValue;
        }

        echo json_encode([
            // 'data' => $filteredData,
            'daily_consumption' => $dailyConsumption
        ]);

    } catch (Exception $e) {
        echo json_encode(["error" => "Error fetching data: " . $e->getMessage()]);
    }
} else {
    echo json_encode(["error" => "Invalid request. Please provide start_date and end_date parameters."]);
}
?>
