<?php
require 'vendor/autoload.php';
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
date_default_timezone_set('Asia/Karachi');

$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : null;
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : null;
$dateRangeLabel = $_GET['Label'];
function fetchData($start_date, $end_date)
{
    // Connect to MongoDB
    function connectDB()
    {
        try {
            $client = new MongoDB\Client("mongodb://admin:cisco123@13.234.241.103:27017/?authSource=iotdb&readPreference=primary&appname=MongoDB%20Compass&ssl=false");
            return $client->iotdb;
        } catch (Exception $e) {
            echo json_encode(['error' => 'Failed to connect to MongoDB: ' . $e->getMessage()]);
            exit;
        }
    }

    $db = connectDB();
    $collection = $db->CBL_b;
    $where = array(
        'UNIXtimestamp' =>  array('$gt' => $start_date, '$lte' => $end_date)
    );
    $select_fields = array(
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
        'U_22_ActiveEnergy_Delivered_kWh' => 1,
        'F1_GWP_TotalFlow' => 1,
        'F2_Airjet_TotalFlow' => 1,
        'F3_MainLine_TotalFlow' => 1,
        'F4_Sewing2_TotalFlow' => 1,
        'F5_Textile_TotalFlow' => 1,
        'F6_Sewing1_TotalFlow' => 1,
        'F7_PG_TotalFlow' => 1,
        'U11_SM11_PowerYield_EXP_Total_kWh' => 1,
        'U12_SM12_PowerYield_EXP_Total_kWh' => 1,

        'PLC_Date_Time' => 1,
    );
    $options = array(
        'projection' => $select_fields
    );
    $cursor = $collection->find($where, $options);   //This is the main line
    $docs = $cursor->toArray();
    $index = 0;
    foreach ($docs as $document) {
        json_encode($document);
        foreach ($document as $key => $value) {
            $term[$index][$key] = $value;
            //  if (!empty($document['GW1_U8_ActiveEnergy_Delivered_kWh'])) {
            //     $arr[] = $document['GW1_U8_ActiveEnergy_Delivered_kWh'];
            // }
            //RO
            // Adding new meters sequentially from $arr1
            if (!empty($document['U_3_EM3_TotalActiveEnergy_kWh'])) {
                $arr1[] = $document['U_3_EM3_TotalActiveEnergy_kWh'];
            }
            if (!empty($document['U_4_EM4_TotalActiveEnergy_kWh'])) {
                $arr2[] = $document['U_4_EM4_TotalActiveEnergy_kWh'];
            }
            if (!empty($document['U_5_EM5_TotalActiveEnergy_kWh'])) {
                $arr3[] = $document['U_5_EM5_TotalActiveEnergy_kWh'];
            }
            if (!empty($document['U_6_EM6_TotalActiveEnergy_kWh'])) {
                $arr4[] = $document['U_6_EM6_TotalActiveEnergy_kWh'];
            }
            if (!empty($document['U_7_EM7_ActiveEnergyDelivered_Wh'])) {
                $arr5[] = $document['U_7_EM7_ActiveEnergyDelivered_Wh'];
            }
            if (!empty($document['U_8_EM8_TotalActiveEnergy_kWh'])) {
                $arr6[] = $document['U_8_EM8_TotalActiveEnergy_kWh'];
            }
            if (!empty($document['U_9_EM9_ActiveEnergyDelivered_Wh'])) {
                $arr7[] = $document['U_9_EM9_ActiveEnergyDelivered_Wh'];
            }
            if (!empty($document['U_10_EM10_TotalActiveEnergy_kWh'])) {
                $arr8[] = $document['U_10_EM10_TotalActiveEnergy_kWh'];
            }
            if (!empty($document['U_15_ActiveEnergy_Total_kWh'])) {
                $arr9[] = $document['U_15_ActiveEnergy_Total_kWh'];
            }
            if (!empty($document['U_22_ActiveEnergy_Delivered_kWh'])) {
                $arr10[] = $document['U_22_ActiveEnergy_Delivered_kWh'];
            }
            if (!empty($document['F1_GWP_TotalFlow'])) {
                $arr11[] = $document['F1_GWP_TotalFlow'];
            }
            if (!empty($document['F2_Airjet_TotalFlow'])) {
                $arr12[] = $document['F2_Airjet_TotalFlow'];
            }
            if (!empty($document['F3_MainLine_TotalFlow'])) {
                $arr13[] = $document['F3_MainLine_TotalFlow'];
            }
            if (!empty($document['F4_Sewing2_TotalFlow'])) {
                $arr14[] = $document['F4_Sewing2_TotalFlow'];
            }
            if (!empty($document['F5_Textile_TotalFlow'])) {
                $arr15[] = $document['F5_Textile_TotalFlow'];
            }
            if (!empty($document['F6_Sewing1_TotalFlow'])) {
                $arr16[] = $document['F6_Sewing1_TotalFlow'];
            }
            if (!empty($document['F7_PG_TotalFlow'])) {
                $arr17[] = $document['F7_PG_TotalFlow'];
            }
            if (!empty($document['U11_SM11_PowerYield_EXP_Total_kWh'])) {
                $arr18[] = $document['U11_SM11_PowerYield_EXP_Total_kWh'];
            }
            if (!empty($document['U12_SM12_PowerYield_EXP_Total_kWh'])) {
                $arr19[] = $document['U12_SM12_PowerYield_EXP_Total_kWh'];
            }
        }
        $index++;
    }
    // print_r($arr6);
    // if (!empty($arr)) {
    //     $first_index = key($arr);
    //     $first = $arr[$first_index + 1];
    //     $end = end($arr);
    //     $U_1 = $end - $first;
    // } else {
    //     $U_1 = 0;
    // }
    // print_r($arr6);
    if (!empty($arr1)) {
        $first_index = key($arr1);
        $first = $arr1[$first_index + 1];
        $end = end($arr1);
        $U_3 = $end - $first;
    } else {
        $U_3 = 0;
    }
    if (!empty($arr2)) {
        $first_index = key($arr2);
        $first = $arr2[$first_index + 1];
        $end = end($arr2);
        $U_4 = $end - $first;
    } else {
        $U_4 = 0;
    }
    if (!empty($arr3)) {
        $first_index = key($arr3);
        $first = $arr3[$first_index + 1];
        $end = end($arr3);
        $U_5 = $end - $first;
    } else {
        $U_5 = 0;
    }
    if (!empty($arr4)) {
        $first_index = key($arr4);
        $first = $arr4[$first_index + 1];
        $end = end($arr4);
        $U_6 = $end - $first;
    } else {
        $U_6 = 0;
    }
    if (!empty($arr5)) {
        $first_index = key($arr5);
        $first = $arr5[$first_index + 1];
        $end = end($arr5);
        $U_7 = $end - $first;
    } else {
        $U_7 = 0;
    }
    if (!empty($arr6)) {
        $first_index = key($arr6);
        $first = $arr6[$first_index + 1];
        $end = end($arr6);
        $U_8 = $end - $first;
    } else {
        $U_8 = 0;
    }
    if (!empty($arr7)) {
        $first_index = key($arr7);
        $first = $arr7[$first_index + 1];
        $end = end($arr7);
        $U_9 = $end - $first;
    } else {
        $U_9 = 0;
    }
    if (!empty($arr8)) {
        $first_index = key($arr8);
        $first = $arr8[$first_index + 1];
        $end = end($arr8);
        $U_10 = $end - $first;
    } else {
        $U_10 = 0;
    }
    if (!empty($arr9)) {
        $first_index = key($arr9);
        $first = $arr9[$first_index + 1];
        $end = end($arr9);
        $U_11 = $end - $first;
    } else {
        $U_11 = 0;
    }
    if (!empty($arr10)) {
        $first_index = key($arr10);
        $first = $arr10[$first_index + 1];
        $end = end($arr10);
        $U_12 = $end - $first;
    } else {
        $U_12 = 0;
    }
    if (!empty($arr11)) {
        $first_index = key($arr11);
        $first = $arr11[$first_index + 1];
        $end = end($arr11);
        $F_1 = (($end - $first));
    } else {
        $F_1 = 0;
    }
    if (!empty($arr12)) {
        $first_index = key($arr12);
        $first = $arr12[$first_index + 1];
        $end = end($arr12);
        $F_2 = (($end - $first));
    } else {
        $F_2 = 0;
    }
    if (!empty($arr13)) {
        $first_index = key($arr13);
        $first = $arr13[$first_index + 1];
        $end = end($arr13);
        $F_3 = (($end - $first));
    } else {
        $F_3 = 0;
    }
    if (!empty($arr14)) {
        $first_index = key($arr14);
        $first = $arr14[$first_index + 1];
        $end = end($arr14);
        $F_4 = (($end - $first));
    } else {
        $F_4 = 0;
    }
    if (!empty($arr15)) {
        $first_index = key($arr15);
        $first = $arr15[$first_index + 1];
        $end = end($arr15);
        $F_5 =(($end - $first));
    } else {
        $F_5 = 0;
    }
    if (!empty($arr16)) {
        // Step 1: Calculate Mean and Standard Deviation
        $mean = array_sum($arr16) / count($arr16);
        $squaredDifferences = array_map(function ($value) use ($mean) {
            return pow($value - $mean, 2);
        }, $arr16);
        $standardDeviation = sqrt(array_sum($squaredDifferences) / count($squaredDifferences));

        // Step 2: Filter Out Values Outside of 2 Standard Deviations
        $filteredArr = array_filter($arr16, function ($value) use ($mean, $standardDeviation) {
            return $value >= ($mean - 2 * $standardDeviation) && $value <= ($mean + 2 * $standardDeviation);
        });

        // Step 3: Calculate the First and Last Values After Filtering
        if (!empty($filteredArr)) {
            $firstIndex = key($filteredArr);
            $first = array_values($filteredArr)[0]; // Get the first element of the filtered array
            $end = array_values($filteredArr)[count($filteredArr) - 1]; // Get the last element of the filtered array
            $F_6 = (($end - $first));
        } else {
            $F_6 = 0; // Default value if no valid data remains after filtering
        }
    } else {
        $F_6 = 0;
    }
    if (!empty($arr17)) {
        $first_index = key($arr17);
        $first = $arr17[$first_index + 1];
        $end = end($arr17);
        $F_7 = (($end - $first));
    } else {
        $F_7 = 0;
    }

    if (!empty($arr18)) {
        $first_index = key($arr18);
        $first = $arr18[$first_index + 1];
        $end = end($arr18);
        $S_1 = $end - $first;
    } else {
        $S_1  = 0;
    }
    if (!empty($arr19)) {
        $first_index = key($arr19);
        $first = $arr19[$first_index + 1];
        $end = end($arr19);
        $S_2 = $end - $first;
    } else {
        $S_2  = 0;
    }
    $count1 = $U_3 + $U_4 + $U_5 + $U_6 + $U_7 + $U_8 + $U_10 + $U_11+$U_9+$U_12;
    $count2 = $F_3;;
    $count2 = $F_3;
    $count3 = $S_1 + $S_2;

    if ($count1 < 0) {
        $count1 = 0;
    } else {
        $count1 = $count1;
    }
    if ($count2 < 0) {
        $count2 = 0;
    } else {
        $count2 = $count2;
    }
    if ($count3 < 0) {
        $count3 = 0;
    } else {
        $count3 = $count3;
    }

    $chartData = [
        [
            "country" => "Electricity",
            "litres" => (int)$count1,
            "color" => "#3598db", // Red color
            "subData" => [
                ["name" => "Ozen 350", "value" => $U_3],
                ["name" => "Atlas Copco", "value" => $U_4],
                ["name" => "Compressor Aux", "value" => $U_5],
                ["name" => "Ganzair Compressor", "value" => $U_6],
                ["name" => "New Centac Comp#2", "value" => $U_9],
                ["name" => "ML-132", "value" => $U_8],
                ["name" => "New Centac Comp#1", "value" => $U_7],
                ["name" => "Kaeser Compressor", "value" => $U_10],
                ["name" => "Dryer", "value" => $U_11],
                ["name" => "Solar Hostels", "value" => $U_12]
            ]
        ],
        [
            "country" => "Compressed Air",
            "litres" => (int)$count2,
            "color" => "#2dcc70", // Red color
            "subData" => [
                ["name" => "GWP", "value" => (int)$F_1],
                ["name" => "Airjet", "value" => (int)$F_2],
                // ["name" => "Mainline", "value" => $F_3],
                ["name" => "Sewing2", "value" => (int)$F_4],
                ["name" => "Textile", "value" => (int)$F_5],
                ["name" => "Sewing1", "value" => (int)$F_7],
                ["name" => "PG", "value" => (int)$F_6]
            ]
        ],
        [
            "country" => 'Solars',
            "litres" => (int)$count3,
            "color" => "#e67f22", // Red color
            "subData" => [
                ["name" => "Solar SPNG", "value" => $S_1],
                ["name" => "Solar SWNG", "value" => $S_2],
            ]
        ],
    ];

    // Set the response header to indicate JSON content
    header('Content-Type: application/json');

    // Output the chart data as JSON
    echo json_encode($chartData);
}
$current_date = date("Y-n-j");
if ($dateRangeLabel == 'Custom Range') {
    $mongotime1 = new MongoDB\BSON\UTCDateTime(strtotime($start_date . 'T00:00:00+05:00'));
    $val1 = json_decode(json_encode($mongotime1), true);
    foreach ($val1 as $key => $value) {
        foreach ($value as $sub_key => $sub_value) {
            $a1 = $sub_value;
        }
    }
    $start_date = intval($a1);

    $mongotime2 = new MongoDB\BSON\UTCDateTime(strtotime($end_date . 'T23:59:18+05:00'));
    $val2 = json_decode(json_encode($mongotime2), true);
    foreach ($val2 as $key => $value) {
        foreach ($value as $sub_key => $sub_value2) {
            $a2 = $sub_value2;
        }
    }
    $end_date = intval($a2);
    fetchData($start_date, $end_date);
}

// Generate the chart data dynamically
