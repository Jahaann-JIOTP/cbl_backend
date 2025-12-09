<?php
error_reporting(E_ERROR | E_PARSE);
require 'vendor/autoload.php';
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
date_default_timezone_set('Asia/Karachi');
$value = $_GET['value'];
// $value = 'month';
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
$current_date = date("Y-n-j");
$collection = $db->CBL_b;
$array=array();
$data = array();
$numberOfMeters=1;
$current_date=date('Y-m-d');
$tag_values = array(
       
        'U_3_EM3_TotalActiveEnergy_kWh',
        'U_4_EM4_TotalActiveEnergy_kWh', 
        'U_5_EM5_TotalActiveEnergy_kWh', 
        'U_6_EM6_TotalActiveEnergy_kWh', 
        'U_7_EM7_ActiveEnergyDelivered_Wh', 
        'U_8_EM8_TotalActiveEnergy_kWh', 
        'U_9_EM9_ActiveEnergyDelivered_Wh', 
        'U_10_EM10_TotalActiveEnergy_kWh',
       'U_15_ActiveEnergy_Delivered_kWh', 
       'U_21_ActiveEnergy_Delivered_kWh',
       'U_22_ActiveEnergy_Delivered_kWh', 
);
function dateDiffInDays($date1, $date2)
 {
  // Calculating the difference in timestamps
  $diff = strtotime($date2) - strtotime($date1);
  // 1 day = 24 hours
  // 24 * 60 * 60 = 86400 seconds
  return abs(round($diff / 86400));
 }
function GetUNIXday($day){
  $mongotime=new MongoDB\BSON\UTCDateTime(strtotime($day.'T00:00:00+05:00'));
  $val=json_decode(json_encode($mongotime), true);
  foreach($val as $key=>$value){foreach($value as $sub_key=>$sub_value){$a=$sub_value;
  }}
  return intval($a);
}
function fetchHourly($date, $tag_values,$numberOfMeters,$Label){
   $db = connectDB();
  $collection = $db->CBL_b;
  $day = date('d', strtotime($date));
  $day = intval($day);
  $month = date('m', strtotime($date));
  $month = intval($month);
  $year = date('Y', strtotime($date));
  $year = intval($year);
  $currentDayUNIX=GetUNIXday($date);
  $cursor = $collection->aggregate([
    ['$project' => ['UNIXtimestamp' => 1, $tag_values[0] => 1, $tag_values[1] => 1,$tag_values[2] => 1, $tag_values[3] => 1, $tag_values[4] => 1, $tag_values[5] => 1,
    $tag_values[6] => 1, $tag_values[7] => 1, $tag_values[8] => 1, $tag_values[9] => 1, $tag_values[10] => 1]], 
    ['$match' => ['UNIXtimestamp' => ['$gte' => $currentDayUNIX ]]], 
    ['$project' => [
        'hour' => ['$hour' => ['$add' => [['$toDate' => ['$multiply' => ['$UNIXtimestamp', 1000]]], 18000000]]],
        'day' => ['$dayOfMonth' => ['$add' => [['$toDate' => ['$multiply' => ['$UNIXtimestamp', 1000]]], 18000000]]],
        'month' => ['$month' => ['$add' => [['$toDate' => ['$multiply' => ['$UNIXtimestamp', 1000]]], 18000000]]],
        'year' => ['$year' => ['$add' => [['$toDate' => ['$multiply' => ['$UNIXtimestamp', 1000]]], 18000000]]],
        'u1' => '$' . $tag_values[0],
        'u2' => '$' . $tag_values[1],
        'u3' => '$' . $tag_values[2],
        'u4' => '$' . $tag_values[3],
        'u5' => '$' . $tag_values[4],
        'u6' => '$' . $tag_values[5],
        'u7' => '$' . $tag_values[6],
        'u8' => '$' . $tag_values[7],
        'u9' => '$' . $tag_values[8],
        'u10' => '$' . $tag_values[9],
        'u11' => '$' . $tag_values[10],
    ]],
    ['$match' => ['year' => $year, 'month' => $month, 'day' => $day]], 
    ['$group' => [
        '_id' => ['year' => '$year', 'month' => '$month', 'day' => '$day', 'hour' => '$hour'],
        'document' => ['$push' => '$$ROOT'],
    ]],
    ['$sort' => ['_id.hour' => 1]],
    ['$project' => [
        'firstRead1' => ['$min' => ['$filter' => ['input' => '$document', 'as' => 'doc', 'cond' => ['$gt' => ['$$doc.u1', 0]]]]],
        'lastRead1' => ['$max' => ['$filter' => ['input' => '$document', 'as' => 'doc', 'cond' => ['$gt' => ['$$doc.u1', 0]]]]],
        'firstRead2' => ['$min' => ['$filter' => ['input' => '$document', 'as' => 'doc', 'cond' => ['$gt' => ['$$doc.u2', 0]]]]],
        'lastRead2' => ['$max' => ['$filter' => ['input' => '$document', 'as' => 'doc', 'cond' => ['$gt' => ['$$doc.u2', 0]]]]],
        'firstRead3' => ['$min' => ['$filter' => ['input' => '$document', 'as' => 'doc', 'cond' => ['$gt' => ['$$doc.u3', 0]]]]],
        'lastRead3' => ['$max' => ['$filter' => ['input' => '$document', 'as' => 'doc', 'cond' => ['$gt' => ['$$doc.u3', 0]]]]],
        'firstRead4' => ['$min' => ['$filter' => ['input' => '$document', 'as' => 'doc', 'cond' => ['$gt' => ['$$doc.u4', 0]]]]],
        'lastRead4' => ['$max' => ['$filter' => ['input' => '$document', 'as' => 'doc', 'cond' => ['$gt' => ['$$doc.u4', 0]]]]],
        'firstRead5' => ['$min' => ['$filter' => ['input' => '$document', 'as' => 'doc', 'cond' => ['$gt' => ['$$doc.u5', 0]]]]],
        'lastRead5' => ['$max' => ['$filter' => ['input' => '$document', 'as' => 'doc', 'cond' => ['$gt' => ['$$doc.u5', 0]]]]],
        'firstRead6' => ['$min' => ['$filter' => ['input' => '$document', 'as' => 'doc', 'cond' => ['$gt' => ['$$doc.u6', 0]]]]],
        'lastRead6' => ['$max' => ['$filter' => ['input' => '$document', 'as' => 'doc', 'cond' => ['$gt' => ['$$doc.u6', 0]]]]],
        'firstRead7' => ['$min' => ['$filter' => ['input' => '$document', 'as' => 'doc', 'cond' => ['$gt' => ['$$doc.u7', 0]]]]],
        'lastRead7' => ['$max' => ['$filter' => ['input' => '$document', 'as' => 'doc', 'cond' => ['$gt' => ['$$doc.u7', 0]]]]],
        'firstRead8' => ['$min' => ['$filter' => ['input' => '$document', 'as' => 'doc', 'cond' => ['$gt' => ['$$doc.u8', 0]]]]],
        'lastRead8' => ['$max' => ['$filter' => ['input' => '$document', 'as' => 'doc', 'cond' => ['$gt' => ['$$doc.u8', 0]]]]],
        'firstRead9' => ['$min' => ['$filter' => ['input' => '$document', 'as' => 'doc', 'cond' => ['$gt' => ['$$doc.u9', 0]]]]],
        'lastRead9' => ['$max' => ['$filter' => ['input' => '$document', 'as' => 'doc', 'cond' => ['$gt' => ['$$doc.u9', 0]]]]],
        'firstRead10' => ['$min' => ['$filter' => ['input' => '$document', 'as' => 'doc', 'cond' => ['$gt' => ['$$doc.u10', 0]]]]],
        'lastRead10' => ['$max' => ['$filter' => ['input' => '$document', 'as' => 'doc', 'cond' => ['$gt' => ['$$doc.u10', 0]]]]],
        'firstRead11' => ['$min' => ['$filter' => ['input' => '$document', 'as' => 'doc', 'cond' => ['$gt' => ['$$doc.u11', 0]]]]],
        'lastRead11' => ['$max' => ['$filter' => ['input' => '$document', 'as' => 'doc', 'cond' => ['$gt' => ['$$doc.u11', 0]]]]],

    ]],
    ['$project' => [
        'kWh' . $Label => ['$subtract' => ['$lastRead1.u1', '$firstRead1.u1']],
        'kWh3' . $Label => ['$subtract' => ['$lastRead2.u2', '$firstRead2.u2']], // Added for u2
        'kWh4' . $Label => ['$subtract' => ['$lastRead3.u3', '$firstRead3.u3']], // Added for u2
        'kWh5' . $Label => ['$subtract' => ['$lastRead4.u4', '$firstRead4.u4']], // Added for u2
        'kWh6' . $Label => ['$subtract' => ['$lastRead5.u5', '$firstRead5.u5']], // Added for u2
        'kWh7' . $Label => ['$subtract' => ['$lastRead6.u6', '$firstRead6.u6']], // Added for u2
        'kWh8' . $Label => ['$subtract' => ['$lastRead7.u7', '$firstRead7.u7']], // Added for u2
        'kWh9' . $Label => ['$subtract' => ['$lastRead8.u8', '$firstRead8.u8']], // Added for u2
        'kWh10' . $Label => ['$subtract' => ['$lastRead9.u9', '$firstRead9.u9']], // Added for u2
        'kWh11' . $Label => ['$subtract' => ['$lastRead10.u10', '$firstRead10.u10']], // Added for u2
        'kWh12' . $Label => ['$subtract' => ['$lastRead11.u11', '$firstRead11.u11']], // Added for u2
    ]], 
]);

  $docs = $cursor->toArray();
  
 return $docs;
}
function fetchWeekly($monthStart,$monthEnd, $tag_values,$numberOfMeters){
   $db = connectDB();
  $collection = $db->CBL_b;
  $monthStartUnix=GetUNIXday($monthStart);
  $monthEndUnix=GetUNIXday($monthEnd);
  $cursor = $collection->aggregate([
    ['$project' => ['UNIXtimestamp' => 1, $tag_values[0] => 1, $tag_values[1] => 1,$tag_values[2] => 1, $tag_values[3] => 1, $tag_values[4] => 1, $tag_values[5] => 1,
    $tag_values[6] => 1, $tag_values[7] => 1, $tag_values[8] => 1, $tag_values[9] => 1, $tag_values[10] => 1]],
    ['$match' => ['UNIXtimestamp' => ['$gte' => $monthStartUnix,'$lte' => $monthEndUnix ]]],
    ['$project' => [
      'UNIXtimestamp'=>1,
      'date' => ['$add' => [['$toDate' => ['$multiply' => ['$UNIXtimestamp', 1000]]], 18000000]],
      'u1'=>'$'.$tag_values[0],
      'u2' => '$' . $tag_values[1],
      'u3' => '$' . $tag_values[2],
      'u4' => '$' . $tag_values[3],
      'u5' => '$' . $tag_values[4],
      'u6' => '$' . $tag_values[5],
      'u7' => '$' . $tag_values[6],
      'u8' => '$' . $tag_values[7],
      'u9' => '$' . $tag_values[8],
      'u10' => '$' . $tag_values[9],
      'u11' => '$' . $tag_values[10],
       ]],
    ['$project' => [
        'UNIXtimestamp'=>1,
        'date' => ['$dateToString'=>['format'=>'%Y-%m-%d','date'=>'$date']],
        'u1'=>1,
        'u2'=>1,
        'u3'=>1,
        'u4'=>1,
        'u5'=>1,
        'u6'=>1,
        'u7'=>1,
        'u8'=>1,
        'u9'=>1,
        'u10'=>1,
        'u11'=>1,
        
         ]],
    ['$project' => [
        'date'=>1,
        'week' => ['$week' => ['$add' => [['$toDate' => ['$multiply' => ['$UNIXtimestamp', 1000]]], 18000000]]],
        'u1'=>1,
        'u2'=>1,
        'u3'=>1,
        'u4'=>1,
        'u5'=>1,
        'u6'=>1,
        'u7'=>1,
        'u8'=>1,
        'u9'=>1,
        'u10'=>1,
        'u11'=>1,
         ]],
    ['$group' => ['_id' => '$week',
         'document' => ['$push' => '$$ROOT'],
         ]
        ],
    ['$sort'=>['_id'=>1]],
    ['$project' => [
      'firstRead1' => ['$min' => ['$filter' => ['input' => '$document', 'as' => 'doc', 'cond' => ['$gt' => ['$$doc.u1', 0]]]]],
      'lastRead1'  => ['$max' => ['$filter' => ['input' => '$document', 'as' => 'doc', 'cond' => ['$gt' => ['$$doc.u1', 0]]]]],
      'firstRead2' => ['$min' => ['$filter' => ['input' => '$document', 'as' => 'doc', 'cond' => ['$gt' => ['$$doc.u2', 0]]]]],
      'lastRead2'  => ['$max' => ['$filter' => ['input' => '$document', 'as' => 'doc', 'cond' => ['$gt' => ['$$doc.u2', 0]]]]],
      'firstRead3' => ['$min' => ['$filter' => ['input' => '$document', 'as' => 'doc', 'cond' => ['$gt' => ['$$doc.u3', 0]]]]],
      'lastRead3' => ['$max' => ['$filter' => ['input' => '$document', 'as' => 'doc', 'cond' => ['$gt' => ['$$doc.u3', 0]]]]],
     'firstRead4' => ['$min' => ['$filter' => ['input' => '$document', 'as' => 'doc', 'cond' => ['$gt' => ['$$doc.u4', 0]]]]],
      'lastRead4' => ['$max' => ['$filter' => ['input' => '$document', 'as' => 'doc', 'cond' => ['$gt' => ['$$doc.u4', 0]]]]],
      'firstRead5' => ['$min' => ['$filter' => ['input' => '$document', 'as' => 'doc', 'cond' => ['$gt' => ['$$doc.u5', 0]]]]],
      'lastRead5' => ['$max' => ['$filter' => ['input' => '$document', 'as' => 'doc', 'cond' => ['$gt' => ['$$doc.u5', 0]]]]],
      'firstRead6' => ['$min' => ['$filter' => ['input' => '$document', 'as' => 'doc', 'cond' => ['$gt' => ['$$doc.u6', 0]]]]],
      'lastRead6' => ['$max' => ['$filter' => ['input' => '$document', 'as' => 'doc', 'cond' => ['$gt' => ['$$doc.u6', 0]]]]],
      'firstRead7' => ['$min' => ['$filter' => ['input' => '$document', 'as' => 'doc', 'cond' => ['$gt' => ['$$doc.u7', 0]]]]],
      'lastRead7' => ['$max' => ['$filter' => ['input' => '$document', 'as' => 'doc', 'cond' => ['$gt' => ['$$doc.u7', 0]]]]],
      'firstRead8' => ['$min' => ['$filter' => ['input' => '$document', 'as' => 'doc', 'cond' => ['$gt' => ['$$doc.u8', 0]]]]],
      'lastRead8' => ['$max' => ['$filter' => ['input' => '$document', 'as' => 'doc', 'cond' => ['$gt' => ['$$doc.u8', 0]]]]],
      'firstRead9' => ['$min' => ['$filter' => ['input' => '$document', 'as' => 'doc', 'cond' => ['$gt' => ['$$doc.u9', 0]]]]],
      'lastRead9' => ['$max' => ['$filter' => ['input' => '$document', 'as' => 'doc', 'cond' => ['$gt' => ['$$doc.u9', 0]]]]],
      'firstRead10' => ['$min' => ['$filter' => ['input' => '$document', 'as' => 'doc', 'cond' => ['$gt' => ['$$doc.u10', 0]]]]],
      'lastRead10' => ['$max' => ['$filter' => ['input' => '$document', 'as' => 'doc', 'cond' => ['$gt' => ['$$doc.u10', 0]]]]],
      'firstRead11' => ['$min' => ['$filter' => ['input' => '$document', 'as' => 'doc', 'cond' => ['$gt' => ['$$doc.u11', 0]]]]],
      'lastRead11' => ['$max' => ['$filter' => ['input' => '$document', 'as' => 'doc', 'cond' => ['$gt' => ['$$doc.u11', 0]]]]],
  ]],
  ['$project' => [
    'day1'=>'$firstRead1.date','$firstRead2.date',
    'day2'=>'$lastRead1.date','$lastRead2.date',
    'kWh0'=>['$subtract'=>['$lastRead1.u1','$firstRead1.u1']],
    'kWh3' => ['$subtract' => ['$lastRead2.u2', '$firstRead2.u2']],
    'kWh4' => ['$subtract' => ['$lastRead3.u3', '$firstRead3.u3']],
    'kWh5' => ['$subtract' => ['$lastRead4.u4', '$firstRead4.u4']],
    'kWh6' => ['$subtract' => ['$lastRead5.u5', '$firstRead5.u5']],
    'kWh7' => ['$subtract' => ['$lastRead6.u6', '$firstRead6.u6']],
    'kWh8' => ['$subtract' => ['$lastRead7.u7', '$firstRead7.u7']],
    'kWh9' => ['$subtract' => ['$lastRead8.u8', '$firstRead8.u8']],
    'kWh10' => ['$subtract' => ['$lastRead9.u9', '$firstRead9.u9']],
    'kWh11' => ['$subtract' => ['$lastRead10.u10', '$firstRead10.u10']],
    'kWh12' => ['$subtract' => ['$lastRead11.u11', '$firstRead11.u11']],
    
]],
  ]);
  $docs = $cursor->toArray();
  return $docs;
}
function fetchDayWise($date, $tag_values,$numberOfMeters,$value){
   $db = connectDB();
  $collection = $db->CBL_b;
  $day = date('d', strtotime($date));
  $day = intval($day);
  $month = date('m', strtotime($date));
  $month = intval($month);
  $year = date('Y', strtotime($date));
  $year = intval($year);
  $currentDayUNIX=GetUNIXday($date);
  
  $cursor = $collection->aggregate([
    ['$project' => ['UNIXtimestamp' => 1,  $tag_values[0] => 1, $tag_values[1] => 1,$tag_values[2] => 1, $tag_values[3] => 1, $tag_values[4] => 1, $tag_values[5] => 1,
    $tag_values[6] => 1, $tag_values[7] => 1, $tag_values[8] => 1, $tag_values[9] => 1, $tag_values[10] => 1,]], 
    ['$match' => ['UNIXtimestamp' => ['$gte' => $currentDayUNIX]]],
    ['$project' => [
    'u1' => '$'.$tag_values[0], 
    'u2' => '$' . $tag_values[1],
    'u3' => '$' . $tag_values[2],
    'u4' => '$' . $tag_values[3],
    'u5' => '$' . $tag_values[4],
    'u6' => '$' . $tag_values[5],
    'u7' => '$' . $tag_values[6],
    'u8' => '$' . $tag_values[7],
    'u9' => '$' . $tag_values[8],
    'u10' => '$' . $tag_values[9],
    'u11' => '$' . $tag_values[10],
    'day' => ['$dayOfMonth' => ['$add' => [['$toDate' => ['$multiply' => ['$UNIXtimestamp', 1000]]], 18000000]]], 
    'month' => ['$month' => ['$add' => [['$toDate' => ['$multiply' => ['$UNIXtimestamp', 1000]]], 18000000]]], 
    'year' => ['$year' => ['$add' => [['$toDate' => ['$multiply' => ['$UNIXtimestamp', 1000]]], 18000000]]],
    'day1' => ['$dayOfWeek' => ['$add' => [['$toDate' => ['$multiply' => ['$UNIXtimestamp', 1000]]], 18000000]]],
    ]],
    ['$match' => ['day' => $day ]],
    ['$group' => [
      '_id' => ['year' => '$year', 'month' => '$month', 'date' => '$day','day' => '$day1'], 
      'document' => ['$push' => '$$ROOT']
      ]], 
    ['$project' => [
      'firstRead1' => ['$min' => ['$filter' => ['input' => '$document', 'as' => 'doc', 'cond' => ['$gt' => ['$$doc.u1', 0]]]]], 
      'lastRead1' => ['$max' => ['$filter' => ['input' => '$document', 'as' => 'doc', 'cond' => ['$gt' => ['$$doc.u1', 0]]]]],
      'firstRead2' => ['$min' => ['$filter' => ['input' => '$document', 'as' => 'doc', 'cond' => ['$gt' => ['$$doc.u2', 0]]]]],
      'lastRead2' => ['$max' => ['$filter' => ['input' => '$document', 'as' => 'doc', 'cond' => ['$gt' => ['$$doc.u2', 0]]]]],
      'firstRead3' => ['$min' => ['$filter' => ['input' => '$document', 'as' => 'doc', 'cond' => ['$gt' => ['$$doc.u3', 0]]]]],
      'lastRead3' => ['$max' => ['$filter' => ['input' => '$document', 'as' => 'doc', 'cond' => ['$gt' => ['$$doc.u3', 0]]]]],
     'firstRead4' => ['$min' => ['$filter' => ['input' => '$document', 'as' => 'doc', 'cond' => ['$gt' => ['$$doc.u4', 0]]]]],
      'lastRead4' => ['$max' => ['$filter' => ['input' => '$document', 'as' => 'doc', 'cond' => ['$gt' => ['$$doc.u4', 0]]]]],
      'firstRead5' => ['$min' => ['$filter' => ['input' => '$document', 'as' => 'doc', 'cond' => ['$gt' => ['$$doc.u5', 0]]]]],
      'lastRead5' => ['$max' => ['$filter' => ['input' => '$document', 'as' => 'doc', 'cond' => ['$gt' => ['$$doc.u5', 0]]]]],
      'firstRead6' => ['$min' => ['$filter' => ['input' => '$document', 'as' => 'doc', 'cond' => ['$gt' => ['$$doc.u6', 0]]]]],
      'lastRead6' => ['$max' => ['$filter' => ['input' => '$document', 'as' => 'doc', 'cond' => ['$gt' => ['$$doc.u6', 0]]]]],
      'firstRead7' => ['$min' => ['$filter' => ['input' => '$document', 'as' => 'doc', 'cond' => ['$gt' => ['$$doc.u7', 0]]]]],
      'lastRead7' => ['$max' => ['$filter' => ['input' => '$document', 'as' => 'doc', 'cond' => ['$gt' => ['$$doc.u7', 0]]]]],
      'firstRead8' => ['$min' => ['$filter' => ['input' => '$document', 'as' => 'doc', 'cond' => ['$gt' => ['$$doc.u8', 0]]]]],
      'lastRead8' => ['$max' => ['$filter' => ['input' => '$document', 'as' => 'doc', 'cond' => ['$gt' => ['$$doc.u8', 0]]]]],
      'firstRead9' => ['$min' => ['$filter' => ['input' => '$document', 'as' => 'doc', 'cond' => ['$gt' => ['$$doc.u9', 0]]]]],
      'lastRead9' => ['$max' => ['$filter' => ['input' => '$document', 'as' => 'doc', 'cond' => ['$gt' => ['$$doc.u9', 0]]]]],
      'firstRead10' => ['$min' => ['$filter' => ['input' => '$document', 'as' => 'doc', 'cond' => ['$gt' => ['$$doc.u10', 0]]]]],
      'lastRead10' => ['$max' => ['$filter' => ['input' => '$document', 'as' => 'doc', 'cond' => ['$gt' => ['$$doc.u10', 0]]]]],
      'firstRead11' => ['$min' => ['$filter' => ['input' => '$document', 'as' => 'doc', 'cond' => ['$gt' => ['$$doc.u11', 0]]]]],
      'lastRead11' => ['$max' => ['$filter' => ['input' => '$document', 'as' => 'doc', 'cond' => ['$gt' => ['$$doc.u11', 0]]]]],
      
      ]],
    ['$project' => [

      'kWh0' => ['$subtract' => ['$lastRead1.u1', '$firstRead1.u1']],
      'kWh3' => ['$subtract' => ['$lastRead2.u2', '$firstRead2.u2']],
      'kWh4' => ['$subtract' => ['$lastRead3.u3', '$firstRead3.u3']],
      'kWh5' => ['$subtract' => ['$lastRead4.u4', '$firstRead4.u4']],
      'kWh6' => ['$subtract' => ['$lastRead5.u5', '$firstRead5.u5']],
      'kWh7' => ['$subtract' => ['$lastRead6.u6', '$firstRead6.u6']],
      'kWh8' => ['$subtract' => ['$lastRead7.u7', '$firstRead7.u7']],
      'kWh9' => ['$subtract' => ['$lastRead8.u8', '$firstRead8.u8']],
      'kWh10' => ['$subtract' => ['$lastRead9.u9', '$firstRead9.u9']],
      'kWh11' => ['$subtract' => ['$lastRead10.u10', '$firstRead10.u10']],
      'kWh12' => ['$subtract' => ['$lastRead11.u11', '$firstRead11.u11']],
      ]]
    ]);
    $docs = $cursor->toArray();
    $index = 0;
    foreach ($docs as $document) {
      json_encode($document);
    //  var_dump($document);
    if($document['_id']['day']==1){$array[] = array(
      'Days' =>  'Su',
      $value => $document['kWh0']+$document['kWh3']+$document['kWh4']+$document['kWh5']+$document['kWh6']+$document['kWh7']+$document['kWh8']+$document['kWh9']+$document['kWh10']+$document['kWh11']+$document['kWh12']
     );}
    else if($document['_id']['day']==2){$array[] = array(
      'Days' =>  'Mon',
      $value => $document['kWh0']+$document['kWh3']+$document['kWh4']+$document['kWh5']+$document['kWh6']+$document['kWh7']+$document['kWh8']+$document['kWh9']+$document['kWh10']+$document['kWh11']+$document['kWh12']
     );}
     else if($document['_id']['day']==3){$array[] = array(
      'Days' =>  'Tue',
      $value => $document['kWh0']+$document['kWh3']+$document['kWh4']+$document['kWh5']+$document['kWh6']+$document['kWh7']+$document['kWh8']+$document['kWh9']+$document['kWh10']+$document['kWh11']+$document['kWh12']
     );}
     else if($document['_id']['day']==4){$array[] = array(
      'Days' =>  'Wed',
      $value => $document['kWh0']+$document['kWh3']+$document['kWh4']+$document['kWh5']+$document['kWh6']+$document['kWh7']+$document['kWh8']+$document['kWh9']+$document['kWh10']+$document['kWh11']+$document['kWh12']
     );}
     else if($document['_id']['day']==5){$array[] = array(
      'Days' =>  'Thur',
      $value => $document['kWh0']+$document['kWh3']+$document['kWh4']+$document['kWh5']+$document['kWh6']+$document['kWh7']+$document['kWh8']+$document['kWh9']+$document['kWh10']+$document['kWh11']+$document['kWh12']
     );}
     else if($document['_id']['day']==6){$array[] = array(
      'Days' =>  'Fri',
      $value => $document['kWh0']+$document['kWh3']+$document['kWh4']+$document['kWh5']+$document['kWh6']+$document['kWh7']+$document['kWh8']+$document['kWh9']+$document['kWh10']+$document['kWh11']+$document['kWh12']
     );}
     else if($document['_id']['day']==7){$array[] = array(
      'Days' =>  'Sat',
      $value => $document['kWh0']+$document['kWh3']+$document['kWh4']+$document['kWh5']+$document['kWh6']+$document['kWh7']+$document['kWh8']+$document['kWh9']+$document['kWh10']+$document['kWh11']+$document['kWh12']
     );}
    }
   return $array;
}
function fetchYearlyConsumption($tag_values) {
  $db = connectDB();
  $collection = $db->CBL_b;

  $currentYear = intval(date('Y'));
  $previousYear = $currentYear - 1;

  $cursor = $collection->aggregate([
      ['$project' => [
          'UNIXtimestamp' => 1,
          'year' => ['$year' => ['$add' => [['$toDate' => ['$multiply' => ['$UNIXtimestamp', 1000]]], 18000000]]],
          'month' => ['$month' => ['$add' => [['$toDate' => ['$multiply' => ['$UNIXtimestamp', 1000]]], 18000000]]],
          'u1' => '$' . $tag_values[0],
          'u2' => '$' . $tag_values[1],
      ]],
      ['$match' => ['year' => ['$in' => [$currentYear, $previousYear]]]],
      ['$group' => [
          '_id' => ['year' => '$year', 'month' => '$month'],
          'firstRead1' => ['$first' => '$u1'],
          'lastRead1' => ['$last' => '$u1'],
          'firstRead2' => ['$first' => '$u2'],
          'lastRead2' => ['$last' => '$u2'],
      ]],
      ['$project' => [
          '_id' => 1,
          'total_kWh1' => ['$subtract' => ['$lastRead1', '$firstRead1']],
          'total_kWh2' => ['$subtract' => ['$lastRead2', '$firstRead2']],
      ]],
      ['$sort' => ['_id.year' => 1, '_id.month' => 1]]
  ]);

  $docs = $cursor->toArray();
  $yearData = [];

  // Initialize array to store monthly data (ensures months without data show as 0)
  for ($i = 1; $i <= 12; $i++) {
      $yearData[$i] = [
          'Month' => date('M', mktime(0, 0, 0, $i, 1)),
          'Previous Year (kWh)' => 0,
          'Current Year (kWh)' => 0
      ];
  }

  // Assign data from MongoDB results
  foreach ($docs as $document) {
      $month = $document['_id']['month'];
      $year = $document['_id']['year'];
      $total_kWh = $document['total_kWh1'] + $document['total_kWh2'];

      if ($year == $previousYear) {
          $yearData[$month]['Previous Year (kWh)'] = max(0, $total_kWh); // Ensure no negative values
      } elseif ($year == $currentYear) {
          $yearData[$month]['Current Year (kWh)'] = max(0, $total_kWh);
      }
  }

  return array_values($yearData); // Convert associative array to indexed array for JSON output
}


if ($value=='today') {
  $start_date = date('Y-n-j', strtotime($current_date));
  // $data has current day hours
  $data = fetchHourly($start_date, $tag_values,$numberOfMeters,1);
  $start_date = date('Y-n-j', strtotime($current_date . ' -1 day'));
  // $data1 has previous day hours
  $data1 = fetchHourly($start_date, $tag_values,$numberOfMeters,2);
  $size1= sizeof($data);
  $size2=sizeof($data1);
  $size=max($size1,$size2);
  $size=intval($size);
  // echo '<pre>',print_r($data),'</pre>';
  // echo '<pre>',print_r($data1),'</pre>';
 
  for ($i=0;$i<$size;$i++){
    // this if condition tells to print values at the same hour but previous day will always have values greater then current day 
    // because the previous day is completed but current day is still going 
    if($data1[$i]['_id']['hour']==$data[$i]['_id']['hour']){
      // replaces null values with zeros
      if($data1[$i]['kWh']+$data1[$i]['kWh3']+$data1[$i]['kWh4']+$data1[$i]['kWh5']+$data1[$i]['kWh6']+$data1[$i]['kWh7']+$data1[$i]['kWh8']+$data1[$i]['kWh9']+$data1[$i]['kWh10']+$data1[$i]['kWh11']+$data1[$i]['kWh12']
      ==null&&$data[$i]['kWh']+$data[$i]['kWh3']+$data[$i]['kWh4']+$data[$i]['kWh5']+$data[$i]['kWh6']+$data[$i]['kWh7']+$data[$i]['kWh8']+$data[$i]['kWh9']+$data[$i]['kWh10']+$data[$i]['kWh11']+$data[$i]['kWh12']
      ==null){
        $array[] = array(
          'Time' =>  $data[$i]['_id']['hour'].':00',
          'Yesterday' => 0,
          'Today' => 0,
         );
       }
      elseif($data[$i]['kWh']+$data[$i]['kWh3']+$data[$i]['kWh4']+$data[$i]['kWh5']+$data[$i]['kWh6']+$data[$i]['kWh7']+$data[$i]['kWh8']+$data[$i]['kWh9']+$data[$i]['kWh10']+$data[$i]['kWh11']+$data[$i]['kWh12']==null){
        $array[] = array(
          'Time' =>  $data[$i]['_id']['hour'].':00',
          'Yesterday' => $data1[$i]['kWh']+$data1[$i]['kWh3']+$data1[$i]['kWh4']+$data1[$i]['kWh5']+$data1[$i]['kWh6']+$data1[$i]['kWh7']+$data1[$i]['kWh8']+$data1[$i]['kWh9']+$data1[$i]['kWh10']+$data1[$i]['kWh11']+$data1[$i]['kWh12'],
          'Today' => 0,
         );
       }
       elseif($data1[$i]['kWh']==null){
        $array[] = array(
          'Time' =>  $data[$i]['_id']['hour'].':00',
          'Yesterday' => 0,
          'Today' => $data[$i]['kWh']+$data[$i]['kWh3']+$data[$i]['kWh4']+$data[$i]['kWh5']+$data[$i]['kWh6']+$data[$i]['kWh7']+$data[$i]['kWh8']+$data[$i]['kWh9']+$data[$i]['kWh10']+$data[$i]['kWh11'],+$data[$i]['kWh12'],
         );
       }
       // assigns if both values are not null
       else{
        $array[] = array(
          'Time' =>  $data[$i]['_id']['hour'].':00',
          'Yesterday' => $data1[$i]['kWh']+$data1[$i]['kWh3']+$data1[$i]['kWh4']+$data1[$i]['kWh5']+$data1[$i]['kWh6']+$data1[$i]['kWh7']+$data1[$i]['kWh8']+$data1[$i]['kWh9']+$data1[$i]['kWh10']+$data1[$i]['kWh11'],+$data1[$i]['kWh12'],
          'Today' => $data[$i]['kWh']+$data[$i]['kWh3']+$data[$i]['kWh4']+$data[$i]['kWh5']+$data[$i]['kWh6']+$data[$i]['kWh7']+$data[$i]['kWh8']+$data[$i]['kWh9']+$data[$i]['kWh10']+$data[$i]['kWh11'],+$data[$i]['kWh12'],
        );
        }
    }
    // when previous event has happened but the current event is yet to happen 
    elseif($data1[$i]['_id']['hour']!=$data[$i]['_id']['hour']){
      $array[] = array(
        'Time' =>  $data1[$i]['_id']['hour'].':00',
        'Yesterday' => $data1[$i]['kWh']+$data1[$i]['kWh3']+$data1[$i]['kWh4']+$data1[$i]['kWh5']+$data1[$i]['kWh6']+$data1[$i]['kWh7']+$data1[$i]['kWh8']+$data1[$i]['kWh9']+$data1[$i]['kWh10']+$data1[$i]['kWh11'],+$data1[$i]['kWh12'],
        'Today' => 0,
       );
    }
  }
  
}
elseif ($value == 'year') {
  $array = fetchYearlyConsumption($tag_values);
}
elseif ($value=='month') {
  $m1= date('n', strtotime($current_date));
  $y1= date('Y', strtotime($current_date));
  $m2 = date('n', strtotime($current_date . ' +1 month'));
  $y2 = date('Y', strtotime($current_date . ' +1 month'));
  $m3 = date('n', strtotime($current_date . ' -1 month'));
  
  $start_date =$y1.'-'.$m1.'-1';
  $end_date =$y2.'-'.$m2.'-1';
  
  $data = fetchWeekly($start_date,$end_date, $tag_values,$numberOfMeters);
  $m1= date('n', strtotime($current_date));
  $y1= date('Y', strtotime($current_date));
  $d=cal_days_in_month(CAL_GREGORIAN,$m3,$y1);
  $p=strtotime($current_date . ' -'.$d.' days');
  $m2 = date('n',$p );
  $y2 = date('Y',$p );
  $start_date =$y2.'-'.$m2.'-1';
  $end_date =$y1.'-'.$m1.'-1';
  $data1= fetchWeekly($start_date,$end_date, $tag_values,$numberOfMeters);
  $size1= sizeof($data);
  $size2=sizeof($data1);
  $size=max($size1,$size2);
  $size=intval($size);
  $no=1;
  // var_dump($data);
  for ($i=0;$i<$size;$i++){
    // $array[] = array(
    //   'Weeks' =>  'Week'.$no,
    //   'Last Month' => $data1[$i]['kWh0'],
    //   'This Month' => $data[$i]['kWh0']
    //  );
    // replaces null values with zeros
    if($data[$i]['kWh0']+$data[$i]['kWh3']+$data[$i]['kWh4']+$data[$i]['kWh5']+$data[$i]['kWh6']+$data[$i]['kWh7']+$data[$i]['kWh8']+$data[$i]['kWh9']+$data[$i]['kWh10']+$data[$i]['kWh11']+$data[$i]['kWh12']
    ==null && $data1[$i]['kWh0']+$data1[$i]['kWh3']+$data1[$i]['kWh4']+$data1[$i]['kWh5']+$data1[$i]['kWh6']+$data1[$i]['kWh7']+$data1[$i]['kWh8']+$data1[$i]['kWh9']+$data1[$i]['kWh10']+$data1[$i]['kWh11']+$data1[$i]['kWh12']==null){
      $array[] = array(
      'Weeks' =>  'Week'.$no,
      'Last Month' => 0,
      'This Month' => 0
     );
     }
    elseif($data[$i]['kWh0']+$data[$i]['kWh3']+$data[$i]['kWh4']+$data[$i]['kWh5']+$data[$i]['kWh6']+$data[$i]['kWh7']+$data[$i]['kWh8']+$data[$i]['kWh9']+$data[$i]['kWh10']+$data[$i]['kWh11']+$data[$i]['kWh12']==null){
      $array[] = array(
        'Weeks' =>  'Week'.$no,
        'Last Month' => $data1[$i]['kWh0']+$data1[$i]['kWh3']+$data1[$i]['kWh4']+$data1[$i]['kWh5']+$data1[$i]['kWh6']+$data1[$i]['kWh7']+$data1[$i]['kWh8']+$data1[$i]['kWh9']+$data1[$i]['kWh10']+$data1[$i]['kWh11']+$data1[$i]['kWh12'],
        'This Month' => 0
       );
     }
     elseif($data1[$i]['kWh0']+$data1[$i]['kWh3']+$data1[$i]['kWh4']+$data1[$i]['kWh5']+$data1[$i]['kWh6']+$data1[$i]['kWh7']+$data1[$i]['kWh8']+$data1[$i]['kWh9']+$data1[$i]['kWh10']+$data1[$i]['kWh11']+$data1[$i]['kWh12']==null){
      $array[] = array(
        'Weeks' =>  'Week'.$no,
        'Last Month' => 0,
        'This Month' => $data[$i]['kWh0']+$data[$i]['kWh3']+$data[$i]['kWh4']+$data[$i]['kWh5']+$data[$i]['kWh6']+$data[$i]['kWh7']+$data[$i]['kWh8']+$data[$i]['kWh9']+$data[$i]['kWh10']+$data[$i]['kWh11']+$data[$i]['kWh12'],
       );
     }
     // assigns if both values are not null
     else{
      $array[] = array(
        'Weeks' =>  'Week'.$no,
        'Last Month' => $data1[$i]['kWh0']+$data1[$i]['kWh3']+$data1[$i]['kWh4']+$data1[$i]['kWh5']+$data1[$i]['kWh6']+$data1[$i]['kWh7']+$data1[$i]['kWh8']+$data1[$i]['kWh9']+$data1[$i]['kWh10']+$data1[$i]['kWh11'],+$data1[$i]['kWh12'],
        'This Month' => $data[$i]['kWh0']+$data[$i]['kWh3']+$data[$i]['kWh4']+$data[$i]['kWh5']+$data[$i]['kWh6']+$data[$i]['kWh7']+$data[$i]['kWh8']+$data[$i]['kWh9']+$data[$i]['kWh10']+$data[$i]['kWh11']+$data[$i]['kWh12'],



       );
      }
    $no++;
  }
}
elseif($value=='week'){
  $current_day = date("l");
  if ($current_day == 'Monday') { $start_date = date('Y-n-j', strtotime($current_date . ' -7 day'));}
  elseif ($current_day == 'Tuesday') {$start_date = date('Y-n-j', strtotime($current_date . ' -8 day'));}
  elseif ($current_day == 'Wednesday') {$start_date = date('Y-n-j', strtotime($current_date . ' -9 day'));}
  elseif ($current_day == 'Thursday') {$start_date = date('Y-n-j', strtotime($current_date . ' -10 day'));}
  elseif ($current_day == 'Friday') { $start_date = date('Y-n-j', strtotime($current_date . ' -11 day'));}
  elseif ($current_day == 'Saturday') {$start_date = date('Y-n-j', strtotime($current_date . ' -12 day'));}
  elseif ($current_day == 'Sunday') {$start_date = date('Y-n-j', strtotime($current_date . ' -13 day'));}
  for ($i=0;$i<=6;$i++){ 
    $info[$i]= fetchDayWise( $start_date, $tag_values,$numberOfMeters,'Last Week');
    $start_date = date('Y-n-j', strtotime($start_date . ' +1 day'));
    }
  $current_day = date("l");
  if ($current_day == 'Monday') {$start_date = date("Y-n-j");} 
  elseif ($current_day == 'Tuesday') { $start_date = date('Y-n-j', strtotime($current_date . ' -1 day'));} 
  elseif ($current_day == 'Wednesday') {$start_date = date('Y-n-j', strtotime($current_date . ' -2 day'));} 
  elseif ($current_day == 'Thursday') {$start_date = date('Y-n-j', strtotime($current_date . ' -3 day'));} 
  elseif ($current_day == 'Friday') {$start_date = date('Y-n-j', strtotime($current_date . ' -4 day'));} 
  elseif ($current_day == 'Saturday') { $start_date = date('Y-n-j', strtotime($current_date . ' -5 day')); } 
  elseif ($current_day == 'Sunday') {$start_date = date('Y-n-j', strtotime($current_date . ' -6 day'));}
  $current_date = date('Y-n-j', strtotime($current_date));
  $dateDiff = dateDiffInDays($start_date, $current_date);
  $dateDiff = $dateDiff + 1;
  for ($i = 0; $i < $dateDiff; $i++) {
      $info1[$i]= fetchDayWise( $start_date, $tag_values,$numberOfMeters,'This Week');
      $start_date = date('Y-n-j', strtotime($start_date . ' +1 day'));
    }
  // echo var_dump($info1).'<br>';
  for ($i=0;$i<7;$i++){
    // $array[] =$info[$i][0];
    // if($info1[$i][0]==null)
    // {
    //   $array[] = array(
    //     'Days' => $info[$i][0]['Days'],
    //     'This Week' => 0
    //    );
    // }
    // else
    // {
    // $array[] =$info1[$i][0];
    // }

    ///////////////////////

    if($info1[$i][0]['This Week']==null && $info[$i][0]['Last Week']==null){
      $array[] = array(
        'Days' => $info[$i][0]['Days'],
        'Last Week (kWh)' => 0,
        'This Week (kWh)' => 0
       );
     }
    elseif($info1[$i][0]['This Week']==null){
      $array[] = array(
        'Days' => $info[$i][0]['Days'],
        'Last Week (kWh)' => $info[$i][0]['Last Week'],
        'This Week (kWh)' => 0
       );
     }
     elseif($info[$i][0]['Last Week']==null){
      $array[] = array(
        'Days' => $info[$i][0]['Days'],
        'Last Week (kWh)' => 0,
        'This Week (kWh)' => $info1[$i][0]['This Week']
       );
     }
     // assigns if both values are not null
     else{
      $array[] = array(
        'Days' => $info[$i][0]['Days'],
        'Last Week (kWh)' => $info[$i][0]['Last Week'],
        'This Week (kWh)' => $info1[$i][0]['This Week']
       );
      }
  }
}
echo json_encode($array);
?>