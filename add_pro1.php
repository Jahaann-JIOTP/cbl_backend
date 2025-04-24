<?php
require 'vendor/autoload.php'; // Load MongoDB PHP Library
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

ob_start(); // Start output buffering

// Connect to MongoDB
$client = new MongoDB\Client("mongodb://admin:cisco123@13.234.241.103:27017/?authSource=iotdb&readPreference=primary&appname=MongoDB%20Compass&ssl=false");
$collection = $client->iotdb->CBL_b; // Replace 'iotdb' with your actual DB name

// Get start and end dates from URL
$start_date = isset($_GET['start']) ? $_GET['start'] : null;
$end_date = isset($_GET['end']) ? $_GET['end'] : null;

// If no dates are provided, set default to last 7 days (today and past 6 days)
if (empty($start_date) || empty($end_date)) {
    $end_date = date('Y-m-d'); // Today's date
    $start_date = date('Y-m-d', strtotime('-6 days')); // 6 days ago
}

// Convert start and end dates to `Y-m-d` format
$startDateStr = date('Y-m-d', strtotime($start_date));
$endDateStr = date('Y-m-d', strtotime($end_date));

$startDateISO = new MongoDB\BSON\UTCDateTime(strtotime($start_date . ' 00:00:00') * 1000);
$endDateISO = new MongoDB\BSON\UTCDateTime(strtotime($end_date . ' 23:59:59') * 1000);


  






$cursor = $collection->find([]);

// Initialize an array to store the filtered data
$dailyData = [];

// List of tags to process
$tags = [
    'U_3_EM3_Activepower_Total_W' => 'W',
    'U_4_EM4_Activepower_Total_W' => 'W',
    'U_6_EM6_Activepower_Total_W' => 'W',
    'U_21_ActivePower_Total_kW' => 'kW',
    'U_8_EM8_Activepower_Total_W' => 'W',
    'U_9_EM9_ActivePowerTotal_kW' => 'kW',
    'U_7_EM7_ActivePowerTotal_kW' => 'kW',
    'U_22_ActivePower_Total_kW' => 'kW',
    // 'U_5_EM5_Activepower_Total_W' => 'W',
    // 'U_10_EM10_Activepower_Total_W' => 'W',
];

// ✅ Main Line Flowrate Calculation
$mainLineTag = 'F3_MainLine_Flowrate';

// Define m³ and kW values for each unit
$unitValues = [
    'U_3' => ['m3' => 42.8, 'kw' => 250],
    'U_4' => ['m3' => 52, 'kw' => 250],
    'U_6' => ['m3' => 16.2, 'kw' => 90],
    'U_9' => ['m3' => 101.44, 'kw' => 600],
    'U_7' => ['m3' => 101.44, 'kw' => 600],
    'U_8' => ['m3' => 23.1, 'kw' => 152],
    'U_21' => ['m3' => 23.5, 'kw' => 160],
    // 'U_5' => ['m3' => 23.1, 'kw' => 152],
    // 'U_10' => ['m3' => 23.5, 'kw' => 160],
];

// Iterate through all documents in the cursor
foreach ($cursor as $document) {
    if (isset($document['timestamp'])) {
        try {
            if ($document['timestamp'] instanceof MongoDB\BSON\UTCDateTime) {
                $timestamp = $document['timestamp']->toDateTime();
                $dateKey = $timestamp->format('Y-m-d');
            } elseif (is_string($document['timestamp'])) {
                $timestampParts = explode('T', $document['timestamp']);
                $dateKey = $timestampParts[0];
                error_log("Checking Document: " . json_encode($document)); // Check entire document

if (isset($document['U_21_ActivePower_Total_kW'])) {
    error_log("Found U_21 for date: $dateKey | Value: " . $document['U_21_ActivePower_Total_kW']);
} else {
    error_log("MISSING U_21 for date: $dateKey");
}

            } else {
                continue;
            }
        } catch (Exception $e) {
            continue;
        }
    } else {
        continue;
    }
    

    // Ensure only requested dates are returned
    if ($dateKey < $startDateStr || $dateKey > $endDateStr) {
        continue;
    }

    if (!isset($dailyData[$dateKey])) {
        $dailyData[$dateKey] = [
            'documentCount' => 0,
            'totalPower' => [],
            'totalDailyAirFlow' => 0,
            'mainLineTotalFlow' => 0,
            'mainLineCount' => 0,
        ];

        foreach ($tags as $tag => $unit) {
            $dailyData[$dateKey]['totalPower'][$tag] = 0;
        }
    }

    // ✅ Sum U_7 and U_9 and assign to U_9
    $u22Value = isset($document['U_22_ActivePower_Total_kW']) ? $document['U_22_ActivePower_Total_kW'] : 0;
    $u9Value = isset($document['U_9_EM9_ActivePowerTotal_kW']) ? $document['U_9_EM9_ActivePowerTotal_kW'] : 0;
    $document['U_9_EM9_ActivePowerTotal_kW'] = $u22Value + $u9Value; // ✅ Overwrite U_9 with (U_22 + U_9)
    
    foreach ($tags as $tag => $unit) {
        if (isset($document[$tag])) {
            $dailyData[$dateKey]['totalPower'][$tag] += $document[$tag];
        }
    }

    // ✅ Accumulate `F3_MainLine_TotalFlow`
    if (isset($document[$mainLineTag])) {
        $dailyData[$dateKey]['mainLineTotalFlow'] += $document[$mainLineTag];
        $dailyData[$dateKey]['mainLineCount']++;
    }

   
    $dailyData[$dateKey]['documentCount'] += 1;
}

// ✅ Apply Logic for AirFlow, Efficiency, and Main Line Calculation
$response = [];

foreach ($dailyData as $date => $data) {
    $dateEntry = [
        'date' => $date,
        'tags' => [],
        'totalDailyAirFlow' => 0,
    ];

    foreach ($tags as $tag => $unit) {
        $averagePowerPerDoc = ($data['documentCount'] > 0)
            ? $data['totalPower'][$tag] / $data['documentCount'] 
            : 0;

            $unitTagParts = explode('_', $tag); // Split string at underscore (_)
            $unitTag = $unitTagParts[0] . "_" . $unitTagParts[1]; // First two parts
            
        if (isset($unitValues[$unitTag])) {
            $m3 = $unitValues[$unitTag]['m3'];
            $kw = $unitValues[$unitTag]['kw'];
            $airFlow = ($m3 / $kw) * $averagePowerPerDoc;

            $dateEntry['totalDailyAirFlow'] += $airFlow;

            $efficiency = 0;
            if (abs($airFlow) > 0 && abs($m3) > 0) {
                $efficiency = ($airFlow / $m3) * 100;
            }

            $dateEntry['tags'][] = [
                'tag' => $tag,
                'airFlow' => number_format($airFlow, 2) . "",
                'efficiency' => number_format($efficiency, 2) . ""
            ];
        }
    }

    // ✅ Calculate daily average of `F3_MainLine_TotalFlow`
    $averageMainLineFlow = ($data['mainLineCount'] > 0) 
        ? $data['mainLineTotalFlow'] / $data['mainLineCount']
        : 0;

    // ✅ Calculate the difference
    $flowDifference = $averageMainLineFlow - $dateEntry['totalDailyAirFlow'];

    $dateEntry['mainLineAverageFlow'] = number_format($averageMainLineFlow, 2) . " m³/day";
    $dateEntry['totalDailyAirFlow'] = number_format($dateEntry['totalDailyAirFlow'], 2) . " m³/day";
    $dateEntry['flowDifference'] = number_format($flowDifference, 2) . " m³/day";

    $response[] = $dateEntry;
}

// ✅ Flush the output buffer before sending JSON response
ob_clean(); // ✅ Ensure no extra output
header('Content-Type: application/json; charset=utf-8');
echo json_encode($response, JSON_PRETTY_PRINT);
exit;
?>
