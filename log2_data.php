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
    "voltage" => [ "VoltageLN_V", "Voltage_Ph1ToPh2_V", "Voltage_Ph2ToPh3_V", "Voltage_Ph3ToPh1_V", "Voltage_pH1ToN_V", "Voltage_pH2ToN_V", "Voltage_pH3ToN_V", "AvgVoltageLL_V"],
    "current" => ["CurrentPh1_A", "CurrentPh2_A", "CurrentPh3_A", "CurrentAvg_A"],
    "power_factor" => ["PF_PH1", "PF_PH2", "PF_PH3", "PF_Avg"],
    "active_power" => ["Activepower_PH1_W","Activepower_PH2_W","Activepower_PH3_W","Activepower_Total_W"],
    "reactive_power"  =>["ReAPower_PH1_VAR","ReAPower_PH2_VAR","ReAPower_PH3_VAR","ReAPower_Total_VAR"],
    "apparent_power"  =>["AppPower_PH1_VA","AppPower_PH2_VA","AppPower_PH3_VA", "AppPower_Total_VA"],
    "harmonics"  =>["VoltageTHD_PH1", "VoltageTHD_PH2","VoltageTHD_PH3","CurrentTHD_PH1","CurrentTHD_PH2", "CurrentTHD_PH3"],
    "active_energy" =>["FWD_ActiveEnergy_Wh", "Rev_ActiveEnergy_Wh"],
    "reactive_energy"  => [ "FWD_ReAInductiveEnergy_VARh", "FWD_ReACapacitiveEnergy_VARh","Rev_ReAInductiveEnergy_VARh","Rev_ReACapacitiveEnergy_VARh"],
    "apparent_energy"=>["FWD_AppEnergy_VAh", "Rev_AppEnergy_VAh"] 
    
    

];

// MongoDB connection
function connectDB() {
    try {
        $client = new MongoDB\Client("mongodb://Jamal:rVl8O8iMN@43.204.118.114:57019/CBL?authSource=admin&readPreference=primary&appname=MongoDB%20Compass&ssl=false");
        return $client->CBL;
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