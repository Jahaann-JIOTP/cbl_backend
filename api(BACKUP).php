<?php
require 'vendor/autoload.php';
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

function getDateRange($range) {
    $currentDate = date('Y-m-d\TH:i:s.vP');
    switch ($range) {
        case 'last7days':
            $startDate = date('Y-m-d', strtotime('-7 days')) . 'T00:00:00.000+05:00';
            $endDate = date('Y-m-d', strtotime('-1 day')) . 'T23:59:59.999+05:00';
            break;
        case 'thisweek':
            $startDate = date('Y-m-d', strtotime('last Sunday')) . 'T00:00:00.000+05:00';
            $endDate = date('Y-m-d') . 'T23:59:59.999+05:00';
            break;
        case 'lastweek':
            $startDate = date('Y-m-d', strtotime('last Sunday -7 days')) . 'T00:00:00.000+05:00';
            $endDate = date('Y-m-d', strtotime('last Saturday')) . 'T23:59:59.999+05:00';
            break;
        case 'thismonth':
            $startDate = date('Y-m-01') . 'T00:00:00.000+05:00';
            $endDate = date('Y-m-d') . 'T23:59:59.999+05:00';
            break;
        case 'thisyear':
            $startDate = date('Y-01-01') . 'T00:00:00.000+05:00';
            $endDate = date('Y-m-d') . 'T23:59:59.999+05:00';
            break;
        default:
            echo json_encode(["error" => "Invalid range parameter."]);
            exit;
    }
    return [$startDate, $endDate];
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['range'])) {
    $meterId = $_GET['meterId'] ?? null;
    $suffixes = isset($_GET['suffixes']) ? explode(',', $_GET['suffixes']) : [];
    list($startDate, $endDate) = getDateRange($_GET['range']);

    $projection = ['timestamp' => 1]; // Always include timestamp

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
        // If no meterId, fetch all fields for the date range
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
            ]
        ];

        $data = $collection->aggregate($pipeline)->toArray();

        $filteredData = array_map(function($document) use ($meterId) {
            $meterData = [];
            foreach ($document as $key => $value) {
                if ($meterId) {
                    if (strpos($key, "{$meterId}_") === 0) {
                        // Keep the full tag name with the meter ID prefix
                        $meterData[$key] = $value;
                    }
                } else {
                    $meterData[$key] = $value; // Include all fields if no meterId is specified
                }
            }
            return ['timestamp' => $document['timestamp'], 'data' => $meterData];
        }, $data);
        

        echo json_encode($filteredData);
    } catch (Exception $e) {
        echo json_encode(["error" => "Error fetching data: " . $e->getMessage()]);
    }
} else {
    echo json_encode(["error" => "Invalid request. Please provide range parameter."]);
}
?>
