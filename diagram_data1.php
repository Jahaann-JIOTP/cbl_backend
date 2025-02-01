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
    "F3_MainLine_Flowrate", "F3_MainLine_TotalFlow",
    "F4_Sewing2_Flowrate", "F4_Sewing2_TotalFlow",
    "F2_Airjet_Flowrate", "F2_Airjet_TotalFlow",
    "F6_Sewing1_Flowrate", "F6_Sewing1_TotalFlow",
    "F7_PG_Flowrate", "F7_PG_TotalFlow",
    "F1_GWP_Flowrate", "F1_GWP_TotalFlow",
    "F5_Textile_Flowrate", "F5_Textile_TotalFlow"
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
