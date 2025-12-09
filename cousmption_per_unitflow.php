<?php
error_reporting(E_ERROR | E_PARSE);
require 'vendor/autoload.php';
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
date_default_timezone_set('Asia/Karachi');
$value = $_GET['value'];
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
$current_date = date("Y-n-j");
$array = array();
$data = array();
$numberOfMeters = 1;
$current_date = date('Y-m-d');
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
function GetUNIXday($day)
{
    $mongotime = new MongoDB\BSON\UTCDateTime(strtotime($day . 'T0:0:0+05:00'));
    $val = json_decode(json_encode($mongotime), true);
    foreach ($val as $key => $value) {
        foreach ($value as $sub_key => $sub_value) {
            $a = $sub_value;
        }
    }
    return intval($a);
}

// Global tag definitions
$tag_values = [
    'F3_MainLine_TotalFlow',                // Sensor tag (F3)
    'U_3_EM3_TotalActiveEnergy_kWh',         // Energy tag 1
    'U_4_EM4_TotalActiveEnergy_kWh',         // Energy tag 2
    'U_5_EM5_TotalActiveEnergy_kWh',         // Energy tag 3
    'U_6_EM6_TotalActiveEnergy_kWh',         // Energy tag 4
    'U_7_EM7_ActiveEnergyDelivered_Wh',      // Energy tag 5 (in Wh; to be converted)
    'U_8_EM8_TotalActiveEnergy_kWh',
    'U_9_EM9_ActiveEnergyDelivered_Wh',
    'U_10_EM10_TotalActiveEnergy_kWh',
    'U_15_ActiveEnergy_Delivered_kWh',
    'U_21_ActiveEnergy_Delivered_kWh',
    'U_22_ActiveEnergy_Delivered_kWh'

];
$numberOfMeters = 1;




/**
 * fetchHourly() aggregates hourly data for a given day.
 * It projects the F3_MainLine_TotalFlow and energy tags,
 * groups documents by hour, and computes:
 *    kWh1 = (last_U3 - first_U3) + (last_U4 - first_U4) + (last_U5 - first_U5)
 *           + (last_U6 - first_U6) + ((last_U7 - first_U7)/1000)
 * It also returns the first and last readings for F3_MainLine_TotalFlow
 * as first_F3 and last_F3.
 */
function fetchHourly($date, $tag_values, $numberOfMeters, $Label)
{
    $db = connectDB();
    $collection = $db->CBL_b;
    $day = intval(date('d', strtotime($date)));
    $month = intval(date('m', strtotime($date)));
    $year = intval(date('Y', strtotime($date)));
    $currentDayUNIX = GetUNIXday($date);

    // Build an aggregation pipeline that:
    // 1. Projects the fields we need (using the provided $tag_values)
    // 2. Filters by the given day
    // 3. Extracts hour, day, month, year from the timestamp
    // 4. Groups by hour and uses $first and $last operators to capture first and last readings
    $pipeline = [
        // 1. Project the required fields
        ['$project' => [
            'UNIXtimestamp' => 1,
            $tag_values[0] => 1, // F3_MainLine_TotalFlow
            $tag_values[1] => 1, // U_3_EM3_TotalActiveEnergy_kWh
            $tag_values[2] => 1, // U_4_EM4_TotalActiveEnergy_kWh
            $tag_values[3] => 1, // U_5_EM5_TotalActiveEnergy_kWh
            $tag_values[4] => 1, // U_6_EM6_TotalActiveEnergy_kWh
            $tag_values[5] => 1, // U_7_EM7_ActiveEnergyDelivered_Wh
            $tag_values[6] => 1, // U_8_EM8_TotalActiveEnergy_kWh
            $tag_values[7] => 1, // U_9_EM9_ActiveEnergyDelivered_Wh
            $tag_values[8] => 1, // U_10_EM10_TotalActiveEnergy_kWh
            $tag_values[9] => 1, // U_15_ActiveEnergy_Delivered_kWh
            $tag_values[10] => 1, // U_21_ActiveEnergy_Delivered_kWh
            $tag_values[11] => 1, // U_22_ActiveEnergy_Delivered_kWh
        ]],
        // 2. Filter documents for the given day (by UNIXtimestamp)
        ['$match' => ['UNIXtimestamp' => ['$gte' => $currentDayUNIX]]],
        // 3. Project additional fields: extract hour, day, month, year; and alias the fields for ease
        ['$project' => [
            'hour'  => ['$hour' => ['$toDate' => ['$multiply' => ['$UNIXtimestamp', 1000]]]],
            'day'   => ['$dayOfMonth' => ['$toDate' => ['$multiply' => ['$UNIXtimestamp', 1000]]]],
            'month' => ['$month' => ['$toDate' => ['$multiply' => ['$UNIXtimestamp', 1000]]]],
            'year'  => ['$year' => ['$toDate' => ['$multiply' => ['$UNIXtimestamp', 1000]]]],
            'F3'    => '$' . $tag_values[0],
            'U3'    => '$' . $tag_values[1],
            'U4'    => '$' . $tag_values[2],
            'U5'    => '$' . $tag_values[3],
            'U6'    => '$' . $tag_values[4],
            'U7'    => '$' . $tag_values[5],
            'U8'    => '$' . $tag_values[6],
            'U9'    => '$' . $tag_values[7],
            'U10'   => '$' . $tag_values[8],
            'U15'   => '$' . $tag_values[9],
            'U21'   => '$' . $tag_values[10],
            'U22'   => '$' . $tag_values[11],
        ]],
        // 4. Ensure we are matching the correct day
        ['$match' => ['year' => $year, 'month' => $month, 'day' => $day]],
        // 5. Group by hour and capture the first and last readings for each field
        ['$group' => [
            '_id' => ['year' => '$year', 'month' => '$month', 'day' => '$day', 'hour' => '$hour'],
            'first_F3' => ['$first' => '$F3'],
            'last_F3'  => ['$last'  => '$F3'],
            'first_U3' => ['$first' => '$U3'],
            'last_U3'  => ['$last'  => '$U3'],
            'first_U4' => ['$first' => '$U4'],
            'last_U4'  => ['$last'  => '$U4'],
            'first_U5' => ['$first' => '$U5'],
            'last_U5'  => ['$last'  => '$U5'],
            'first_U6' => ['$first' => '$U6'],
            'last_U6'  => ['$last'  => '$U6'],
            'first_U7' => ['$first' => '$U7'],
            'last_U7'  => ['$last'  => '$U7'],
            'first_U8' => ['$first' => '$U8'],
            'last_U8'  => ['$last'  => '$U8'],
            'first_U9' => ['$first' => '$U9'],
            'last_U9'  => ['$last'  => '$U9'],
            'first_U10' => ['$first' => '$U10'],
            'last_U10'  => ['$last'  => '$U10'],
            'first_U15' => ['$first' => '$U15'],
            'last_U15'  => ['$last'  => '$U15'],
            'first_U21' => ['$first' => '$U21'],
            'last_U21'  => ['$last'  => '$U21'],
            'first_U22' => ['$first' => '$U22'],
            'last_U22'  => ['$last'  => '$U22'],
        ]],
        ['$sort' => ['_id.hour' => 1]],
        // Final projection (optional: here we output all grouped fields)
        ['$project' => [
            '_id' => 1,
            'first_F3' => 1,
            'last_F3'  => 1,
            'first_U3' => 1,
            'last_U3'  => 1,
            'first_U4' => 1,
            'last_U4'  => 1,
            'first_U5' => 1,
            'last_U5'  => 1,
            'first_U6' => 1,
            'last_U6'  => 1,
            'first_U7' => 1,
            'last_U7'  => 1,
            'first_U8' => 1,
            'last_U8'  => 1,
            'first_U9' => 1,
            'last_U9'  => 1,
            'first_U10' => 1,
            'last_U10'  => 1,
            'first_U15' => 1,
            'last_U15'  => 1,
            'first_U21' => 1,
            'last_U21'  => 1,
            'first_U22' => 1,
            'last_U22'  => 1,
        ]]
    ];

    $docs = $collection->aggregate($pipeline)->toArray();
    return $docs;
}



// --- Main Logic for "today" ---
if ($value == 'today') {
    // Fetch aggregated hourly data for today and yesterday.
    $today_date = $current_date;
    $data_today = fetchHourly($today_date, $tag_values, $numberOfMeters, 1);

    $yesterday_date = date('Y-m-d', strtotime($today_date . ' -1 day'));
    $data_yesterday = fetchHourly($yesterday_date, $tag_values, $numberOfMeters, 1);

    // Create arrays for 24 hours (indices 0 to 23) with default null values.
    $hourlyToday = array_fill(0, 24, null);
    $hourlyYesterday = array_fill(0, 24, null);

    // Arrange today's data by hour.
    foreach ($data_today as $entry) {
        if (isset($entry['_id']['hour'])) {
            $hr = intval($entry['_id']['hour']);
            $hourlyToday[$hr] = $entry;
        }
    }
    // Arrange yesterday's data by hour.
    foreach ($data_yesterday as $entry) {
        if (isset($entry['_id']['hour'])) {
            $hr = intval($entry['_id']['hour']);
            $hourlyYesterday[$hr] = $entry;
        }
    }

    // Define energy mapping for each meter tag (exclude F3 from this mapping).
    $energyMapping = [
        'U_3_EM3_TotalActiveEnergy_kWh'         => ['field' => 'U3',  'factor' => 1],
        'U_4_EM4_TotalActiveEnergy_kWh'         => ['field' => 'U4',  'factor' => 1],
        'U_5_EM5_TotalActiveEnergy_kWh'         => ['field' => 'U5',  'factor' => 1],
        'U_6_EM6_TotalActiveEnergy_kWh'         => ['field' => 'U6',  'factor' => 1],
        'U_7_EM7_ActiveEnergyDelivered_Wh'      => ['field' => 'U7',  'factor' => 1],
        'U_8_EM8_TotalActiveEnergy_kWh'         => ['field' => 'U8',  'factor' => 1],
        'U_9_EM9_ActiveEnergyDelivered_Wh'      => ['field' => 'U9',  'factor' => 1],
        'U_10_EM10_TotalActiveEnergy_kWh'       => ['field' => 'U10', 'factor' => 1],
        'U_15_ActiveEnergy_Delivered_kWh'        => ['field' => 'U15', 'factor' => 1],
        'U_21_ActiveEnergy_Delivered_kWh'        => ['field' => 'U21', 'factor' => 1],
        'U_22_ActiveEnergy_Delivered_kWh'        => ['field' => 'U22', 'factor' => 1],
    ];

    // Define the F3 sensor tag (used for flow calculation)
    $f3Tag = 'F3';  // Adjust this if your aggregation pipeline uses a different alias

    // Prepare an output array
    $output = [];

    // Loop through each hour (0 to 23)
    for ($hr = 0; $hr < 24; $hr++) {
        $formattedHour = sprintf("%02d:00", $hr);

        // ---- Process Today's Data ----
        $entryToday = $hourlyToday[$hr];
        $total_energy_today = 0;
        $f3_diff_today = 0;
        if ($entryToday) {
            foreach ($energyMapping as $map) {
                $field = $map['field'];
                $first = isset($entryToday["first_$field"]) ? floatval($entryToday["first_$field"]) : 0;
                $last  = isset($entryToday["last_$field"]) ? floatval($entryToday["last_$field"]) : 0;
                $diff  = $last - $first;
                if ($diff < 0) {
                    $diff = 0;
                }
                $total_energy_today += $diff;
            }
            // Compute F3 difference for today: (last_F3 - first_F3)
            $firstF3 = isset($entryToday["first_$f3Tag"]) ? floatval($entryToday["first_$f3Tag"]) : 0;
            $lastF3  = isset($entryToday["last_$f3Tag"]) ? floatval($entryToday["last_$f3Tag"]) : 0;
            $f3_diff_today = $lastF3 - $firstF3;
        }
        $flow_today = ($total_energy_today > 0) ? round($f3_diff_today / $total_energy_today, 2) : 0;

        // ---- Process Yesterday's Data ----
        $entryYesterday = $hourlyYesterday[$hr];
        $total_energy_yesterday = 0;
        $f3_diff_yesterday = 0;
        if ($entryYesterday) {
            foreach ($energyMapping as $map) {
                $field = $map['field'];
                $first = isset($entryYesterday["first_$field"]) ? floatval($entryYesterday["first_$field"]) : 0;
                $last  = isset($entryYesterday["last_$field"]) ? floatval($entryYesterday["last_$field"]) : 0;
                $diff  = $last - $first;
                if ($diff < 0) {
                    $diff = 0;
                }
                $total_energy_yesterday += $diff;
            }
            $firstF3 = isset($entryYesterday["first_$f3Tag"]) ? floatval($entryYesterday["first_$f3Tag"]) : 0;
            $lastF3  = isset($entryYesterday["last_$f3Tag"]) ? floatval($entryYesterday["last_$f3Tag"]) : 0;
            $f3_diff_yesterday = $lastF3 - $firstF3;
        }
        $flow_yesterday = ($total_energy_yesterday > 0) ? round($f3_diff_yesterday / $total_energy_yesterday, 2) : 0;

        // Build output for this hour with rounded flow values (2 digits after the decimal point)
        $output[] = [
            'Time'      => $formattedHour,
            'Today'     => $flow_today,
            'Yesterday' => $flow_yesterday
        ];
    }

    // Return the final output as JSON.
    echo json_encode($output, JSON_PRETTY_PRINT);
}







/**
 * fetchDailyData() aggregates data for a given day.
 * It projects the energy tags and F3_MainLine_TotalFlow, groups by the day,
 * and computes:
 *   total_energy = (last_U3 - first_U3)
 *                  + (last_U4 - first_U4)
 *                  + (last_U5 - first_U5)
 *                  + (last_U6 - first_U6)
 *                  + ((last_U7 - first_U7)/1000)
 * and also returns:
 *   first_F3 and last_F3
 *
 * Ensure that your MongoDB documents include these fields.
 */
function fetchDailyData($date, $tag_values, $numberOfMeters)
{
    $db = connectDB();
    $collection = $db->CBL_b;
    $day = intval(date('d', strtotime($date)));
    $month = intval(date('m', strtotime($date)));
    $year = intval(date('Y', strtotime($date)));
    $currentDayUNIX = GetUNIXday($date);

    $pipeline = [
        // Project required fields
        ['$project' => [
            'UNIXtimestamp' => 1,
            $tag_values[0] => 1, // F3_MainLine_TotalFlow
            $tag_values[1] => 1, // U3
            $tag_values[2] => 1, // U4
            $tag_values[3] => 1, // U5
            $tag_values[4] => 1, // U6
            $tag_values[5] => 1, // U7
            $tag_values[6] => 1, // U8
            $tag_values[7] => 1, // U9
            $tag_values[8] => 1, // U10
            $tag_values[9] => 1, // U15
            $tag_values[10] => 1, // U21
            $tag_values[11] => 1, // U22
        ]],
        // Match documents for the given day
        ['$match' => ['UNIXtimestamp' => ['$gte' => $currentDayUNIX]]],
        // Convert timestamp to date
        ['$addFields' => [
            'date' => ['$toDate' => ['$multiply' => ['$UNIXtimestamp', 1000]]]
        ]],
        // Group by day (we assume one document per day)
        ['$group' => [
            '_id' => [
                'year' => ['$year' => '$date'],
                'month' => ['$month' => '$date'],
                'day' => ['$dayOfMonth' => '$date']
            ],
            'first_F3' => ['$first' => '$' . $tag_values[0]],
            'last_F3'  => ['$last'  => '$' . $tag_values[0]],
            'first_U3' => ['$first' => '$' . $tag_values[1]],
            'last_U3'  => ['$last'  => '$' . $tag_values[1]],
            'first_U4' => ['$first' => '$' . $tag_values[2]],
            'last_U4'  => ['$last'  => '$' . $tag_values[2]],
            'first_U5' => ['$first' => '$' . $tag_values[3]],
            'last_U5'  => ['$last'  => '$' . $tag_values[3]],
            'first_U6' => ['$first' => '$' . $tag_values[4]],
            'last_U6'  => ['$last'  => '$' . $tag_values[4]],
            'first_U7' => ['$first' => '$' . $tag_values[5]],
            'last_U7'  => ['$last'  => '$' . $tag_values[5]],
            'first_U8' => ['$first' => '$' . $tag_values[6]],
            'last_U8'  => ['$last'  => '$' . $tag_values[6]],
            'first_U9' => ['$first' => '$' . $tag_values[7]],
            'last_U9'  => ['$last'  => '$' . $tag_values[7]],
            'first_U10' => ['$first' => '$' . $tag_values[8]],
            'last_U10'  => ['$last'  => '$' . $tag_values[8]],
            'first_U15' => ['$first' => '$' . $tag_values[9]],
            'last_U15'  => ['$last'  => '$' . $tag_values[9]],
            'first_U21' => ['$first' => '$' . $tag_values[10]],
            'last_U21'  => ['$last'  => '$' . $tag_values[10]],
            'first_U22' => ['$first' => '$' . $tag_values[11]],
            'last_U22'  => ['$last'  => '$' . $tag_values[11]],
        ]],
        // Project the computed total energy and pass F3 values as is.
        ['$project' => [
            'total_energy' => [
                '$add' => [
                    ['$subtract' => ['$last_U3', '$first_U3']],
                    ['$subtract' => ['$last_U4', '$first_U4']],
                    ['$subtract' => ['$last_U5', '$first_U5']],
                    ['$subtract' => ['$last_U6', '$first_U6']],
                    ['$subtract' => ['$last_U7', '$first_U7']],
                    ['$subtract' => ['$last_U8', '$first_U8']],
                    ['$subtract' => ['$last_U9', '$first_U9']],
                    ['$subtract' => ['$last_U10', '$first_U10']],
                    ['$subtract' => ['$last_U15', '$first_U15']],
                    ['$subtract' => ['$last_U21', '$first_U21']],
                    ['$subtract' => ['$last_U22', '$first_U22']],

                ]
            ],
            'first_F3' => 1,
            'last_F3'  => 1,
            '_id'      => 1
        ]]
    ];

    $docs = $collection->aggregate($pipeline)->toArray();
    return (isset($docs[0])) ? $docs[0] : null;
}

// --- Weekly Logic using fetchDailyData() ---
// We'll compute data for the current week and last week.
// First, determine the Monday for this week.
function fetchMultipleDaysData($dates, $tag_values, $numberOfMeters)
{
    $data_cache = [];

    foreach ($dates as $date) {
        if (!isset($data_cache[$date])) {  // Check if already fetched
            $data_cache[$date] = fetchDailyData($date, $tag_values, $numberOfMeters);
        }
    }

    return $data_cache;
}

// --- Weekly Logic ---
if ($value == 'week') {
    $timestamp = strtotime($current_date);
    $dayOfWeek = date('N', $timestamp); // 1 = Monday, 7 = Sunday

    // Find Monday of this and last week
    $monday_this = strtotime("-" . ($dayOfWeek - 1) . " days", $timestamp);
    $monday_last = strtotime("-7 days", $monday_this);

    $output = [];
    $daysOfWeek = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

    // Store all required dates in one array
    $dates_to_fetch = [];
    for ($i = 0; $i < 7; $i++) {
        $dates_to_fetch[] = date('Y-m-d', $monday_this + ($i * 86400)); // Faster than strtotime("+$i days")
        $dates_to_fetch[] = date('Y-m-d', $monday_last + ($i * 86400));
    }

    // Fetch all required data in one go
    $data_cache = fetchMultipleDaysData($dates_to_fetch, $tag_values, $numberOfMeters);

    // Process Data
    for ($i = 0; $i < 7; $i++) {
        $date_this = date('Y-m-d', $monday_this + ($i * 86400));
        $date_last = date('Y-m-d', $monday_last + ($i * 86400));

        $data_this = $data_cache[$date_this] ?? null;
        $data_last = $data_cache[$date_last] ?? null;

        $flow_this = ($data_this && isset($data_this['total_energy'], $data_this['first_F3'], $data_this['last_F3']))
            ? (($data_this['total_energy'] > 0 && ($diff = $data_this['last_F3'] - $data_this['first_F3']) > 0)
                ? round($diff / $data_this['total_energy'], 2)
                : 0)
            : 0;

        $flow_last = ($data_last && isset($data_last['total_energy'], $data_last['first_F3'], $data_last['last_F3']))
            ? (($data_last['total_energy'] > 0 && ($diff = $data_last['last_F3'] - $data_last['first_F3']) > 0)
                ? round($diff / $data_last['total_energy'], 2)
                : 0)
            : 0;

        $output[] = [
            'Day' => $daysOfWeek[$i],
            'This Week' => $flow_this,
            'Last Week' => $flow_last
        ];
    }

    echo json_encode($output, JSON_PRETTY_PRINT);
}
// fetchDayWise() aggregates data for a single day.
// It projects the F3_MainLine_TotalFlow field and energy tags,
// groups all documents for that day, and returns one document with:
//   - first_F3 and last_F3 (from F3_MainLine_TotalFlow)
//   - first_U3, last_U3, ..., first_U7, last_U7 (for energy tags)
//   - total_energy computed as:
//         (last_U3 - first_U3) + (last_U4 - first_U4) + (last_U5 - first_U5) + (last_U6 - first_U6) + ((last_U7 - first_U7)/1000)
function fetchDayWise($date, $tag_values, $numberOfMeters, $label)
{
    $db = connectDB();
    $collection = $db->CBL_b;
    $dayInt = intval(date('d', strtotime($date)));
    $monthInt = intval(date('m', strtotime($date)));
    $yearInt = intval(date('Y', strtotime($date)));
    $currentDayUNIX = GetUNIXday($date);

    $pipeline = [
        ['$project' => [
            'UNIXtimestamp' => 1,
            $tag_values[0] => 1,  // F3_MainLine_TotalFlow
            $tag_values[1] => 1,  // U3
            $tag_values[2] => 1,  // U4
            $tag_values[3] => 1,  // U5
            $tag_values[4] => 1,  // U6
            $tag_values[5] => 1,  // U7
            $tag_values[6] => 1,  // U8
            $tag_values[7] => 1,  // U9
            $tag_values[8] => 1,  // U10
            $tag_values[9] => 1,  // U15
            $tag_values[10] => 1,  // U21
            $tag_values[11] => 1,  // U22
        ]],
        ['$match' => ['UNIXtimestamp' => ['$gte' => $currentDayUNIX]]],
        ['$addFields' => [
            'date' => ['$toDate' => ['$multiply' => ['$UNIXtimestamp', 1000]]]
        ]],
        // Group by day (all documents for the same day)
        ['$group' => [
            '_id' => [
                'year'  => ['$year' => '$date'],
                'month' => ['$month' => '$date'],
                'day'   => ['$dayOfMonth' => '$date']
            ],
            'first_F3' => ['$first' => '$' . $tag_values[0]],
            'last_F3'  => ['$last'  => '$' . $tag_values[0]],
            'first_U3' => ['$first' => '$' . $tag_values[1]],
            'last_U3'  => ['$last'  => '$' . $tag_values[1]],
            'first_U4' => ['$first' => '$' . $tag_values[2]],
            'last_U4'  => ['$last'  => '$' . $tag_values[2]],
            'first_U5' => ['$first' => '$' . $tag_values[3]],
            'last_U5'  => ['$last'  => '$' . $tag_values[3]],
            'first_U6' => ['$first' => '$' . $tag_values[4]],
            'last_U6'  => ['$last'  => '$' . $tag_values[4]],
            'first_U7' => ['$first' => '$' . $tag_values[5]],
            'last_U7'  => ['$last'  => '$' . $tag_values[5]],
            'first_U8' => ['$first' => '$' . $tag_values[6]],
            'last_U8'  => ['$last'  => '$' . $tag_values[6]],
            'first_U9' => ['$first' => '$' . $tag_values[7]],
            'last_U9'  => ['$last'  => '$' . $tag_values[7]],
            'first_U10' => ['$first' => '$' . $tag_values[8]],
            'last_U10'  => ['$last'  => '$' . $tag_values[8]],
            'first_U15' => ['$first' => '$' . $tag_values[9]],
            'last_U15'  => ['$last'  => '$' . $tag_values[9]],
            'first_U21' => ['$first' => '$' . $tag_values[10]],
            'last_U21'  => ['$last'  => '$' . $tag_values[10]],
            'first_U22' => ['$first' => '$' . $tag_values[11]],
            'last_U22'  => ['$last'  => '$' . $tag_values[11]],
        ]],
        ['$project' => [
            'total_energy' => [
                '$add' => [
                    ['$subtract' => ['$last_U3', '$first_U3']],
                    ['$subtract' => ['$last_U4', '$first_U4']],
                    ['$subtract' => ['$last_U5', '$first_U5']],
                    ['$subtract' => ['$last_U6', '$first_U6']],
                    ['$subtract' => ['$last_U7', '$first_U7']],
                    ['$subtract' => ['$last_U8', '$first_U8']],
                    ['$subtract' => ['$last_U9', '$first_U9']],
                    ['$subtract' => ['$last_U10', '$first_U10']],
                    ['$subtract' => ['$last_U15', '$first_U15']],
                    ['$subtract' => ['$last_U21', '$first_U21']],
                    ['$subtract' => ['$last_U22', '$first_U22']],

                ]
            ],
            'first_F3' => 1,
            'last_F3' => 1,
            '_id' => 1
        ]]
    ];

    $docs = $collection->aggregate($pipeline)->toArray();
    return (isset($docs[0])) ? $docs[0] : null;
}

// --- Month Branch Logic ---
// For each day in the month, compute daily flow = (last_F3 - first_F3)/total_energy.
// Then, group the days into weeks (simple grouping: days 1-7 => Week1, 8-14 => Week2, etc.)
// Finally, output an array where each week shows the sum (or average) of daily flows.
if ($value == 'month') {
    // For This Month:
    $thisMonthStart = date('Y-m-01', strtotime($current_date));
    $thisMonthDays = cal_days_in_month(CAL_GREGORIAN, date('n', strtotime($current_date)), date('Y', strtotime($current_date)));

    $dailyFlowThis = []; // Store daily flow values
    for ($d = 1; $d <= $thisMonthDays; $d++) {
        $date = date('Y-m-d', strtotime($thisMonthStart . " +" . ($d - 1) . " days"));
        $dailyData = fetchDayWise($date, $tag_values, $numberOfMeters, 'ThisMonth');
        if ($dailyData !== null) {
            $energy = $dailyData['total_energy'] ?? 0;
            $f3_diff = $dailyData['last_F3'] - $dailyData['first_F3'];
            $daily_flow = ($energy > 0 && $f3_diff > 0) ? $f3_diff / $energy : 0;
        } else {
            $daily_flow = 0;
        }
        $dailyFlowThis[$d] = $daily_flow;
    }

    // For Last Month:
    $lastMonthStart = date('Y-m-01', strtotime($current_date . ' -1 month'));
    $lastMonthDays = cal_days_in_month(CAL_GREGORIAN, date('n', strtotime($current_date . ' -1 month')), date('Y', strtotime($current_date . ' -1 month')));

    $dailyFlowLast = [];
    for ($d = 1; $d <= $lastMonthDays; $d++) {
        $date = date('Y-m-d', strtotime($lastMonthStart . " +" . ($d - 1) . " days"));
        $dailyData = fetchDayWise($date, $tag_values, $numberOfMeters, 'LastMonth');
        if ($dailyData !== null) {
            $energy = $dailyData['total_energy'] ?? 0;
            $f3_diff = $dailyData['last_F3'] - $dailyData['first_F3'];
            $daily_flow = ($energy > 0 && $f3_diff > 0) ? $f3_diff / $energy : 0;
        } else {
            $daily_flow = 0;
        }
        $dailyFlowLast[$d] = $daily_flow;
    }

    // **Group daily flows into weekly averages**
    $weeklyFlowThis = [];
    $weeklyFlowLast = [];

    // For This Month:
    for ($d = 1; $d <= $thisMonthDays; $d++) {
        $weekNumber = ceil($d / 7);
        if (!isset($weeklyFlowThis[$weekNumber])) {
            $weeklyFlowThis[$weekNumber] = ['sum' => 0, 'count' => 0];
        }
        $weeklyFlowThis[$weekNumber]['sum'] += $dailyFlowThis[$d];
        $weeklyFlowThis[$weekNumber]['count']++;
    }

    // For Last Month:
    for ($d = 1; $d <= $lastMonthDays; $d++) {
        $weekNumber = ceil($d / 7);
        if (!isset($weeklyFlowLast[$weekNumber])) {
            $weeklyFlowLast[$weekNumber] = ['sum' => 0, 'count' => 0];
        }
        $weeklyFlowLast[$weekNumber]['sum'] += $dailyFlowLast[$d];
        $weeklyFlowLast[$weekNumber]['count']++;
    }

    // **Calculate the weekly averages**
    $weeklyAverageThis = [];
    $weeklyAverageLast = [];

    foreach ($weeklyFlowThis as $week => $data) {
        $weeklyAverageThis[$week] = ($data['count'] > 0) ? $data['sum'] / $data['count'] : 0;
    }

    foreach ($weeklyFlowLast as $week => $data) {
        $weeklyAverageLast[$week] = ($data['count'] > 0) ? $data['sum'] / $data['count'] : 0;
    }

    // **Prepare output: list all weeks with average flow values**
    $maxWeeks = max(count($weeklyAverageThis), count($weeklyAverageLast));
    $output = [];
    for ($w = 1; $w <= $maxWeeks; $w++) {
        $output[] = [
            'Weeks' => 'Week' . $w,
            'Last Month' => isset($weeklyAverageLast[$w]) ? round($weeklyAverageLast[$w], 2) : 0,
            'This Month' => isset($weeklyAverageThis[$w]) ? round($weeklyAverageThis[$w], 2) : 0
        ];
    }

    echo json_encode($output, JSON_PRETTY_PRINT);
}

/**
 * fetchYearlyFlow() aggregates data from MongoDB by month (for current and previous year).
 * It projects the F3_MainLine_TotalFlow field and all energy tags,
 * then groups by year and month, using $first and $last to capture:
 *   - first_F3 and last_F3 (from F3_MainLine_TotalFlow)
 *   - first_U3, last_U3, ..., first_U7, last_U7 (from energy tags)
 *
 * It then computes:
 *   f3_diff = (last_F3 - first_F3)
 *   energy_diff = (last_U3 - first_U3)
 *                   + (last_U4 - first_U4)
 *                   + (last_U5 - first_U5)
 *                   + (last_U6 - first_U6)
 *                   + ((last_U7 - first_U7)/1000)
 *
 * Finally, it computes flow = f3_diff / energy_diff (if both differences > 0; else 0).
 */
function fetchMonthlyFirstDocument($month, $tag_values, $numberOfMeters)
{
    $db = connectDB();
    $collection = $db->CBL_b;

    // $month in "YYYY-MM" format, e.g. "2024-12"
    $start = strtotime($month . "-01");
    $end = strtotime(date("Y-m-t", $start));

    $pipeline = [
        ['$match' => ['UNIXtimestamp' => ['$gte' => $start, '$lte' => $end]]],
        ['$sort' => ['UNIXtimestamp' => 1]],
        ['$limit' => 1],
        ['$project' => [
            'F3'  => '$' . $tag_values[0],
            'U3'  => '$' . $tag_values[1],
            'U4'  => '$' . $tag_values[2],
            'U5'  => '$' . $tag_values[3],
            'U6'  => '$' . $tag_values[4],
            'U7'  => '$' . $tag_values[5],
            'U8'  => '$' . $tag_values[6],
            'U9'  => '$' . $tag_values[7],
            'U10' => '$' . $tag_values[8],
            'U15' => '$' . $tag_values[9],
            'U21' => '$' . $tag_values[10],
            'U22' => '$' . $tag_values[11],
        ]]
    ];

    $docs = $collection->aggregate($pipeline)->toArray();
    return !empty($docs) ? (array)$docs[0] : [];
}

// Function to fetch the last document of the month
function fetchMonthlyLastDocument($month, $tag_values, $numberOfMeters)
{
    $db = connectDB();
    $collection = $db->CBL_b;

    $start = strtotime($month . "-01");
    $end = strtotime(date("Y-m-t", $start));

    $pipeline = [
        ['$match' => ['UNIXtimestamp' => ['$gte' => $start, '$lte' => $end]]],
        ['$sort' => ['UNIXtimestamp' => -1]],
        ['$limit' => 1],
        ['$project' => [
            'F3'  => '$' . $tag_values[0],
            'U3'  => '$' . $tag_values[1],
            'U4'  => '$' . $tag_values[2],
            'U5'  => '$' . $tag_values[3],
            'U6'  => '$' . $tag_values[4],
            'U7'  => '$' . $tag_values[5],
            'U8'  => '$' . $tag_values[6],
            'U9'  => '$' . $tag_values[7],
            'U10' => '$' . $tag_values[8],
            'U15' => '$' . $tag_values[9],
            'U21' => '$' . $tag_values[10],
            'U22' => '$' . $tag_values[11],
        ]]
    ];

    $docs = $collection->aggregate($pipeline)->toArray();
    return !empty($docs) ? (array)$docs[0] : [];
}

// ------------------- Main Code for Yearly Flow Calculation -------------------
if ($value == 'year') {
    $currentYear = intval(date('Y'));
    $previousYear = $currentYear - 1;

    // Prepare an array for 12 months with default values (including the Month field)
    $yearData = [];
    for ($m = 1; $m <= 12; $m++) {
        $yearData[$m] = [
            'Month' => date('M', mktime(0, 0, 0, $m, 1)),
            'Current Year' => 0,
            'Previous Year' => 0
        ];
    }

    // Define energy mapping for meter tags (F3 is calculated separately)
    $energyMapping = [
        'U_3_EM3_TotalActiveEnergy_kWh'         => ['field' => 'U3', 'factor' => 1],
        'U_4_EM4_TotalActiveEnergy_kWh'         => ['field' => 'U4', 'factor' => 1],
        'U_5_EM5_TotalActiveEnergy_kWh'         => ['field' => 'U5', 'factor' => 1],
        'U_6_EM6_TotalActiveEnergy_kWh'         => ['field' => 'U6', 'factor' => 1],
        'U_7_EM7_ActiveEnergyDelivered_Wh'      => ['field' => 'U7', 'factor' => 1],
        'U_8_EM8_TotalActiveEnergy_kWh'         => ['field' => 'U8', 'factor' => 1],
        'U_9_EM9_ActiveEnergyDelivered_Wh'      => ['field' => 'U9', 'factor' => 1],
        'U_10_EM10_TotalActiveEnergy_kWh'       => ['field' => 'U10', 'factor' => 1],
        'U_15_ActiveEnergy_Delivered_kWh'        => ['field' => 'U15', 'factor' => 1],
        'U_21_ActiveEnergy_Delivered_kWh'        => ['field' => 'U21', 'factor' => 1],
        'U_22_ActiveEnergy_Delivered_kWh'        => ['field' => 'U22', 'factor' => 1],
    ];

    // F3 sensor tag (as projected in the aggregation pipeline)
    $f3Tag = 'F3';

    // Loop through each month (1 to 12)
    for ($m = 1; $m <= 12; $m++) {
        // Build month string for current and previous year (format: "YYYY-MM")
        $monthStrCurrent = sprintf("%d-%02d", $currentYear, $m);
        $monthStrPrevious = sprintf("%d-%02d", $previousYear, $m);

        // Fetch documents for the current month for both years.
        $firstDocCurrent = fetchMonthlyFirstDocument($monthStrCurrent, $tag_values, $numberOfMeters);
        $lastDocCurrent  = fetchMonthlyLastDocument($monthStrCurrent, $tag_values, $numberOfMeters);
        $flowCurrent = 0;
        if (!empty($firstDocCurrent) && !empty($lastDocCurrent)) {
            $totalEnergyCurrent = 0;
            foreach ($energyMapping as $map) {
                $field = $map['field'];
                $firstValue = isset($firstDocCurrent[$field]) ? floatval($firstDocCurrent[$field]) : 0;
                $lastValue  = isset($lastDocCurrent[$field]) ? floatval($lastDocCurrent[$field]) : 0;
                $diff = $lastValue - $firstValue;
                if ($diff < 0) {
                    $diff = 0;
                }
                $totalEnergyCurrent += $diff;
            }
            $f3_firstCurrent = isset($firstDocCurrent[$f3Tag]) ? floatval($firstDocCurrent[$f3Tag]) : 0;
            $f3_lastCurrent  = isset($lastDocCurrent[$f3Tag]) ? floatval($lastDocCurrent[$f3Tag]) : 0;
            $f3_diffCurrent  = $f3_lastCurrent - $f3_firstCurrent;
            $flowCurrent = ($totalEnergyCurrent > 0) ? round($f3_diffCurrent / $totalEnergyCurrent, 2) : 0;
        }

        // For previous year:
        $firstDocPrevious = fetchMonthlyFirstDocument($monthStrPrevious, $tag_values, $numberOfMeters);
        $lastDocPrevious  = fetchMonthlyLastDocument($monthStrPrevious, $tag_values, $numberOfMeters);
        $flowPrevious = 0;
        if (!empty($firstDocPrevious) && !empty($lastDocPrevious)) {
            $totalEnergyPrevious = 0;
            foreach ($energyMapping as $map) {
                $field = $map['field'];
                $firstValue = isset($firstDocPrevious[$field]) ? floatval($firstDocPrevious[$field]) : 0;
                $lastValue  = isset($lastDocPrevious[$field]) ? floatval($lastDocPrevious[$field]) : 0;
                $diff = $lastValue - $firstValue;
                if ($diff < 0) {
                    $diff = 0;
                }
                $totalEnergyPrevious += $diff;
            }
            $f3_firstPrevious = isset($firstDocPrevious[$f3Tag]) ? floatval($firstDocPrevious[$f3Tag]) : 0;
            $f3_lastPrevious  = isset($lastDocPrevious[$f3Tag]) ? floatval($lastDocPrevious[$f3Tag]) : 0;
            $f3_diffPrevious  = $f3_lastPrevious - $f3_firstPrevious;
            $flowPrevious = ($totalEnergyPrevious > 0) ? round($f3_diffPrevious / $totalEnergyPrevious, 2) : 0;
        }

        // Store the flow values and Month abbreviation for the month.
        $yearData[$m]['Month'] = date('M', mktime(0, 0, 0, $m, 1));
        $yearData[$m]['Current Year'] = $flowCurrent;
        $yearData[$m]['Previous Year'] = $flowPrevious;
    }

    // Format flow values to one decimal place.
    foreach ($yearData as $m => $data) {
        $yearData[$m]['Current Year'] = number_format($data['Current Year'], 1);
        $yearData[$m]['Previous Year'] = number_format($data['Previous Year'], 1);
    }

    header('Content-Type: application/json');
    echo json_encode(array_values($yearData), JSON_PRETTY_PRINT);
}
