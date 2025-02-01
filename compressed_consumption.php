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
$Tag = 'U_1_ActiveEnergy_Delivered_kWh';
if ($dateRangeLabel == 'Custom Range') {
    $mongotime1 = new MongoDB\BSON\UTCDateTime(strtotime($startDate . 'T00:00:00+05:00'));
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

    $select_fields = array(
        $Tag => 1,
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
        'U_15_ActiveEnergy_Total_kWh' => 1,
        'U_21_ActiveEnergy_Total_kWh' => 1,
    );
    // print_r($select_fields);
    $options = ['projection' => $select_fields];

    $cursor = $collection->find($where, $options);
    $docs = $cursor->toArray();
    $previousValue = null;
    $data = [];

    foreach ($docs as $document) {
        $unixTimestamp = $document['UNIXtimestamp'];
        $humanDate = date('Y-m-d H:i:s', $unixTimestamp);
    
        // List of all meter keys to process
        $meters = [
            'U_3_EM3_TotalActiveEnergy_kWh',
            'U_4_EM4_TotalActiveEnergy_kWh',
            'U_5_EM5_TotalActiveEnergy_kWh',
            'U_6_EM6_TotalActiveEnergy_kWh',
            'U_7_EM7_ActiveEnergyDelivered_Wh',
            'U_8_EM8_TotalActiveEnergy_kWh',
            'U_9_EM9_ActiveEnergyDelivered_Wh',
            'U_10_EM10_TotalActiveEnergy_kWh',
            'U_15_ActiveEnergy_Total_kWh',
            'U_21_ActiveEnergy_Total_kWh',
        ];
    
        // Calculate the total value by summing up all the meters
        $totalMetersValue = 0;
        foreach ($meters as $meter) {
            $totalMetersValue += isset($document[$meter]) ? $document[$meter] : 0; // Default to 0 if the meter is not present
        }
    
        // Get the value of F3_MainLine_TotalFlow
        $flowValue = isset($document['F3_MainLine_TotalFlow']) ? $document['F3_MainLine_TotalFlow'] : 1; // Default to 1 to avoid division by zero
    
        // Calculate the final value by dividing F3_MainLine_TotalFlow by the total meters value
        $finalValue = ($totalMetersValue != 0) ? $flowValue / $totalMetersValue : 0;
    
        // Calculate interval only if there's a previous value
        if ($previousValue !== null) {
            $interval = $finalValue - $previousValue;
        } else {
            $interval = 0; // No interval to calculate for the first data point
        }
    
        // Add current value and interval to the data array
        $data[] = [
            'date' => $humanDate,
            'value' => round((float)$finalValue, 2),
            'interval' => round((float)$interval, 2)
        ];
    
        // Update previousValue for the next iteration
        $previousValue = $finalValue;
    }
    

    echo json_encode($data);
}
