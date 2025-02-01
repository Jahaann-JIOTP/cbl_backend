<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
date_default_timezone_set("Asia/Karachi");

// Database connection
$con = mysqli_connect("15.206.128.214", "jahaann", "Jahaann#321", "cbl_alarms");

if (!$con) {
    echo json_encode(["error" => "Database connection failed: " . mysqli_connect_error()]);
    exit();
}

// Get filter parameter (default to 'today')
$filter = $_GET['filter'] ?? 'today';

// Calculate start and end dates based on the filter
$today = date('Y-m-d');
switch (strtolower($filter)) {
    case 'today':
        $start_date = "$today 00:00:00";
        $end_date = "$today 23:59:59";
        break;
    case 'last7days':
        $start_date = date('Y-m-d 00:00:00', strtotime('-7 days'));
        $end_date = date('Y-m-d 23:59:59', strtotime('-1 day')); // Exclude today
        break;
    case 'last15days':
        $start_date = date('Y-m-d 00:00:00', strtotime('-15 days'));
        $end_date = date('Y-m-d 23:59:59', strtotime('-1 day')); // Exclude today
        break;
    case 'last30days':
        $start_date = date('Y-m-d 00:00:00', strtotime('-30 days'));
        $end_date = date('Y-m-d 23:59:59', strtotime('-1 day')); // Exclude today
        break;
    default:
        echo json_encode(["error" => "Invalid filter provided."]);
        exit();
}

// Step 1: Ensure $meter_id and $status are assigned before use
// You may need to extract these values from an appropriate source, for example:
$meter_id = $_GET['meter_id'] ?? ''; // Assuming meter_id is passed as a query parameter
$status = $_GET['status'] ?? ''; // Assuming status is passed as a query parameter

// Step 2: Update `end_time` for previous alarms of the same meter and status
$update_end_time_query = "
    UPDATE recentalarms ra
    LEFT JOIN alarms a 
    ON ra.meter = a.Source AND ra.start_time = a.Time
    SET 
        ra.end_time = a.end_time,
        ra.total_duration = CASE 
            WHEN a.end_time IS NOT NULL THEN TIMESTAMPDIFF(SECOND, ra.start_time, a.end_time)
            ELSE TIMESTAMPDIFF(SECOND, ra.start_time, NOW())
        END
    WHERE ra.end_time IS NULL OR ra.total_duration = 0;
";
$update_result = mysqli_query($con, $update_end_time_query);

if (!$update_result) {
    error_log("Error updating end_time and total_duration: " . mysqli_error($con));
}


$sync_query = "
    INSERT INTO recentalarms (
        meter, option_selected, db_value, url_value, status, start_time, end_time, created_at, total_duration
    )
    SELECT 
        Source AS meter, 
        Status AS option_selected, 
        db_value, 
        url_value, 
        status1 AS status,
        Time AS start_time,
        end_time, 
        NOW() AS created_at,
        CASE 
            WHEN end_time IS NULL THEN TIMESTAMPDIFF(SECOND, Time, NOW())
            ELSE TIMESTAMPDIFF(SECOND, Time, end_time)
        END AS total_duration
    FROM alarms
    WHERE Time BETWEEN '$start_date' AND '$end_date'
    AND NOT EXISTS (
        SELECT 1 
        FROM recentalarms 
        WHERE alarms.Source = recentalarms.meter 
        AND alarms.Time = recentalarms.start_time
    )
";
mysqli_query($con, $sync_query);


$recentalarms_query = "
    SELECT 
        ra.id,
        ra.meter,
        ra.option_selected,
        ra.db_value, 
        ra.url_value, 
        ra.status, 
        ra.start_time, 
        CASE 
            WHEN ra.end_time IS NULL THEN '....' 
            ELSE DATE_FORMAT(ra.end_time, '%Y-%m-%d %H:%i:%s') 
        END AS end_time, 
        ra.total_duration,
        ra.created_at
    FROM recentalarms ra
    WHERE ra.start_time BETWEEN '$start_date' AND '$end_date'
    ORDER BY ra.start_time DESC;
";
$recentalarms_result = mysqli_query($con, $recentalarms_query);

$recentalarms = [];
while ($row = mysqli_fetch_assoc($recentalarms_result)) {
    // Format total duration into HH:MM:SS
    if (!empty($row['total_duration'])) {
        $row['total_duration'] = sprintf(
            "%02d:%02d:%02d", 
            floor($row['total_duration'] / 3600), 
            floor(($row['total_duration'] % 3600) / 60), 
            $row['total_duration'] % 60
        );
    } else {
        $row['total_duration'] = "00:00:00";
    }

    $recentalarms[] = $row;
}

echo json_encode(["recentalarms" => $recentalarms], JSON_PRETTY_PRINT);


?>