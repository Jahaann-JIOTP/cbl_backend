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

// Get the meter ID from the query string (if provided)
$meter = $_GET['meter'] ?? null;

// Define the meter-to-title mapping
$meterTitles = [
    "U_15" => "Dryer",
    "U_21" => "Janitza",
    "U_22" => "Solar Hostels",
];

// Define the keys to fetch for each meter
$meterKeys = [
    "Voltage_AN_V", "Voltage_BN_V", "Voltage_CN_V", "Voltage_LN_V", "Current_A_A", "Current_C_A",
    "Current_N_Amp", "Current_Avg_A", "ActivePower_A_kW", "ActivePower_B_kW", "ActivePower_C_kW", 
    "ActivePower_Total_kW", "ReactivePower_A_kVAR", "ReactivePower_Total_kVAR", "Voltage_AB_V", 
    "Voltage_BC_V", "Voltage_CA_V", "Frequency_Hz", "Voltage_LL_V", "ApparentPower_A_kVA", 
    "ApparentPower_B_kVA", "ApparentPower_C_kVA", "ApparentPower_Total_kVA", "Current_CN_Amp", 
    "Current_BN_Amp", "Current_AN_Amp", "Current_Total_Amp", "PowerFactor_Total",
];

// Check if the meter parameter is provided and valid
if ($meter && isset($meterTitles[$meter])) {
    $meterData = [
        "meter_id" => $meter,
        "meter_title" => $meterTitles[$meter]
    ];

    foreach ($meterKeys as $key) {
        $fullKey = $meter . "_" . $key;

        // Fetch the value and handle missing data
        $value = isset($msg[$fullKey]) ? round($msg[$fullKey], 2) : 0;

        // Special handling for U_15 and PowerFactor_Total
        if ($meter === "U_15" && $key === "PowerFactor_Total") {
            $value = round($value / 100, 2); // Divide PowerFactor_Total by 100
        }

        $meterData[$key] = $value;
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

// Debugging output to ensure logic is applied
if ($meter === "U_15") {
    $data["debug"] = [
        "PowerFactor_Total_before_division" => isset($msg["U_15_PowerFactor_Total"]) ? $msg["U_15_PowerFactor_Total"] : null,
        "PowerFactor_Total_after_division" => isset($meterData["PowerFactor_Total"]) ? $meterData["PowerFactor_Total"] : null,
    ];
}

// Output the data as JSON
echo json_encode($data, JSON_PRETTY_PRINT);
exit();
