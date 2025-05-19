<?php
require 'vendor/autoload.php';
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

function connectDB() {
    try {
        $client = new MongoDB\Client("mongodb://127.0.0.1:27017");
        return $client->iotdb;
    } catch (Exception $e) {
        echo json_encode(["error" => "Failed to connect to MongoDB: " . $e->getMessage()]);
        exit;
    }
}

$db = connectDB();
$collectionNew = $db->GCL_new;
$collectionActiveTags = $db->GCL_ActiveTags;

$collectionNew->createIndex(['timestamp' => 1, 'meterId' => 1]);
$collectionActiveTags->createIndex(['timestamp' => 1]);

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['start_date']) && isset($_GET['end_date'])) {
    $meterId = $_GET['meterId'] ?? null;
    $suffixes = isset($_GET['suffixes']) ? explode(',', $_GET['suffixes']) : [];

    // Format the start and end dates
    $startDate = $_GET['start_date'] . 'T00:00:00.000+05:00';
    $endDate = $_GET['end_date'] . 'T23:59:59.999+05:00';

    if (!$meterId || empty($suffixes)) {
        echo json_encode(["error" => "Please provide meterId and suffixes."]);
        exit;
    }

    try {
        // Construct the tags dynamically based on meterId and suffixes
        $tags = array_map(function($suffix) use ($meterId) {
            return "{$meterId}_{$suffix}";
        }, $suffixes);

        // Fetch data from GCL_ActiveTags
        $pipelineActiveTags = [
            [
                '$match' => [
                    'timestamp' => [
                        '$gte' => $startDate,
                        '$lte' => $endDate
                    ]
                ]
            ],
            [
                '$project' => array_merge(['timestamp' => 1], array_fill_keys($tags, 1))
            ]
        ];

        $dataActiveTags = $collectionActiveTags->aggregate($pipelineActiveTags)->toArray();

        // Format the output
        $formattedData = array_map(function($document) use ($tags) {
            $tagData = [];
            foreach ($tags as $tag) {
                if (isset($document[$tag])) {
                    $tagData[$tag] = $document[$tag];
                }
            }
            return [
                'timestamp' => $document['timestamp'],
                'data' => $tagData
            ];
        }, $dataActiveTags);

        echo json_encode($formattedData);
    } catch (Exception $e) {
        echo json_encode(["error" => "Error fetching data: " . $e->getMessage()]);
    }
} else {
    echo json_encode(["error" => "Invalid request. Please provide start_date, end_date, meterId, and suffixes parameters."]);
}
?>
