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
      $client = new MongoDB\Client("mongodb://admin:cisco123@13.234.241.103:27017/?authSource=iotdb&readPreference=primary&appname=MongoDB%20Compass&ssl=false");
      return $client->iotdb;
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
        'U_5_EM5_TotalActiveEnergy_kWh',
        'U_6_EM6_TotalActiveEnergy_kWh',
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
    ['$project' => ['UNIXtimestamp' => 1, $tag_values[0] => 1, $tag_values[1] => 1]], 
    ['$match' => ['UNIXtimestamp' => ['$gte' => $currentDayUNIX ]]], 
    ['$project' => [
        'hour' => ['$hour' => ['$add' => [['$toDate' => ['$multiply' => ['$UNIXtimestamp', 1000]]], 18000000]]],
        'day' => ['$dayOfMonth' => ['$add' => [['$toDate' => ['$multiply' => ['$UNIXtimestamp', 1000]]], 18000000]]],
        'month' => ['$month' => ['$add' => [['$toDate' => ['$multiply' => ['$UNIXtimestamp', 1000]]], 18000000]]],
        'year' => ['$year' => ['$add' => [['$toDate' => ['$multiply' => ['$UNIXtimestamp', 1000]]], 18000000]]],
        'u1' => '$' . $tag_values[0],
        'u2' => '$' . $tag_values[1],
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
    ]],
    ['$project' => [
        'kWh' . $Label => ['$subtract' => ['$lastRead1.u1', '$firstRead1.u1']],
        'kWh3' . $Label => ['$subtract' => ['$lastRead2.u2', '$firstRead2.u2']], // Added for u2
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
    ['$project' => ['UNIXtimestamp' => 1, $tag_values[0] => 1, $tag_values[1] => 1]],
    ['$match' => ['UNIXtimestamp' => ['$gte' => $monthStartUnix,'$lte' => $monthEndUnix ]]],
    ['$project' => [
      'UNIXtimestamp'=>1,
      'date' => ['$add' => [['$toDate' => ['$multiply' => ['$UNIXtimestamp', 1000]]], 18000000]],
      'u1'=>'$'.$tag_values[0],
      'u2' => '$' . $tag_values[1],
       ]],
    ['$project' => [
        'UNIXtimestamp'=>1,
        'date' => ['$dateToString'=>['format'=>'%Y-%m-%d','date'=>'$date']],
        'u1'=>1,
        'u2'=>1,
         ]],
    ['$project' => [
        'date'=>1,
        'week' => ['$week' => ['$add' => [['$toDate' => ['$multiply' => ['$UNIXtimestamp', 1000]]], 18000000]]],
        'u1'=>1,
        'u2'=>1,
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
  ]],
  ['$project' => [
    'day1'=>'$firstRead1.date','$firstRead2.date',
    'day2'=>'$lastRead1.date','$lastRead2.date',
    'kWh0'=>['$subtract'=>['$lastRead1.u1','$firstRead1.u1']],
    'kWh3' => ['$subtract' => ['$lastRead2.u2', '$firstRead2.u2']],
    
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
    ['$project' => ['UNIXtimestamp' => 1,  $tag_values[0] => 1,$tag_values[1] => 1,]], 
    ['$match' => ['UNIXtimestamp' => ['$gte' => $currentDayUNIX]]],
    ['$project' => [
    'u1' => '$'.$tag_values[0], 
    'u2' => '$' . $tag_values[1],
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
    ]],
    ['$project' => [
      'kWh0' => ['$subtract' => ['$lastRead1.u1', '$firstRead1.u1']],
      'kWh3' => ['$subtract' => ['$lastRead2.u2', '$firstRead2.u2']],
      ]]
    ]);
    $docs = $cursor->toArray();
    $index = 0;
    foreach ($docs as $document) {
      json_encode($document);
    //  var_dump($document);
    if($document['_id']['day']==1){$array[] = array(
      'Days' =>  'Su',
      $value => $document['kWh0']+$document['kWh3']
     );}
    else if($document['_id']['day']==2){$array[] = array(
      'Days' =>  'Mon',
      $value => $document['kWh0']+$document['kWh3']
     );}
     else if($document['_id']['day']==3){$array[] = array(
      'Days' =>  'Tue',
      $value => $document['kWh0']+$document['kWh3']
     );}
     else if($document['_id']['day']==4){$array[] = array(
      'Days' =>  'Wed',
      $value => $document['kWh0']+$document['kWh3']
     );}
     else if($document['_id']['day']==5){$array[] = array(
      'Days' =>  'Thur',
      $value => $document['kWh0']+$document['kWh3']
     );}
     else if($document['_id']['day']==6){$array[] = array(
      'Days' =>  'Fri',
      $value => $document['kWh0']+$document['kWh3']
     );}
     else if($document['_id']['day']==7){$array[] = array(
      'Days' =>  'Sat',
      $value => $document['kWh0']+$document['kWh3']
     );}
    }
   return $array;
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
      if($data1[$i]['kWh2']+$data1[$i]['kWh3']==null&&$data[$i]['kWh1']+$data[$i]['kWh3']==null){
        $array[] = array(
          'Time' =>  $data[$i]['_id']['hour'].':00',
          'Yesterday' => 0,
          'Today' => 0,
         );
       }
      elseif($data[$i]['kWh1']+$data[$i]['kWh3']==null){
        $array[] = array(
          'Time' =>  $data[$i]['_id']['hour'].':00',
          'Yesterday' => $data1[$i]['kWh2']+$data1[$i]['kWh3'],
          'Today' => 0,
         );
       }
       elseif($data1[$i]['kWh2']==null){
        $array[] = array(
          'Time' =>  $data[$i]['_id']['hour'].':00',
          'Yesterday' => 0,
          'Today' => $data[$i]['kWh1']+$data[$i]['kWh3'],
         );
       }
       // assigns if both values are not null
       else{
        $array[] = array(
          'Time' =>  $data[$i]['_id']['hour'].':00',
          'Yesterday' => $data1[$i]['kWh2']+$data1[$i]['kWh3'],
          'Today' => $data[$i]['kWh1']+$data[$i]['kWh3'],
        );
        }
    }
    // when previous event has happened but the current event is yet to happen 
    elseif($data1[$i]['_id']['hour']!=$data[$i]['_id']['hour']){
      $array[] = array(
        'Time' =>  $data1[$i]['_id']['hour'].':00',
        'Yesterday' => $data1[$i]['kWh2']+$data1[$i]['kWh3'],
        'Today' => 0,
       );
    }
  }
  
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
    if($data[$i]['kWh0']+$data[$i]['kWh3']==null && $data1[$i]['kWh0']+$data1[$i]['kWh3']==null){
      $array[] = array(
      'Weeks' =>  'Week'.$no,
      'Last Month' => 0,
      'This Month' => 0
     );
     }
    elseif($data[$i]['kWh0']+$data[$i]['kWh3']==null){
      $array[] = array(
        'Weeks' =>  'Week'.$no,
        'Last Month' => $data1[$i]['kWh0']+$data1[$i]['kWh3'],
        'This Month' => 0
       );
     }
     elseif($data1[$i]['kWh0']+$data1[$i]['kWh3']==null){
      $array[] = array(
        'Weeks' =>  'Week'.$no,
        'Last Month' => 0,
        'This Month' => $data[$i]['kWh0']+$data[$i]['kWh3']
       );
     }
     // assigns if both values are not null
     else{
      $array[] = array(
        'Weeks' =>  'Week'.$no,
        'Last Month' => $data1[$i]['kWh0']+$data1[$i]['kWh3'],
        'This Month' => $data[$i]['kWh0']+$data[$i]['kWh3']
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