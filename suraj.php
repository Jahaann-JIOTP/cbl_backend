<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

// Dummy data to simulate the response
$msg = [
    "U_3_EM3_CurrentAvg_A" => 1224,
    "U_3_EM3_AvgVoltageLL_V" => 400,
    "U_3_EM3_Activepower_Total_W" => 720,
    "U_4_EM4_CurrentAvg_A" => 1190,
    "U_4_EM4_AvgVoltageLL_V" => 400,
    "U_4_EM4_Activepower_Total_W" => 700,
    "U_5_EM5_CurrentAvg_A" => 649,
    "U_5_EM5_AvgVoltageLL_V" => 11,
    "U_5_EM5_Activepower_Total_W" => 4200,
    "U_6_EM6_CurrentAvg_A" => 1445,
    "U_6_EM6_AvgVoltageLL_V" => 400,
    "U_6_EM6_Activepower_Total_W" => 850,
    "U_7_EM7_CurrentAvg_A" => 605,
    "U_7_EM7_VoltageLL_V" => 11,
    "U_7_EM7_ActivePowerTotal_kW" => 3920,
    "U_8_EM8_CurrentAvg_A" => 13.9,
    "U_8_EM8_AvgVoltageLL_V" => 229.8,
    "U_8_EM8_Activepower_Total_W" => 4600,
    "U_9_EM9_CurrentAvg_A" => 766,
    "U_9_EM9_VoltageLL_V" => 11,
    "U_9_EM9_ActivePowerTotal_kW" => 4960,
    "U_10_EM10_CurrentAvg_A" => 18.3,
    "U_10_EM10_AvgVoltageLL_V" => 400,
    "U_10_EM10_Activepower_Total_W" => 6000,
    "U_15_Voltage_LL_V" => 400,
    "U_15_Current_Total_Amp" => 3808,
    "U_15_ActivePower_Total_kW" => 2240,
    "U_21_Voltage_LL_V" => 11,
    "U_21_Current_Total_Amp" => 574,
    "U_21_ActivePower_Total_kW" => 3720,
    "U_22_Voltage_LL_V" => 400,
    "U_22_Current_Total_Amp" => 6358,
    "U_22_ActivePower_Total_kW" => 3740
];

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
    "U_15_Voltage_LL_V", "U_15_Current_Total_Amp", "U_15_ActivePower_Total_kW",
    "U_21_Voltage_LL_V", "U_21_Current_Total_Amp", "U_21_ActivePower_Total_kW",
    "U_22_Voltage_LL_V", "U_22_Current_Total_Amp", "U_22_ActivePower_Total_kW"
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
