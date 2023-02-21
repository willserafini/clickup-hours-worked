<?php
require('helper.php');
require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$teamId = $_ENV['TEAM_ID'];
$token = $_ENV['TOKEN'];
//$myUserId = $_ENV['USER_ID'];

//needs to get the monthly hours
$query = [
    'start_date' => strtotime("2023-01-01 00:00:00") * 1000,
    'end_date' => strtotime("2023-01-31 23:59:59") * 1000,
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
    $dateOfTrack = date('d/m/Y', $data->start / 1000);
    if (!array_key_exists($dateOfTrack, $hoursSum)) {
        $hoursSum[$dateOfTrack]['duration'] = 0;
        $hoursSum[$dateOfTrack]['detailed'] = [];
    }

    $hoursSum[$dateOfTrack]['duration'] += $data->duration;
    $hoursSum[$dateOfTrack]['detailed'][] = [
        'name' => $data->task->name,
        'hours' => getHoursFormatted(milisecondsToHours($data->duration)),
        'task_url' => $data->task_url
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

fputcsv($fd, ['Date', 'Hours', 'Task', 'Task url']);

$sumHoursWorked = 0;
foreach ($hoursSum as $date => $row) {
    $hoursWorked = milisecondsToHours($row['duration']); //miliseconds to hours
    $sumHoursWorked += $hoursWorked;

    fputcsv($f, [$date, getHoursFormatted($hoursWorked)]);
    foreach ($row['detailed'] as $taskDetailed) {
        fputcsv($fd, [$date, $taskDetailed['hours'], $taskDetailed['name'], $taskDetailed['task_url']]);
    }
}

fputcsv($f, ['Total', getHoursFormatted($sumHoursWorked)]);

fclose($f);
fclose($fd);

echo "done...\n";