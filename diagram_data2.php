<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

// Your backend logic to fetch data
$url = "http://13.234.241.103:1880/latestcbl";
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
    "U11_SM11_PowerYield_EXP_Daily_kWh", "U11_SM11_PowerYield_EXP_Total_kWh","U11_SM11_ActivePower_EXP_Total_kW","U11_SM11_Min_Adj_ActivePower_kW", "U11_SM11_Max_Adj_ActivePower_kW", "U11_SM11_ReAPower_EXP_Total_var","U11_SM11_Min_Adj_ReAPower_kvar","U11_SM11_Max_Adj_ReAPower_kvar","U11_SM11_Rated_ActivePower_kw","U11_SM11_Rated_ReAPower_kvar","U11_SM11_GridConnectedDevices","U11_SM11_OFF_Grid_Devices","U12_SM12_PowerYield_EXP_Daily_kWh","U12_SM12_PowerYield_EXP_Total_kWh","U12_SM12_ActivePower_EXP_Total_kW","U12_SM12_Min_Adj_ActivePower_kW","U12_SM12_Max_Adj_ActivePower_kW","U12_SM12_ReAPower_EXP_Total_var","U12_SM12_Min_Adj_ReAPower_kvar","U12_SM12_Max_Adj_ReAPower_kvar","U12_SM12_Rated_ActivePower_kw","U12_SM12_Rated_ReAPower_kvar", "U12_SM12_GridConnectedDevices","U12_SM12_OFF_Grid_Devices"
    
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
