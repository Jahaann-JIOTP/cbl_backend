<?php
require 'vendor/autoload.php';
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

function connectDB()
{
    try {
        $client = new MongoDB\Client("mongodb://admin:cisco123@13.234.241.103:27017/?authSource=iotdb&readPreference=primary&ssl=false");
        return $client->iotdb;
    } catch (Exception $e) {
        echo json_encode(["error" => "Failed to connect to MongoDB: " . $e->getMessage()]);
        exit;
    }
}

$db = connectDB();
$collection = $db->CBL_b;
$collection->createIndex(['timestamp' => 1]);

function getDateRanges($timePeriod)
{
    $now = new DateTime('now', new DateTimeZone('Asia/Karachi'));
    $thisWeekStart = (clone $now)->modify('monday this week');
    $lastWeekStart = (clone $thisWeekStart)->modify('-7 days');
    $lastWeekEnd = (clone $lastWeekStart)->modify('+6 days');
    $thisMonthStart = (clone $now)->modify('first day of this month');
    $lastMonthStart = (clone $thisMonthStart)->modify('-1 month');
    $lastMonthEnd = (clone $lastMonthStart)->modify('last day of this month');
    $today = $now->format('Y-m-d');
    $yesterday = (clone $now)->modify('-1 day')->format('Y-m-d');

    switch ($timePeriod) {
        case "This Week over Last Week":
            return [
                ['start' => $lastWeekStart->format('Y-m-d'), 'end' => $lastWeekEnd->format('Y-m-d')],
                ['start' => $thisWeekStart->format('Y-m-d'), 'end' => $now->format('Y-m-d')]
            ];
        case "This Month over Last Month":
            return [
                ['start' => $lastMonthStart->format('Y-m-d'), 'end' => $lastMonthEnd->format('Y-m-d')],
                ['start' => $thisMonthStart->format('Y-m-d'), 'end' => $now->format('Y-m-d')]
            ];
        case "Today over Yesterday":
            return [
                ['start' => $yesterday, 'end' => $yesterday],
                ['start' => $today, 'end' => $today]
            ];
        default:
            return null;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['time_period'])) {
    $timePeriod = $_GET['time_period'];
    $meterIds = isset($_GET['meterId']) ? explode(',', $_GET['meterId']) : [];
    $suffixes = isset($_GET['suffixes']) ? explode(',', $_GET['suffixes']) : [];

    if (empty($meterIds) || empty($suffixes)) {
        echo json_encode(["error" => "Please provide valid meterIds and suffixes parameters."]);
        exit;
    }

    $dateRanges = getDateRanges($timePeriod);
    if (!$dateRanges) {
        echo json_encode(["error" => "Invalid time_period parameter."]);
        exit;
    }

    try {
        // Initialize data containers
        $firstValuesByDay = [];
        $lastValuesByDay = [];
        $latestAvailableValues = [];
        $hourlyConsumption = [];
        $flowrateSums = [];

        // Process data for each date range
        foreach ($dateRanges as $index => $range) {
            $startDate = $range['start'] . 'T00:00:00.000+05:00';
            $endDate = $range['end'] . 'T23:59:59.999+05:00';

            $projection = ['timestamp' => 1];
            foreach ($meterIds as $meterId) {
                foreach ($suffixes as $suffix) {
                    $projection["{$meterId}_{$suffix}"] = 1;
                }
            }

            $pipeline = [
                ['$match' => ['timestamp' => ['$gte' => $startDate, '$lte' => $endDate]]],
                ['$project' => $projection],
                ['$sort' => ['timestamp' => 1]]
            ];

            $data = $collection->aggregate($pipeline)->toArray();

            foreach ($data as $document) {
                $day = (new DateTime($document['timestamp']))->format('Y-m-d');
                $hour = (new DateTime($document['timestamp']))->format('H:00');
                foreach ($meterIds as $meterId) {
                    foreach ($suffixes as $suffix) {
                        $key = "{$meterId}_{$suffix}";
                        if (isset($document[$key])) {
                            if ($suffix === "Flowrate") {
                                // Summation logic for Flowrate
                                $flowrateSums[$day] = ($flowrateSums[$day] ?? 0) + $document[$key];
                            } else {
                                if (!isset($firstValuesByDay[$day][$key])) {
                                    $firstValuesByDay[$day][$key] = $document[$key];
                                }
                                $lastValuesByDay[$day][$key] = $document[$key];
                                $latestAvailableValues[$key] = $document[$key];

                                // Track hourly consumption
                                if (!isset($hourlyConsumption[$day][$hour][$key])) {
                                    $hourlyConsumption[$day][$hour][$key] = [
                                        'first' => $document[$key],
                                        'last' => $document[$key]
                                    ];
                                } else {
                                    $hourlyConsumption[$day][$hour][$key]['last'] = $document[$key];
                                }
                            }
                        }
                    }
                }
            }
        }

        if ($timePeriod === "This Week over Last Week") {
            $result = [];
            $daysOfWeek = ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"];

            foreach ($dateRanges as $index => $range) {
                $weeklyData = [];
                foreach ($daysOfWeek as $dayName) {
                    $weeklyData[$dayName] = [
                        'day' => $dayName,
                        'value' => 0 // Default value
                    ];
                }

                foreach (
                    new DatePeriod(
                        new DateTime($range['start']),
                        new DateInterval('P1D'),
                        (new DateTime($range['end']))->modify('+1 day')
                    ) as $date
                ) {
                    $day = $date->format('Y-m-d');
                    $dayName = $daysOfWeek[(int)$date->format('N') - 1];

                    if (in_array("Flowrate", $suffixes)) {
                        $weeklyData[$dayName]['value'] = $flowrateSums[$day] ?? 0;
                    } else {
                        $consumption = 0;
                        foreach ($firstValuesByDay[$day] ?? [] as $key => $firstValue) {
                            $lastValue = $lastValuesByDay[$day][$key] ?? null;
                            if ($lastValue !== null) {
                                $consumption += $lastValue - $firstValue;
                            }
                        }
                        $weeklyData[$dayName]['value'] = $consumption > 0 ? $consumption : 0;
                    }
                }

                $result[$index === 0 ? "Last Week" : "This Week"] = array_values($weeklyData);
            }
        } elseif ($timePeriod === "This Month over Last Month") {
            if (!function_exists('groupIntoWeeks')) {
                function groupIntoWeeks($firstValuesByDay, $lastValuesByDay, $monthStart, $monthEnd, $latestAvailableValues, $flowrateSums, $suffixes, $includeCurrentWeek = false)
                {
                    $weeks = [];
                    $dayCounter = 0;
                    $weekData = [];

                    foreach (new DatePeriod($monthStart, new DateInterval('P1D'), $monthEnd->modify('+1 day')) as $date) {
                        $day = $date->format('Y-m-d');
                        $dayCounter++;
                        $weekIndex = intdiv($dayCounter - 1, 7); // Divide into weeks (0-indexed)

                        if (!isset($weekData[$weekIndex])) {
                            $weekData[$weekIndex] = []; // Initialize week data
                        }

                        if (in_array("Flowrate", $suffixes)) {
                            $weekData[$weekIndex]['Flowrate'] = ($weekData[$weekIndex]['Flowrate'] ?? 0) + ($flowrateSums[$day] ?? 0);
                        } else {
                            foreach ($firstValuesByDay[$day] ?? [] as $key => $firstValue) {
                                $lastValue = $lastValuesByDay[$day][$key] ?? $latestAvailableValues[$key] ?? null;
                                if ($lastValue !== null) {
                                    $consumption = $lastValue - $firstValue;
                                    $weekData[$weekIndex][$key] = ($weekData[$weekIndex][$key] ?? 0) + $consumption;
                                }
                            }
                        }
                    }

                    for ($i = 0; $i < 5; $i++) {
                        $total = array_sum($weekData[$i] ?? []);
                        $weeks[] = [
                            'week' => "Week " . ($i + 1),
                            'value' => $total > 0 ? $total : 0
                        ];
                    }

                    return $weeks;
                }
            }

            $lastMonthStart = new DateTime($dateRanges[0]['start']);
            $lastMonthEnd = new DateTime($dateRanges[0]['end']);
            $thisMonthStart = new DateTime($dateRanges[1]['start']);
            $thisMonthEnd = new DateTime($dateRanges[1]['end']);

            $result = [
                "Last Month Weeks" => groupIntoWeeks(
                    $firstValuesByDay,
                    $lastValuesByDay,
                    $lastMonthStart,
                    $lastMonthEnd,
                    $latestAvailableValues,
                    $flowrateSums,
                    $suffixes
                ),
                "This Month Weeks" => groupIntoWeeks(
                    $firstValuesByDay,
                    $lastValuesByDay,
                    $thisMonthStart,
                    $thisMonthEnd,
                    $latestAvailableValues,
                    $flowrateSums,
                    $suffixes,
                    true
                )
            ];
        } elseif ($timePeriod === "Today over Yesterday") {
            $today = $dateRanges[1]['start'];
            $yesterday = $dateRanges[0]['start'];

            $result = [
                "Yesterday" => in_array("Flowrate", $suffixes) ? ($flowrateSums[$yesterday] ?? 0) : [],
                "Today" => in_array("Flowrate", $suffixes) ? ($flowrateSums[$today] ?? 0) : []
            ];

            if (!in_array("Flowrate", $suffixes)) {
                $allHours = array_map(function ($hour) {
                    return str_pad($hour, 2, "0", STR_PAD_LEFT) . ":00";
                }, range(0, 23));

                foreach ([$yesterday, $today] as $day) {
                    foreach ($allHours as $hour) {
                        foreach ($hourlyConsumption[$day][$hour] ?? [] as $key => $value) {
                            $consumption = $value['last'] - $value['first'];
                            if ($day === $yesterday) {
                                $result["Yesterday"][$hour][$key] = $consumption;
                            } elseif ($day === $today) {
                                $result["Today"][$hour][$key] = $consumption;
                            }
                        }

                        if (!isset($result["Yesterday"][$hour])) {
                            $result["Yesterday"][$hour] = [];
                            foreach ($meterIds as $meterId) {
                                foreach ($suffixes as $suffix) {
                                    $result["Yesterday"][$hour]["{$meterId}_{$suffix}"] = 0;
                                }
                            }
                        }
                        if (!isset($result["Today"][$hour])) {
                            $result["Today"][$hour] = [];
                            foreach ($meterIds as $meterId) {
                                foreach ($suffixes as $suffix) {
                                    $result["Today"][$hour]["{$meterId}_{$suffix}"] = 0;
                                }
                            }
                        }
                    }
                }
            }
        }

        echo json_encode([$result]);
    } catch (Exception $e) {
        echo json_encode(["error" => "Error fetching data: " . $e->getMessage()]);
    }
} else {
    echo json_encode(["error" => "Invalid request. Please provide time_period parameter."]);
}
