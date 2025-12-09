<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

// Your backend logic to fetch data
$url = "http://43.204.118.114:6881/latestcbl";
$json = file_get_contents($url);

if ($json === false) {
    // Handle API errors
    echo json_encode(["error" => "Unable to fetch data from source API."]);
    exit();
}

$msg = json_decode($json, true);
if ($msg === null) {
    // Handle JSON decoding errors
    echo json_encode(["error" => "Invalid JSON response from source API."]);
    exit();
}

// Define all the keys you want to extract
$meterKeys = [
    "U_3_EM3_CurrentAvg_A", "U_3_EM3_AvgVoltageLL_V", "U_3_EM3_Activepower_Total_W",
    "U_4_EM4_CurrentAvg_A", "U_4_EM4_AvgVoltageLL_V", "U_4_EM4_Activepower_Total_W",
    "U_5_EM5_CurrentAvg_A", "U_5_EM5_AvgVoltageLL_V", "U_5_EM5_Activepower_Total_W",
    "U_6_EM6_CurrentAvg_A", "U_6_EM6_AvgVoltageLL_V", "U_6_EM6_Activepower_Total_W",
    "U_7_EM7_CurrentAvg_A", "U_7_EM7_VoltageLL_V", "U_7_EM7_ActivePowerTotal_kW",
    "U_8_EM8_CurrentAvg_A", "U_8_EM8_AvgVoltageLL_V", "U_8_EM8_Activepower_Total_W",
    "U_9_EM9_CurrentAvg_A", "U_9_EM9_VoltageLL_V", "U_9_EM9_ActivePowerTotal_kW",
    "U_10_EM10_CurrentAvg_A", "U_10_EM10_AvgVoltageLL_V", "U_10_EM10_Activepower_Total_W",
    "U_15_Voltage_LL_V","U_15_Current_Total_Amp","U_15_ActivePower_Total_kW",
    "U_21_Voltage_LL_V","U_21_Current_Total_Amp","U_21_ActivePower_Total_kW",
    "U_22_Voltage_LL_V","U_22_Current_Total_Amp","U_22_ActivePower_Total_kW"
    
];

// Prepare data for JSON output
$data = ["authorized" => true];

foreach ($meterKeys as $key) {
    $data[$key] = $msg[$key] ?? 0; // Use 0 as default if the key is missing
}

// Output data as JSON
echo json_encode($data);
exit();
?>
