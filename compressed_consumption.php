<?php
require 'vendor/autoload.php';
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
date_default_timezone_set('Asia/Karachi');

$startDate = $_GET['startDate'];
$endDate = $_GET['endDate'];
$dateRangeLabel = $_GET['Label'];

function connectDB()
{
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
$current_date = date("Y-m-d");

if ($dateRangeLabel == 'Custom Range') {
    $prevStartDate = date('Y-m-d', strtotime($startDate . ' -1 day'));
    $mongotime1 = new MongoDB\BSON\UTCDateTime(strtotime($prevStartDate . 'T23:45:00+05:00'));
    $val1 = json_decode(json_encode($mongotime1), true);
    foreach ($val1 as $key => $value) {
        foreach ($value as $sub_key => $sub_value) {
            $a1 = $sub_value;
        }
    }
    $start_date1 = intval($a1);

    $mongotime2 = new MongoDB\BSON\UTCDateTime(strtotime($endDate . 'T23:59:18+05:00'));
    $val2 = json_decode(json_encode($mongotime2), true);
    foreach ($val2 as $key => $value) {
        foreach ($value as $sub_key => $sub_value2) {
            $a2 = $sub_value2;
        }
    }
    $new_end1 = intval($a2);

    $where = array(
        'UNIXtimestamp' =>  array('$gte' => $start_date1, '$lte' => $new_end1)
    );

    $select_fields = [
        'PLC_Date_Time' => 1,
        'UNIXtimestamp' => 1,
        'F3_MainLine_TotalFlow' => 1,
        'U_3_EM3_TotalActiveEnergy_kWh' => 1,
        'U_4_EM4_TotalActiveEnergy_kWh' => 1,
        'U_5_EM5_TotalActiveEnergy_kWh' => 1,
        'U_6_EM6_TotalActiveEnergy_kWh' => 1,
        'U_7_EM7_ActiveEnergyDelivered_Wh' => 1,
        'U_8_EM8_TotalActiveEnergy_kWh' => 1,
        'U_9_EM9_ActiveEnergyDelivered_Wh' => 1,
        'U_10_EM10_TotalActiveEnergy_kWh' => 1,
        'U_21_ActiveEnergy_Delivered_kWh' => 1,
        'U_15_ActiveEnergy_Delivered_kWh' => 1,
        'U_22_ActiveEnergy_Delivered_kWh' => 1,
    ];

    $options = ['projection' => $select_fields];
    $cursor = $collection->find($where, $options);
    $docs = $cursor->toArray();

    $previousEnergyValue = null;
    $previousFlowValue = null;
    $data = [];
    $ratioSum = 0;
    $count = 0;
    $previousEnergyValue = null;
    $previousFlowValue = null;
    
    foreach ($docs as $document) {
        $unixTimestamp = $document['UNIXtimestamp'];
        $humanDate = date('Y-m-d H:i:s', $unixTimestamp);
    
        $meters = [
            'U_3_EM3_TotalActiveEnergy_kWh',
            'U_4_EM4_TotalActiveEnergy_kWh',
            'U_5_EM5_TotalActiveEnergy_kWh',
            'U_6_EM6_TotalActiveEnergy_kWh',
            'U_7_EM7_ActiveEnergyDelivered_Wh',
            'U_8_EM8_TotalActiveEnergy_kWh',
            'U_9_EM9_ActiveEnergyDelivered_Wh',
            'U_10_EM10_TotalActiveEnergy_kWh',
            'U_21_ActiveEnergy_Delivered_kWh',
            'U_15_ActiveEnergy_Delivered_kWh',
            'U_22_ActiveEnergy_Delivered_kWh',
        ];
    
        $currentEnergyValue = 0;
        foreach ($meters as $meter) {
            $currentEnergyValue += isset($document[$meter]) ? $document[$meter] : 0;
        }
    
        $currentFlowValue = isset($document['F3_MainLine_TotalFlow']) ? $document['F3_MainLine_TotalFlow']:0;
        $energyInterval = ($previousEnergyValue !== null) ? ($currentEnergyValue - $previousEnergyValue) : 0;
        $flowInterval = ($previousFlowValue !== null) ? ($currentFlowValue - $previousFlowValue) : 0;
    
        $ratio = ($flowInterval != 0) ? ($flowInterval / $energyInterval) : 0;
    
        if ($energyInterval > 0 && $flowInterval > 0) {
            $ratioSum += $ratio;
            $count++;
        }
    
        $averageRatio = ($count > 0) ? round($ratioSum / $count, 4) : 0;
    
        $data[] = [
            'date' => $humanDate,
            'value1' => round($ratio, 4),
            'interval' => 0,
            'value' => $averageRatio
        ];
    
        $previousEnergyValue = $currentEnergyValue;
        $previousFlowValue = $currentFlowValue;
    }
    
    // JSON Output
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit;
    

}
