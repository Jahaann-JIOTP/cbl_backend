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

// Get the meter ID from the query string (if provided)
$meter = $_GET['meter'] ?? null;

// Define the meter-to-title mapping
$meterTitles = [
    "U_3_EM3" => "Ozen 350",
    "U_4_EM4" => "Atlas Copco",
    "U_5_EM5" => "Compressor Aux",
    "U_6_EM6" => "Ganzair Compressor",
    
    "U_8_EM8" => "ML-132",
    
   
  
    "U_10_EM10" => "Kaeser Compressor",
   
];

// Define the keys to fetch for each meter
$meterKeys = [
    "CurrentPh3_A", "CurrentPh2_A", "CurrentPh1_A", "CurrentAvg_A",
    "Voltage_Ph3ToPh1_V", "Voltage_Ph2ToPh3_V", "Voltage_Ph1ToPh2_V",
    "AvgVoltageLL_V", "Voltage_pH1ToN_V", "Voltage_pH2ToN_V",
    "Voltage_pH3ToN_V", "VoltageLN_V", "Activepower_PH3_W",
    "Activepower_PH2_W", "Activepower_PH1_W", "Activepower_Total_W",
    "ReAPower_Total_VAR", "AppPower_Total_VA", "Freq_Hz", "PF_Avg",
    "PF_PH1", "PF_PH2", "PF_PH3"
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

    $data = [
        "authorized" => true,
        "meter" => $meterData
    ];
} else {
    // If no meter is specified or invalid meter, return an error
    $data = [
        "authorized" => false,
        "error" => "Invalid or missing meter ID. Please specify a valid meter ID."
    ];
}

// Output the data as JSON
echo json_encode($data, JSON_PRETTY_PRINT);
exit();
