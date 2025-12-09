<?php
error_reporting(E_ERROR | E_PARSE);
require 'vendor/autoload.php';
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
date_default_timezone_set('Asia/Karachi');
$value = $_GET['value'];
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
$current_date = date("Y-n-j");
$array=array();
$data = array();
$numberOfMeters=1;
$current_date=date('Y-m-d');
$tag_values = array(
        'F3_MainLine_TotalFlow',
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
  $mongotime=new MongoDB\BSON\UTCDateTime(strtotime($day.'T0:0:0+05:00'));
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
    ['$project' => ['UNIXtimestamp' => 1, $tag_values[0] => 1]], 
    ['$match' => ['UNIXtimestamp' => ['$gte' => $currentDayUNIX ]]], 
    ['$project' => [
    'hour' => ['$hour' => ['$add' => [['$toDate' => ['$multiply' => ['$UNIXtimestamp', 1000]]], 18000000]]],
    'day' => ['$dayOfMonth' => ['$add' => [['$toDate' => ['$multiply' => ['$UNIXtimestamp', 1000]]], 18000000]]],
    'month' => ['$month' => ['$add' => [['$toDate' => ['$multiply' => ['$UNIXtimestamp', 1000]]], 18000000]]],
    'year' => ['$year' => ['$add' => [['$toDate' => ['$multiply' => ['$UNIXtimestamp', 1000]]], 18000000]]],
    'u1'=>'$'.$tag_values[0],
     ]],
    ['$match' => ['year' => $year,'month' => $month,'day' => $day]], 
    ['$group' => ['_id' => ['year'=>'$year','month'=>'$month','day'=>'$day','hour'=>'$hour',],
     'document' => ['$push' => '$$ROOT'],
     ]
    ],
    ['$sort'=>['_id.hour'=>1]],
    ['$project' => ['firstRead1'=>['$min'=>['$filter'=>['input'=>'$document','as'=>'doc','cond'=>['$gt'=>['$$doc.u1',0]]]]],
      'lastRead1'=>['$max'=>['$filter'=>['input'=>'$document','as'=>'doc','cond'=>['$gt'=>['$$doc.u1',0]]]]],
      ]
  ],
    ['$project' => [
        'kWh'.$Label=>['$subtract'=>['$lastRead1.u1','$firstRead1.u1']],
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
    ['$project' => ['UNIXtimestamp' => 1, $tag_values[0] => 1]],
    ['$match' => ['UNIXtimestamp' => ['$gte' => $monthStartUnix,'$lte' => $monthEndUnix ]]],
    ['$project' => [
      'UNIXtimestamp'=>1,
      'date' => ['$add' => [['$toDate' => ['$multiply' => ['$UNIXtimestamp', 1000]]], 18000000]],
      'u1'=>'$'.$tag_values[0],
       ]],
    ['$project' => [
        'UNIXtimestamp'=>1,
        'date' => ['$dateToString'=>['format'=>'%Y-%m-%d','date'=>'$date']],
        'u1'=>1,
         ]],
    ['$project' => [
        'date'=>1,
        'week' => ['$week' => ['$add' => [['$toDate' => ['$multiply' => ['$UNIXtimestamp', 1000]]], 18000000]]],
        'u1'=>1,
         ]],
    ['$group' => ['_id' => '$week',
         'document' => ['$push' => '$$ROOT'],
         ]
        ],
    ['$sort'=>['_id'=>1]],
    ['$project' => ['firstRead1'=>['$min'=>['$filter'=>['input'=>'$document','as'=>'doc','cond'=>['$gt'=>['$$doc.u1',0]]]]],
      'lastRead1'=>['$max'=>['$filter'=>['input'=>'$document','as'=>'doc','cond'=>['$gt'=>['$$doc.u1',0]]]]],
      ]
  ],
  ['$project' => [
    'day1'=>'$firstRead1.date',
    'day2'=>'$lastRead1.date',
    'kWh0'=>['$subtract'=>['$lastRead1.u1','$firstRead1.u1']],
    
]],
  ]);
  $docs = $cursor->toArray();
  return $docs;
}
function fetchYearlyWaterConsumption($tag_values) {
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
      ]],
      ['$match' => ['year' => ['$in' => [$currentYear, $previousYear]]]],
      ['$group' => [
          '_id' => ['year' => '$year', 'month' => '$month'],
          'firstRead1' => ['$first' => '$u1'],
          'lastRead1' => ['$last' => '$u1'],
      ]],
      ['$project' => [
          '_id' => 1,
          'total_consumption' => ['$subtract' => ['$lastRead1', '$firstRead1']],
      ]],
      ['$sort' => ['_id.year' => 1, '_id.month' => 1]]
  ]);

  $docs = $cursor->toArray();
  $yearData = [];

  // Initialize array to store monthly data (ensures months without data show as 0)
  for ($i = 1; $i <= 12; $i++) {
      $yearData[$i] = [
          'Month' => date('M', mktime(0, 0, 0, $i, 1)),
          'Previous Year' => 0,
          'Current Year' => 0
      ];
  }

  // Assign data from MongoDB results
  foreach ($docs as $document) {
      $month = $document['_id']['month'];
      $year = $document['_id']['year'];
      $total_consumption = $document['total_consumption'];

      // Convert flow to cubic meters (assuming it's in liters or another unit)
      $total_consumption_m3 = $total_consumption; 

      if ($year == $previousYear) {
          $yearData[$month]['Previous Year'] = max(0, $total_consumption_m3); // Ensure no negative values
      } elseif ($year == $currentYear) {
          $yearData[$month]['Current Year'] = max(0, $total_consumption_m3);
      }
  }

  return array_values($yearData); // Convert associative array to indexed array for JSON output
}

function fetchDayWise($date, $tag_values, $numberOfMeters, $value){
  $db = connectDB();
  $collection = $db->CBL_b;
  $day = date('d', strtotime($date));
  $day = intval($day);
  $month = date('m', strtotime($date));
  $month = intval($month);
  $year = date('Y', strtotime($date));
  $year = intval($year);
  $currentDayUNIX = GetUNIXday($date);

  $cursor = $collection->aggregate([
    ['$project' => ['UNIXtimestamp' => 1, $tag_values[0] => 1]],
    ['$match' => ['UNIXtimestamp' => ['$gte' => $currentDayUNIX]]],
    ['$project' => [
      'u1' => '$'.$tag_values[0],
      'day' => ['$dayOfMonth' => ['$add' => [['$toDate' => ['$multiply' => ['$UNIXtimestamp', 1000]]], 18000000]]],
      'month' => ['$month' => ['$add' => [['$toDate' => ['$multiply' => ['$UNIXtimestamp', 1000]]], 18000000]]],
      'year' => ['$year' => ['$add' => [['$toDate' => ['$multiply' => ['$UNIXtimestamp', 1000]]], 18000000]]],
      'dayOfWeek' => ['$dayOfWeek' => ['$add' => [['$toDate' => ['$multiply' => ['$UNIXtimestamp', 1000]]], 18000000]]],
    ]],
    ['$match' => ['day' => $day]],
    ['$group' => [
      '_id' => ['year' => '$year', 'month' => '$month', 'date' => '$day', 'dayOfWeek' => '$dayOfWeek'],
      'document' => ['$push' => '$$ROOT']
    ]],
    ['$project' => [
      'firstRead1' => ['$min' => ['$filter' => ['input' => '$document', 'as' => 'doc', 'cond' => ['$gt' => ['$$doc.u1', 0]]]]],
      'lastRead1' => ['$max' => ['$filter' => ['input' => '$document', 'as' => 'doc', 'cond' => ['$gt' => ['$$doc.u1', 0]]]]],
    ]],
    ['$project' => [
      'kWh0' => ['$subtract' => ['$lastRead1.u1', '$firstRead1.u1']],
    ]]
  ]);

  $docs = $cursor->toArray();
  $array = [];
  foreach ($docs as $document) {
    $dayOfWeek = $document['_id']['dayOfWeek'];
    $dayName = '';
    switch ($dayOfWeek) {
      case 1:
        $dayName = 'Sun';
        break;
      case 2:
        $dayName = 'Mon';
        break;
      case 3:
        $dayName = 'Tue';
        break;
      case 4:
        $dayName = 'Wed';
        break;
      case 5:
        $dayName = 'Thu';
        break;
      case 6:
        $dayName = 'Fri';
        break;
      case 7:
        $dayName = 'Sat';
        break;
    }

    $array[] = array(
      'Days' => $dayName,
      $value => $document['kWh0'] ?? 0
    );
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
      if($data1[$i]['kWh2']==null&&$data[$i]['kWh1']==null){
        $array[] = array(
          'Time' =>  $data[$i]['_id']['hour'].':00',
          'Yesterday' => 0,
          'Today' => 0,
         );
       }
      elseif($data[$i]['kWh1']==null){
        $array[] = array(
          'Time' =>  $data[$i]['_id']['hour'].':00',
          'Yesterday' => ($data1[$i]['kWh2']),
          'Today' => 0,
         );
       }
       elseif($data1[$i]['kWh2']==null){
        $array[] = array(
          'Time' =>  $data[$i]['_id']['hour'].':00',
          'Yesterday' => 0,
          'Today' => ($data[$i]['kWh1']),
         );
       }
       // assigns if both values are not null
       else{
        $array[] = array(
          'Time' =>  $data[$i]['_id']['hour'].':00',
          'Yesterday' =>($data1[$i]['kWh2']),
          'Today' => ($data[$i]['kWh1']),
        );
        }
    }
    // when previous event has happened but the current event is yet to happen 
    elseif($data1[$i]['_id']['hour']!=$data[$i]['_id']['hour']){
      $array[] = array(
        'Time' =>  $data1[$i]['_id']['hour'].':00',
        'Yesterday' => ($data1[$i]['kWh2']),
        'Today' => 0,
       );
    }
  }
  
} elseif ($value == 'year') {
  $array = fetchYearlyWaterConsumption($tag_values);
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
    if($data[$i]['kWh0']==null && $data1[$i]['kWh0']==null){
      $array[] = array(
      'Weeks' =>  'Week'.$no,
      'Last Month' => 0,
      'This Month' => 0
     );
     }
    elseif($data[$i]['kWh0']==null){
      $array[] = array(
        'Weeks' =>  'Week'.$no,
        'Last Month' => ($data1[$i]['kWh0']),
        'This Month' => 0
       );
     }
     elseif($data1[$i]['kWh0']==null){
      $array[] = array(
        'Weeks' =>  'Week'.$no,
        'Last Month' => 0,
        'This Month' => ($data[$i]['kWh0']),
       );
     }
     // assigns if both values are not null
     else{
      $array[] = array(
        'Weeks' =>  'Week'.$no,
        'Last Month' =>($data1[$i]['kWh0']),
        'This Month' => ($data[$i]['kWh0']),
       );
      }
    $no++;
  }
}
elseif ($value == 'week') {
  // Get the start date for last week
  $current_day = date("l");
  switch ($current_day) {
    case 'Monday':
      $start_date = date('Y-n-j', strtotime($current_date . ' -7 day'));
      break;
    case 'Tuesday':
      $start_date = date('Y-n-j', strtotime($current_date . ' -8 day'));
      break;
    case 'Wednesday':
      $start_date = date('Y-n-j', strtotime($current_date . ' -9 day'));
      break;
    case 'Thursday':
      $start_date = date('Y-n-j', strtotime($current_date . ' -10 day'));
      break;
    case 'Friday':
      $start_date = date('Y-n-j', strtotime($current_date . ' -11 day'));
      break;
    case 'Saturday':
      $start_date = date('Y-n-j', strtotime($current_date . ' -12 day'));
      break;
    case 'Sunday':
      $start_date = date('Y-n-j', strtotime($current_date . ' -13 day'));
      break;
  }

  $info = [];
  for ($i = 0; $i <= 6; $i++) {
    $info[$i] = fetchDayWise($start_date, $tag_values, $numberOfMeters, 'Last Week');
    $start_date = date('Y-n-j', strtotime($start_date . ' +1 day'));
  }

  // Get the start date for this week
  switch ($current_day) {
    case 'Monday':
      $start_date = date("Y-n-j");
      break;
    case 'Tuesday':
      $start_date = date('Y-n-j', strtotime($current_date . ' -1 day'));
      break;
    case 'Wednesday':
      $start_date = date('Y-n-j', strtotime($current_date . ' -2 day'));
      break;
    case 'Thursday':
      $start_date = date('Y-n-j', strtotime($current_date . ' -3 day'));
      break;
    case 'Friday':
      $start_date = date('Y-n-j', strtotime($current_date . ' -4 day'));
      break;
    case 'Saturday':
      $start_date = date('Y-n-j', strtotime($current_date . ' -5 day'));
      break;
    case 'Sunday':
      $start_date = date('Y-n-j', strtotime($current_date . ' -6 day'));
      break;
  }

  $info1 = [];
  $dateDiff = dateDiffInDays($start_date, $current_date) + 1;
  for ($i = 0; $i < $dateDiff; $i++) {
    $info1[$i] = fetchDayWise($start_date, $tag_values, $numberOfMeters, 'This Week');
    $start_date = date('Y-n-j', strtotime($start_date . ' +1 day'));
  }

  // Initialize array with default values for days of the week
  $daysOfWeek = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
  $array = array_fill(0, 7, ['Days' => null, 'Last Week (kWh)' => 0, 'This Week (kWh)' => 0]);

  for ($i = 0; $i < 7; $i++) {
    $lastWeekData = $info[$i][0]['Last Week'] ?? 0;
    $thisWeekData = $info1[$i][0]['This Week'] ?? 0;
    $dayName = $daysOfWeek[$i];

    $array[$i] = array(
      'Days' => $dayName,
      'Last Week' => $lastWeekData,
      'This Week' => $thisWeekData,
    );
  }
}
echo json_encode($array);
?>