<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

// Fetch data from the source API
$url = "http://43.204.118.114:6881/latestcbl";
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
// Define the keys to fetch for energy data
$energyKeys = [
   "FWD_ActiveEnergy_Wh", "Rev_ActiveEnergy_Wh", "ActiveEnergy_Total_kWh",
    "FWD_ReAInductiveEnergy_VARh", "FWD_ReACapacitiveEnergy_VARh",
    "Rev_ReAInductiveEnergy_VARh", "Rev_ReACapacitiveEnergy_VARh",
    "ReactiveEnergy_Total_kVARh", "TotalReactiveEnergy_Inductive_kVARh",
    "FWD_AppEnergy_VAh", "Rev_AppEnergy_VAh", "ApparentEnergy_Total_kVAh", "ActiveEnergy_Delivered_kWh"
];

// Check if the meter parameter is provided and valid
if ($meter && isset($meterTitles[$meter])) {
    $meterData = [
        "meter_id" => $meter,
        "meter_title" => $meterTitles[$meter]
    ];

    foreach ($energyKeys as $key) {
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
?>