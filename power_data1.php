<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

// Fetch data from the source API
$url = "http://13.234.241.103:1880/latestcbl";
$json = file_get_contents($url);

if ($json === false) {
    echo json_encode(["error" => "Unable to fetch data from source API."]);
    exit();
}

$msg = json_decode($json, true);

if ($msg === null) {
    echo json_encode(["error" => "Invalid JSON response from source API."]);
    exit();
}

// Get the meter ID from the query string
$meter = $_GET['meter'] ?? null;

// Define the meter-to-title mapping
$meterTitles = [
    
    "U_7_EM7" => "New Centac Com#2",
    "U_9_EM9" => "New Centac Com#1",
   
];

// Define the keys to fetch for each meter
$meterKeys = [
    "HarmonicsTHDIA", "HarmonicsTHDIB", "HarmonicsTHDIC","HarmonicsTHDIG","HarmonicsTHDIN","HarmonicsTHDVAB","HarmonicsTHDVAN","HarmonicsTHDVBC","HarmonicsTHDVBN","HarmonicsTHDVCA","HarmonicsTHDVCN","HarmonicsTHDVLL","HarmonicsTHDVLN", "ActivePowerA_kW", "ActivePowerB_kW",
    "ActivePowerC_kW", "ActivePowerTotal_kW", "ReactivePowerA_kVAR",
    "ReactivePowerB_kVAR", "ReactivePowerC_kVAR", "ReactivePowerTotal_kVAR",
    "ApparentPowerA_kVA", "ApparentPowerB_kVA", "ApparentPowerC_kVA","ApparentPowerTotal_kVA"
];

// Check if the meter parameter is provided and valid
if ($meter && isset($meterTitles[$meter])) {
    $meterData = [
        "meter_id" => $meter,
        "meter_title" => $meterTitles[$meter]
    ];

    foreach ($meterKeys as $key) {
        $fullKey = $meter . "_" . $key;
        $meterData[$key] = isset($msg[$fullKey]) ? round($msg[$fullKey], 2) : 0;
    }

    $response = [
        "authorized" => true,
        "meter" => $meterData
    ];
} else {
    // If no meter is specified or invalid meter, return an error
    $response = [
        "authorized" => false,
        "error" => "Invalid or missing meter ID. Please specify a valid meter ID."
    ];
}

// Output the response as JSON
echo json_encode($response, JSON_PRETTY_PRINT);
exit();
