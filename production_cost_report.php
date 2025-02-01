<?php
require 'vendor/autoload.php';
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
date_default_timezone_set('Asia/Karachi');

// MongoDB Connection
function connectDB()
{
    try {
        $client = new MongoDB\Client("mongodb://admin:cisco123@13.234.241.103:27017/?authSource=iotdb&readPreference=primary&ssl=false");
        return $client->iotdb;
    } catch (Exception $e) {
        echo json_encode(["error" => "Failed to connect to MongoDB: " . $e->getMessage()]);
        exit;
    }
}

// MySQL Connection
function connectMySQL()
{
    $servername = "15.206.128.214";
    $username = "jahaann";
    $password = "Jahaann#321";
    $dbname = "gcl";

    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    return $conn;
}

$db = connectDB();
$collection = $db->CBL_b;
$collection->createIndex(['timestamp' => 1]);

// Meter ID to Name Mapping
$meterNameMapping = [
    "GWP_total_flow" => "F1_GWP",
    "Airjet_total_flow" => "F2_Airjet",
    "Sewing2_total_flow" => "F4_Sewing2",
    "Textile_total_flow" => "F5_Textile",
    "PG_total_flow" => "F6_Sewing1",
    "Sewing1_total_flow" => "F7_PG",
];

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['start_date']) && isset($_GET['end_date'])) {
    // $meterIds = $input['meterIds'] ?? []; // Expecting an array of meter IDs
    // $suffixes = isset($input['suffixes']) ? explode(',', $input['suffixes']) : [];
    // Parse `meterIds` and `suffixes` from query parameters and convert to PHP arrays
    $meterIds = isset($_GET['meterIds']) ? json_decode($_GET['meterIds'], true) : [];
    $suffixes = isset($_GET['suffixes']) ? json_decode($_GET['suffixes'], true) : [];

    // Resulting arrays
    $meterIds = is_array($meterIds) ? $meterIds : []; // Ensure it's an array
    $suffixes = is_array($suffixes) ? $suffixes : []; // Ensure it's an array
    $startDate = $_GET['start_date'] . 'T00:00:00.000+05:00';
    $endDate = new DateTime($_GET['end_date']);
    $endDate->modify('+1 day');
    $endDate = $endDate->format('Y-m-d') . 'T23:59:59.999+05:00';

    $projection = ['timestamp' => 1];
    foreach ($meterIds as $meterId) {
        foreach ($suffixes as $suffix) {
            $projection["{$meterId}_{$suffix}"] = 1;
        }
    }

    try {
        // MongoDB Data
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

        // Calculate Daily Consumption
        $dailyConsumption = [];
        $firstValuesByDay = [];

        foreach ($filteredData as $document) {
            $currentDate = (new DateTime($document['timestamp']))->format('Y-m-d');

            foreach ($meterIds as $meterId) {
                foreach ($suffixes as $suffix) {
                    $key = "{$meterId}_{$suffix}";
                    if (isset($document['data'][$key])) {
                        if (!isset($firstValuesByDay[$currentDate][$key])) {
                            $firstValuesByDay[$currentDate][$key] = $document['data'][$key];
                        }
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
                    $mappedName = array_search($meterId, $meterNameMapping) ?? $meterId;

                    $firstValue = $firstValuesByDay[$currentDate][$key] ?? 0;
                    $nextFirstValue = $firstValuesByDay[$nextDate][$key] ?? 0;

                    $dailyConsumption[$currentDate][$mappedName] = ($nextFirstValue - $firstValue) * 1000 / 35.31;
                }
            }
        }

        // SQL Production Data
        $conn = connectMySQL();
        $sql = "SELECT id, GWP, Airjet, Sewing2, Textile, Sewing1, PG, date 
                FROM production 
                WHERE date BETWEEN '{$_GET['start_date']}' AND '{$_GET['end_date']}'";
        $result = $conn->query($sql);

        $sqlData = [];
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sqlData[$row['date']] = [
                    "GWP" => (int)$row["GWP"],
                    "Airjet" => (int)$row["Airjet"],
                    "Sewing2" => (int)$row["Sewing2"],
                    "Textile" => (int)$row["Textile"],
                    "Sewing1" => (int)$row["Sewing1"],
                    "PG" => (int)$row["PG"]
                ];
            }
        }
        $conn->close();

        // Flow Per Production
        $flowPerProduction = [];
        foreach ($dailyConsumption as $date => $flows) {
            if (isset($sqlData[$date])) {
                foreach ($flows as $meter => $flow) {
                    $productionKey = str_replace("_total_flow", "", $meter);
                    $production = $sqlData[$date][$productionKey] ?? 0;

                    $flowPerProduction[$date][$meter] = $production > 0 ? $flow / $production : 0;
                }
            }
        }

        echo json_encode([
            'daily_consumption' => $dailyConsumption,
            'flow_per_production' => $flowPerProduction
        ]);
    } catch (Exception $e) {
        echo json_encode(["error" => "Error fetching data: " . $e->getMessage()]);
    }
} else {
    echo json_encode(["error" => "Invalid request. Please provide start_date and end_date parameters."]);
}
