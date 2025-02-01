<?php
require 'vendor/autoload.php';

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Suppress deprecated notices
error_reporting(E_ALL & ~E_DEPRECATED);

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

// Tag groups for filtering
$tagGroups = [
    "voltage" => [ "Voltage_AN_V", "Voltage_BN_V", "Voltage_CN_V", "Voltage_LN_V", "Voltage_AB_V", "Voltage_BC_V", "Voltage_CA_V", "Voltage_LL_V"],
    "current" => ["Current_AN_Amp", "Current_BN_Amp", "Current_CN_Amp", "Current_Total_Amp"],
    "power_factor" => ["PowerFactor_Total"],
    "active_power" => ["ActivePower_A_kW","ActivePower_B_kW","ActivePower_C_kW","ActivePower_Total_kW"],
    "reactive_power"  =>["ReactivePower_A_kVAR","ReactivePower_B_kVAR","ReactivePower_C_kVAR","ReactivePower_Total_kVAR"],
    "apparent_power"  =>["ApparentPower_A_kVA","ApparentPower_B_kVA","ApparentPower_C_kVA", "ApparentPower_Total_kVA"],
    // "harmonics"  =>["VoltageTHD_PH1", "VoltageTHD_PH2","VoltageTHD_PH3","CurrentTHD_PH1","CurrentTHD_PH2", "CurrentTHD_PH3"],
    "active_energy" =>["ActiveEnergy_Delivered_kWh", "ActiveEnergy_Received_kWh", "ActiveEnergy_Total_kWh"],
    "reactive_energy"  => [ "ReactiveEnergy_Total_kVARh"],
    "apparent_energy"=>["ApparentEnergy_Total_kVAh"] 
    
   
    

];

// MongoDB connection
function connectDB() {
    try {
        $client = new MongoDB\Client("mongodb://admin:cisco123@13.234.241.103:27017/?authSource=iotdb&readPreference=primary&appname=MongoDB%20Compass&ssl=false");
        return $client->iotdb;
    } catch (Exception $e) {
        echo json_encode(["error" => "Failed to connect to MongoDB: " . $e->getMessage()]);
        exit;
    }
}

$db = connectDB();
$collection = $db->CBL_b;

// Fetch parameters
$type = $_GET['type'] ?? null;
$meters = $_GET['meters'] ?? null;
$start_date = $_GET['start_date'] ?? null;
$end_date = $_GET['end_date'] ?? null;

// Validate input
if (!$type || !$meters || !$start_date || !$end_date) {
    echo json_encode(["error" => "Missing required parameters: type, meters, start_date, or end_date"]);
    exit;
}

if (!array_key_exists($type, $tagGroups)) {
    echo json_encode(["error" => "Invalid type specified. Allowed types: " . implode(', ', array_keys($tagGroups))]);
    exit;
}

$tagsToFetch = $tagGroups[$type];
$meterIds = explode(',', $meters);

try {
    // Build query
    $query = [
        'timestamp' => [
            '$gte' => $start_date . 'T00:00:00.000+05:00',
            '$lte' => $end_date . 'T23:59:59.999+05:00',
        ],
    ];

    // Fetch data
    $data = $collection->find($query)->toArray();

    if (empty($data)) {
        echo json_encode([
            "success" => false,
            "message" => "No documents found for the specified date range.",
        ]);
        exit;
    }

    // Process results
    $results = [];
    foreach ($data as $item) {
        foreach ($meterIds as $meterId) {
            $entry = [
                'time' => isset($item['timestamp'])
                    ? (is_string($item['timestamp']) ? $item['timestamp'] : $item['timestamp']->toDateTime()->format('Y-m-d H:i:s'))
                    : null,
                'meterId' => $meterId,
            ];

            foreach ($tagsToFetch as $tag) {
                $field = "{$meterId}_{$tag}";
                if (isset($item[$field])) {
                    $entry[$tag] = $item[$field];
                }
            }

            if (count($entry) > 2) {
                $results[] = $entry;
            }
        }
    }

    echo json_encode(["success" => true, "data" => $results]);
} catch (Exception $e) {
    echo json_encode(["error" => "Error fetching data: " . $e->getMessage()]);
    exit;
}
?>