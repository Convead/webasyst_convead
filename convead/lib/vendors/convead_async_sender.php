<?php
/**
 * Скрипт для асинхронной отправки данных в Convead
 * Запускается в фоновом режиме
 */

if (!isset($argv[1])) {
    exit("Usage: php convead_async_sender.php <data_file>\n");
}

$data_file = $argv[1];

if (!file_exists($data_file)) {
    exit("Data file not found: {$data_file}\n");
}

$request_data = json_decode(file_get_contents($data_file), true);
unlink($data_file); // Удаляем временный файл

if (!$request_data) {
    exit("Invalid data format\n");
}

$url = $request_data['url'];
$post = $request_data['post'];
$headers = $request_data['headers'];
$method = $request_data['method'];
$timeout = $request_data['timeout'];
$connect_timeout = $request_data['connect_timeout'];

// Отправляем запрос
$curl = curl_init($url);
curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $connect_timeout);
curl_setopt($curl, CURLOPT_FAILONERROR, true);
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

if ($post) {
    if ($method == "POST") curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
}

$full_headers = array("Accept:application/json, text/javascript, */*; q=0.01");
$full_headers = array_unique(array_merge($full_headers, $headers));
curl_setopt($curl, CURLOPT_HTTPHEADER, $full_headers);

curl_exec($curl);
curl_close($curl);

exit(0);
