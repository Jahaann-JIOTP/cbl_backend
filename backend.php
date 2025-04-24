<?php
require 'vendor/autoload.php';
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
date_default_timezone_set('Asia/Karachi');
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

$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : null;
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : null;

$mongotime1 = new MongoDB\BSON\UTCDateTime(strtotime($start_date . 'T00:00:00+05:00'));
// print_r($mongotime1);
$mongotime2 = new MongoDB\BSON\UTCDateTime(strtotime($end_date. 'T23:59:59+05:00'));
$val1 = json_decode(json_encode($mongotime1), true);
$val2 = json_decode(json_encode($mongotime2), true);
foreach ($val1 as $key => $value) {
  foreach ($value as $sub_key => $sub_value) {
    $a1 = $sub_value;
  }
}
$start_date = intval($a1);
// print_r($start_date);
foreach ($val2 as $key => $value) {
  foreach ($value as $sub_key => $sub_value2) {
    $a2 = $sub_value2;
  }
}
$end_date = intval($a2);
$where = array(
  'UNIXtimestamp' =>  array('$gt' => $start_date, '$lte' => $end_date)
);
$select_fields = [
  'F1_GWP_TotalFlow' => 1,
  'F2_Airjet_TotalFlow' => 1,
  'F3_MainLine_TotalFlow' => 1,
  'F4_Sewing2_TotalFlow' => 1,
  'F5_Textile_TotalFlow' => 1,
  'F6_Sewing1_TotalFlow' => 1,
  'F7_PG_TotalFlow' => 1
];
$options = ['projection' => $select_fields];
$docs = $collection->find($where, $options)->toArray();

// Helper functions
function calculate_mean($values)
{
  return array_sum($values) / count($values);
}

function calculate_std_dev($values, $mean)
{
  $sum = 0;
  foreach ($values as $value) {
    $sum += pow($value - $mean, 2);
  }
  return sqrt($sum / count($values));
}

function filter_by_std_dev($values)
{
  $mean = calculate_mean($values);
  $std_dev = calculate_std_dev($values, $mean);
  $upper_threshold = $mean + 2 * $std_dev;
  $lower_threshold = $mean - 2 * $std_dev;

  return array_filter($values, function ($value) use ($upper_threshold, $lower_threshold) {
    return $value >= $lower_threshold && $value <= $upper_threshold;
  });
}

// Collect and filter data for each flow meter
$flow_arrays = ['arr_F1' => [], 'arr_F2' => [], 'arr_F3' => [], 'arr_F4' => [], 'arr_F5' => [], 'arr_F6' => [], 'arr_F7' => []];
foreach ($docs as $document) {
  if (!empty($document['F1_GWP_TotalFlow'])) $flow_arrays['arr_F1'][] = $document['F1_GWP_TotalFlow'];
  if (!empty($document['F2_Airjet_TotalFlow'])) $flow_arrays['arr_F2'][] = $document['F2_Airjet_TotalFlow'];
  if (!empty($document['F3_MainLine_TotalFlow'])) $flow_arrays['arr_F3'][] = $document['F3_MainLine_TotalFlow'];
  if (!empty($document['F4_Sewing2_TotalFlow'])) $flow_arrays['arr_F4'][] = $document['F4_Sewing2_TotalFlow'];
  if (!empty($document['F5_Textile_TotalFlow'])) $flow_arrays['arr_F5'][] = $document['F5_Textile_TotalFlow'];
  if (!empty($document['F6_Sewing1_TotalFlow'])) $flow_arrays['arr_F6'][] = $document['F6_Sewing1_TotalFlow'];
  if (!empty($document['F7_PG_TotalFlow'])) $flow_arrays['arr_F7'][] = $document['F7_PG_TotalFlow'];
}

// Calculate flow differences after filtering
foreach ($flow_arrays as $key => $array) {
  $filtered_values = filter_by_std_dev($array);
  $flow_arrays[$key] = !empty($filtered_values) ? end($filtered_values) - reset($filtered_values) : 0;
}

// Convert to cubic meters
$count1 = $flow_arrays['arr_F1'];
$count2 = $flow_arrays['arr_F2'];
$count3 = $flow_arrays['arr_F3'];
$count4 = $flow_arrays['arr_F4'];
$count5 = $flow_arrays['arr_F5'];
$count6 = $flow_arrays['arr_F6'];
$count7 = $flow_arrays['arr_F7'];

// Unaccountable energy
$count8 = $count1 + $count2 + $count4 + $count5 + $count6 + $count7;
$count9 = $count3 - $count8;

// Prepare data for JSON
$array1[] = ["to" => "MainLine", 'value' => (int)$count3];
$array1[] = ["from" => "MainLine", "to" => "GWP", 'value' => (int)$count1];
$array1[] = ["from" => "MainLine", "to" => "Airjet", 'value' => (int)$count2];
$array1[] = ["from" => "MainLine", "to" => "Sewing 2", 'value' => (int)$count4];
$array1[] = ["from" => "MainLine", "to" => "Textile", 'value' => (int)$count5];
$array1[] = ["from" => "MainLine", "to" => "Sewing 1", 'value' => (int)$count7];
$array1[] = ["from" => "MainLine", "to" => "PG", 'value' => (int)$count6];
$array1[] = ["from" => "MainLine", 'to' => 'Unaccounted Air', 'value' => (int)$count9, 'nodeColor' => '#ff0000'];

// Output JSON
$data = json_encode($array1);
echo $data;
