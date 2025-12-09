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
        $client = new MongoDB\Client("mongodb://Jamal:rVl8O8iMN@43.204.118.114:57019/CBL?authSource=admin&readPreference=primary&appname=MongoDB%20Compass&ssl=false");
        return $client->CBL;
    } catch (Exception $e) {
        echo json_encode(["error" => "Failed to connect to MongoDB: " . $e->getMessage()]);
        exit;
    }
}

$db = connectDB();
$collection = $db->CBL_b;

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

    // Define the tags for Reactive Power and Power Factor
    $reactivePowerTags = [
        'U_3_EM3_ReAPower_Total_VAR',
        'U_4_EM4_ReAPower_Total_VAR',
        'U_5_EM5_ReAPower_Total_VAR',
        'U_6_EM6_ReAPower_Total_VAR',
        'U_7_EM7_ReactivePowerTotal_kVAR',
        'U_8_EM8_ReAPower_Total_VAR',
        'U_9_EM9_ReactivePowerTotal_kVAR',
        'U_10_EM10_ReAPower_Total_VAR',
        'U_21_ReactivePower_Total_kVAR',
        'U_15_ReactivePower_Total_kVAR',
        'U_22_ReactivePower_Total_kVAR',

    ];

    $powerFactorTags = [
        'U_3_EM3_PF_Avg',
        'U_4_EM4_PF_Avg',
        'U_5_EM5_PF_Avg',
        'U_6_EM6_PF_Avg',
        'U_7_EM7_PowerFactorTotal',
        'U_8_EM8_PF_Avg',
        'U_9_EM9_PowerFactorTotal',
        'U_10_EM10_PF_Avg',
        'U_21_PowerFactor_Total',
        'U_15_PowerFactor_Total',
        'U_22_PowerFactor_Total',
    ];

    // MongoDB projection to include only required fields
    $select_fields = array_fill_keys(array_merge($reactivePowerTags, $powerFactorTags), 1);
    $select_fields['PLC_Date_Time'] = 1; // Optionally include timestamps
    $select_fields['UNIXtimestamp'] = 1;

    // Query filter
    $where = array(
        'UNIXtimestamp' =>  array('$gte' => $start_date1, '$lte' => $new_end1)
    );

    // Fetch the documents
    $options = ['projection' => $select_fields];
    $cursor = $collection->find($where, $options);

    $data = [];
    foreach ($cursor as $document) {
        $totalReactivePower = 0;
        $totalPowerFactor = 0;

        // Sum the values for Reactive Power tags
        foreach ($reactivePowerTags as $tag) {
            if (isset($document[$tag])) {
                $totalReactivePower += $document[$tag];
            }
        }

        // Sum the values for Power Factor tags
        foreach ($powerFactorTags as $tag) {
            if (isset($document[$tag])) {
                $totalPowerFactor += $document[$tag];
            }
        }

        $humanDate = date('Y-m-d H:i:s', $document['UNIXtimestamp']);
        $data[] = [
            'date' => $humanDate,
            'value'  => round($totalReactivePower, 2),
            'powerFactor' => round(($totalPowerFactor)/100, 2)
        ];
    }

    echo json_encode($data);
}
?>
