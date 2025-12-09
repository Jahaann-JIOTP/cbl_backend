<?php
require 'vendor/autoload.php';
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

function connectDB()
{
    try {
        $client = new MongoDB\Client("mongodb://Jamal:rVl8O8iMN@43.204.118.114:57019/CBL?authSource=admin&readPreference=primary&ssl=false");
        return $client->CBL;
    } catch (Exception $e) {
        echo json_encode(["error" => "Failed to connect to MongoDB: " . $e->getMessage()]);
        exit;
    }
}

$db = connectDB();
$collection = $db->CBL_b;
$collection->createIndex(['timestamp' => 1]);

// Meter ID to Name Mapping
$meterNameMapping = [
    "GWP" => "F1_GWP",
    "Airjet" => "F2_Airjet",
    "Mainline" => "F3_MainLine",
    "Sewing2" => "F4_Sewing2",
    "Textile" => "F5_Textile",
    "Sewing1" => "F6_Sewing1",
    "PG" => "F7_PG",
    "Ozen 350" => "U_3_EM3",
    "Atlas Copco" => "U_4_EM4",
    "Compressor Aux" => "U_5_EM5",
    "Ganzair Compressor" => "U_6_EM6",
    "New Centac Comp#2" => "U_7_EM7",
    "ML-132" => "U_8_EM8",
    "Kaeser Compressor" => "U_10_EM10",
    "Dryer" => "U_15",
    "Solar 1" => "U11_SM11",
    "Solar 2" => "U12_SM12",
];
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['start_date']) && isset($_GET['end_date']) && ($_GET['suffixes'] == "Flowrate")) {
    $meterIds = isset($_GET['meterId']) ? explode(',', $_GET['meterId']) : [];
    $suffixes = isset($_GET['suffixes']) ? explode(',', $_GET['suffixes']) : [];

    if (empty($meterIds) || empty($suffixes)) {
        echo json_encode(["error" => "Please provide valid meterIds and suffixes parameters."]);
        exit;
    }

    $startDate = $_GET['start_date'] . 'T00:00:00.000+05:00';
    $endDate = $_GET['end_date'] . 'T23:59:59.999+05:00';

    $projection = ['timestamp' => 1];
    foreach ($meterIds as $meterId) {
        foreach ($suffixes as $suffix) {
            $projection["{$meterId}_{$suffix}"] = 1;
        }
    }

    try {
        $pipeline = [
            ['$match' => ['timestamp' => ['$gte' => $startDate, '$lte' => $endDate]]],
            ['$project' => $projection],
            ['$sort' => ['timestamp' => 1]]
        ];

        // Debugging: Log the pipeline
        error_log("Pipeline: " . json_encode($pipeline));

        $data = $collection->aggregate($pipeline)->toArray();

        // Debugging: Log the query result
        error_log("Query Result: " . json_encode($data));

        $summedValues = [];

        foreach ($data as $document) {
            foreach ($meterIds as $meterId) {
                foreach ($suffixes as $suffix) {
                    $key = "{$meterId}_{$suffix}";

                    if (isset($document[$key])) {
                        if (!isset($summedValues[$key])) {
                            $summedValues[$key] = 0;
                        }
                        $summedValues[$key] += $document[$key];
                    }
                }
            }
        }

        // Replace meter IDs with names in the final output
        $totalSummedValues = [];
        foreach ($summedValues as $key => $value) {
            // Find the meter ID by matching it with the meter IDs in the mapping
            $meterId = null;
            foreach ($meterNameMapping as $name => $id) {
                if (strpos($key, $id) === 0) { // Check if $key starts with $id
                    $meterId = $name;
                    break;
                }
            }

            if ($meterId === null) {
                // If no matching meter ID is found, skip this key
                continue;
            }

            // Map the meter ID to its name
            $meterName = $meterId;

            // Store the total sum with the meter name
            $totalSummedValues[$meterName] = $value;
        }

        echo json_encode([
            'total_consumption' => $totalSummedValues
        ]);
    } catch (Exception $e) {
        echo json_encode(["error" => "Error fetching data: " . $e->getMessage()]);
    }
} else {
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['start_date']) && isset($_GET['end_date'])) {
        $meterIds = isset($_GET['meterId']) ? explode(',', $_GET['meterId']) : [];
        $suffixes = isset($_GET['suffixes']) ? explode(',', $_GET['suffixes']) : [];

        if (empty($meterIds) || empty($suffixes)) {
            echo json_encode(["error" => "Please provide valid meterIds and suffixes parameters."]);
            exit;
        }

        // Replace suffixes based on specific meter IDs
        foreach ($meterIds as $index => $meterId) {
            if ($meterId === "U_15") {
                $suffixes[$index] = "ActiveEnergy_Total_kWh";
            } elseif ($meterId === "U_7_EM7") {
                $suffixes[$index] = "ActiveEnergy_DelpRec_Wh";
            }
        }

        $startDate = $_GET['start_date'] . 'T00:00:00.000+05:00';
        $endDate = $_GET['end_date'] . 'T23:59:59.999+05:00';

        $projection = ['timestamp' => 1];
        foreach ($meterIds as $meterId) {
            foreach ($suffixes as $suffix) {
                $projection["{$meterId}_{$suffix}"] = 1;
            }
        }

        try {
            $pipeline = [
                ['$match' => ['timestamp' => ['$gte' => $startDate, '$lte' => $endDate]]],
                ['$project' => $projection],
                ['$sort' => ['timestamp' => 1]]
            ];

            $data = $collection->aggregate($pipeline)->toArray();

            $filteredData = array_map(function ($document) use ($meterIds, $suffixes) {
                $meterData = [];
                foreach ($meterIds as $meterId) {
                    foreach ($suffixes as $suffix) {
                        $key = "{$meterId}_{$suffix}";
                        if (isset($document[$key])) {
                            $meterData[$key] = $document[$key];
                        }
                    }
                }
                return ['timestamp' => $document['timestamp'], 'data' => $meterData];
            }, $data);

            $dailyConsumption = [];
            $firstValuesByDay = [];
            $lastValuesByDay = [];

            foreach ($filteredData as $document) {
                $currentDate = (new DateTime($document['timestamp']))->format('Y-m-d');

                foreach ($meterIds as $meterId) {
                    foreach ($suffixes as $suffix) {
                        $key = "{$meterId}_{$suffix}";

                        if (isset($document['data'][$key])) {
                            if (!isset($firstValuesByDay[$currentDate][$key])) {
                                $firstValuesByDay[$currentDate][$key] = $document['data'][$key];
                            }
                            $lastValuesByDay[$currentDate][$key] = $document['data'][$key];
                        }
                    }
                }
            }

            $dates = array_keys($firstValuesByDay);
            for ($i = 0; $i < count($dates) - 1; $i++) {
                $currentDate = $dates[$i];
                $nextDate = $dates[$i + 1];

                foreach ($meterIds as $meterId) {
                    foreach ($suffixes as $suffix) {
                        $key = "{$meterId}_{$suffix}";
                        if (isset($firstValuesByDay[$nextDate][$key])) {
                            $currentValue = $firstValuesByDay[$currentDate][$key];
                            $nextValue = $firstValuesByDay[$nextDate][$key];
                            $dailyConsumption[$currentDate][$key] = $nextValue - $currentValue;
                        }
                    }
                }
            }

            $lastDate = end($dates);
            foreach ($meterIds as $meterId) {
                foreach ($suffixes as $suffix) {
                    $key = "{$meterId}_{$suffix}";
                    if (isset($firstValuesByDay[$lastDate][$key]) && isset($lastValuesByDay[$lastDate][$key])) {
                        $firstValue = $firstValuesByDay[$lastDate][$key];
                        $lastValue = $lastValuesByDay[$lastDate][$key];
                        $dailyConsumption[$lastDate][$key] = $lastValue - $firstValue;
                    }
                }
            }

            $totalConsumption = [];
            foreach ($dailyConsumption as $date => $consumption) {
                foreach ($consumption as $key => $value) {
                    $meterId = null;
                    foreach ($meterNameMapping as $name => $id) {
                        if (strpos($key, $id) === 0) {
                            $meterId = $name;
                            break;
                        }
                    }

                    if ($meterId === null) {
                        continue;
                    }

                    $meterName = $meterId;

                    if (!isset($totalConsumption[$meterName])) {
                        $totalConsumption[$meterName] = 0;
                    }
                    $totalConsumption[$meterName] += $value;
                }
            }

            echo json_encode([
                'total_consumption' => $totalConsumption
            ]);
        } catch (Exception $e) {
            echo json_encode(["error" => "Error fetching data: " . $e->getMessage()]);
        }
    } else {
        echo json_encode(["error" => "Invalid request. Please provide start_date and end_date parameters."]);
    }
}
