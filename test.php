<?php
session_start();
error_reporting(E_ERROR | E_PARSE);
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
        echo json_encode(["error" => "Failed to connect to MongoDB: " . $e->getMessage()]);
        exit;
    }
}

$db = connectDB();
$collection = $db->CBL_b;
$start_date = $_GET["date1"];
$end_date = $_GET["date2"];
$location = "U_3_EM3_AvgVoltageLL_V";
$selected_fields = explode(",", $location);

$result = array();

foreach ($selected_fields as $tag) {
    $start_date_utc = new DateTime($start_date . 'T00:00:00', new DateTimeZone('Asia/Karachi'));
    $end_date_utc = new DateTime($end_date . 'T23:59:59', new DateTimeZone('Asia/Karachi'));

    $where = array(
        'timestamp' =>  array('$gte' => $start_date_utc->format('Y-m-d\TH:i:sO'), '$lt' => $end_date_utc->format('Y-m-d\TH:i:sO'))
    );

    $select_fields = array(
        'timestamp' => 1,
        $tag => 1
    );

    $options = array(
        'projection' => $select_fields
    );

    $cursor = $collection->find($where, $options);
    $docs = $cursor->toArray();

    $array = array();
    foreach ($docs as $document) {
        $document = json_decode(json_encode($document), true);
        $values = array();

        if (isset($document[$tag])) {
            if (is_array($document[$tag])) {
                foreach ($document[$tag] as $key => $value) {
                    $values[$key] = round($value, 1);
                }
            } else {
                $values = round($document[$tag], 1);
            }
        }

        if (!empty($values)) {
            $array[] = array('date' => $document['timestamp'], 'values' => $values);
        }
    }

    $result[$tag] = $array;
}

$data = json_encode($result);
echo $data;
