<?php
require('helper.php');
require_once __DIR__ . '/vendor/autoload.php';

error_reporting(E_ALL & ~E_WARNING & ~E_DEPRECATED);

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$teamId = $_ENV['TEAM_ID'];
$token = $_ENV['TOKEN'];
//$myUserId = $_ENV['USER_ID'];

date_default_timezone_set('America/Sao_Paulo');

// Adjust date calculations to use the specified timezone
$start_date = new DateTime('first day of this month 00:00:00');
$end_date = new DateTime('last day of this month 23:59:59');

//var_dump($start_date, $end_date);

$query = [
    'start_date' => $start_date->getTimestamp() * 1000,
    'end_date' => $end_date->getTimestamp() * 1000,
];

$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_HTTPHEADER => [
      "Authorization: $token",
      "Content-Type: application/json"
    ],
    CURLOPT_URL => "https://api.clickup.com/api/v2/team/$teamId/time_entries?" . http_build_query($query),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST => "GET",
]);
  
$response = curl_exec($curl);
$error = curl_error($curl);

curl_close($curl);

if ($error) {
    echo "cURL Error #:" . $error;
    exit();
}

$response = json_decode($response);
/*var_dump($response->data);
exit;*/

$hoursSum = [];
foreach ($response->data as $data) {
    $dateOfTrack = (new DateTime())->setTimestamp($data->start / 1000)->format('d/m/Y');
    if (!array_key_exists($dateOfTrack, $hoursSum)) {
        $hoursSum[$dateOfTrack]['duration'] = 0;
        $hoursSum[$dateOfTrack]['detailed'] = [];
    }

    $hoursSum[$dateOfTrack]['duration'] += $data->duration;
    $hoursSum[$dateOfTrack]['detailed'][] = [
        'name' => $data->task->name,
        'hours' => getHoursFormatted(milisecondsToHours($data->duration)),
        'task_url' => $data->task_url,
        'startDateTime' => (new DateTime())->setTimestamp($data->start / 1000)->format('Y-m-d H:i:s'),
        'endDateTime' => (new DateTime())->setTimestamp($data->end / 1000)->format('Y-m-d H:i:s')
    ];
}

$month = date('m-Y');
$filename = "hours/hours-$month.csv";
$filenameDetailed = "hours/hours-$month-detailed.csv";

// open csv file for writing
$f = fopen($filename, 'w');
$fd = fopen($filenameDetailed, 'w');

if ($f === false || $fd === false) {
	die('Error opening the file ' . $filename);
}

fputcsv($f, ['Date', 'Hours']);

fputcsv($fd, ['Date', 'Hours', 'Task', 'Task url', 'Start DateTime', 'End DateTime']);

$sumHoursWorked = 0;
foreach ($hoursSum as $date => $row) {
    $hoursWorked = milisecondsToHours($row['duration']); //miliseconds to hours
    $sumHoursWorked += $hoursWorked;

    fputcsv($f, [$date, getHoursFormatted($hoursWorked)]);
    foreach ($row['detailed'] as $taskDetailed) {
        fputcsv($fd, [$date, $taskDetailed['hours'], $taskDetailed['name'], $taskDetailed['task_url'], $taskDetailed['startDateTime'], $taskDetailed['endDateTime']]);
    }
}

fputcsv($f, ['Total', getHoursFormatted($sumHoursWorked)]);

fclose($f);
fclose($fd);

echo "done...\n";