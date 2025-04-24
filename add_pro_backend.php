<?php
require 'vendor/autoload.php';
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

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
    $servername = "65.0.16.20";
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
    $meterIds = ['F1_GWP', 'F2_Airjet', 'F4_Sewing2', 'F5_Textile', 'F6_Sewing1', 'F7_PG'];
    $suffixes = ['TotalFlow'];

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

        $filteredData = [];
        foreach ($data as $document) {
            $currentDate = (new DateTime($document['timestamp']))->format('Y-m-d');
            $meterData = [];

            foreach ($meterIds as $meterId) {
                foreach ($suffixes as $suffix) {
                    $key = "{$meterId}_{$suffix}";
                    if (isset($document[$key])) {
                        $meterData[$key] = $document[$key];
                    }
                }
            }

            $filteredData[$currentDate][] = ['timestamp' => $document['timestamp'], 'data' => $meterData];
        }

        // Calculate Daily Consumption
        $dailyConsumption = [];
        foreach ($filteredData as $date => $records) {
            $firstValidF6 = null;
            $firstF7 = null;

            // Get valid F6 value for the current date
            foreach ($records as $record) {
                $data = $record['data'];
                if (isset($data['F6_Sewing1_TotalFlow'])) {
                    if ($firstF7 === null && isset($data['F7_PG_TotalFlow'])) {
                        $firstF7 = $data['F7_PG_TotalFlow'];
                    }

                    if ($firstValidF6 === null) {
                        // Ignore F6 value if its whole number matches F7
                        if (floor($data['F6_Sewing1_TotalFlow']) != floor($firstF7)) {
                            $firstValidF6 = $data['F6_Sewing1_TotalFlow'];
                        }
                    }
                }
            }

            $nextDate = (new DateTime($date))->modify('+1 day')->format('Y-m-d');
            $nextFirstValidF6 = null;
            $nextFirstF7 = null;

            // Get valid F6 value for the next date
            if (isset($filteredData[$nextDate])) {
                foreach ($filteredData[$nextDate] as $record) {
                    $data = $record['data'];
                    if (isset($data['F7_PG_TotalFlow']) && $nextFirstF7 === null) {
                        $nextFirstF7 = $data['F7_PG_TotalFlow'];
                    }

                    if (isset($data['F6_Sewing1_TotalFlow'])) {
                        if (floor($data['F6_Sewing1_TotalFlow']) != floor($nextFirstF7)) {
                            $nextFirstValidF6 = $data['F6_Sewing1_TotalFlow'];
                            break;
                        }
                    }
                }
            }

            foreach ($meterIds as $meterId) {
                foreach ($suffixes as $suffix) {
                    $key = "{$meterId}_{$suffix}";
                    $mappedName = array_search($meterId, $meterNameMapping) ?? $meterId;

                    $firstValue = ($meterId === 'F6_Sewing1' && $firstValidF6 !== null) ? $firstValidF6 : ($filteredData[$date][0]['data'][$key] ?? 0);
                    $nextFirstValue = ($meterId === 'F6_Sewing1' && $nextFirstValidF6 !== null) ? $nextFirstValidF6 : ($filteredData[$nextDate][0]['data'][$key] ?? 0);

                    $dailyConsumption[$date][$mappedName] = ($nextFirstValue - $firstValue) * 1000 / 35.31;
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
?>
