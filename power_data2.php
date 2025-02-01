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
    
  "U_15" => "Dryer",
   "U_21" => "Janitza",
   "U_22" => "Solar Hostels",
   
];

// Define the keys to fetch for each meter
$meterKeys = [
    "CurrentTHD_PH1", "CurrentTHD_PH2", "CurrentTHD_PH3",
    "VoltageTHD_PH1", "VoltageTHD_PH2", "VoltageTHD_PH3", "ActivePower_A_kW", "ActivePower_B_kW",
    "ActivePower_C_kW", "ActivePower_Total_kW", "ReactivePower_A_kVAR",
    "ReactivePower_B_kVAR", "ReactivePower_C_kVAR", "ReactivePower_Total_kVAR",
    "ApparentPower_A_kVA", "ApparentPower_B_kVA", "ApparentPower_C_kVA","ApparentPower_Total_kVA"
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
