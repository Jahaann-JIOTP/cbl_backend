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
    "voltage" => ["VoltageAB_V", "VoltageBC_V", "VoltageCA_V", "VoltageLL_V", "VoltageAN_V", "VoltageBN_V", "VoltageCN_V", "VoltageLN_V"],
    "current" => ["CurrentA_A", "CurrentC_A", "CurrentN_A", "CurrentAvg_A"],
    "power_factor" => ["PowerFactorA", "PowerFactorB", "PowerFactorC", "PowerFactorTotal"],
    "active_power" => ["ActivePowerA_kW","ActivePowerB_kW","ActivePowerC_kW","ActivePowerTotal_kW"],
    "reactive_power"  =>["ReactivePowerA_kVAR","ReactivePowerB_kVAR","ReactivePowerC_kVAR","ReactivePowerTotal_kVAR"],
    "apparent_power"  =>["ApparentPowerA_kVA","ApparentPowerB_kVA","ApparentPowerC_kVA", "ApparentPowerTotal_kVA"],
    "harmonics"  =>["HarmonicsTHDIA", "HarmonicsTHDIB","HarmonicsTHDIC","HarmonicsTHDIN","HarmonicsTHDIG", "HarmonicsTHDVAB","HarmonicsTHDVCA","HarmonicsTHDVBC","HarmonicsTHDVAN"],
    "active_energy" =>["ActiveEnergyDelivered_Wh", "ActiveEnergyReceived_Wh","ActiveEnergy_DelpRec_Wh","ActiveEnergy_DelmRec_Wh"],
    "reactive_energy"  => [ "ReactiveEnergyDelivered_VARh", "ReactiveEnergyReceived_VARh","ReactiveEnergy_DelpRec_VARh","ReactiveEnergy_DelmRec_VARh"],
    "apparent_energy"=>["ApparentEnergyDelivered_VAh"] 

    
   

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