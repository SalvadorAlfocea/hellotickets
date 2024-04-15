<?php

class HelloTicketsAPI
{
    private string $base_url = 'https://api-live.hellotickets.com/v1/';
    private string $public_key = 'pub-5e9892d6-3612-4fb4-882c-7c5aa95dcd78';

    public function callAPI(string $endpoint, array $params): ?array
    {
        $url = $this->base_url . $endpoint . '?' . http_build_query($params);
        $headers = [
            'X-Public-Key: ' . $this->public_key,
            'Accept-Language: en-US',
            'Accept: application/json'
        ];

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_HEADER => true,
            CURLOPT_SSL_VERIFYPEER => false
        ]);

        $response = curl_exec($curl);
        $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        curl_close($curl);

        $headers = substr($response, 0, $header_size);

        return $http_status == 200 ? json_decode(substr($response, $header_size), true) : null;
    }
}

$api = new HelloTicketsAPI();

// Excercise 1
$eventList = $api->callAPI('events', [
    'city_id' => 1, //NYC
    'limit' => 10,
    'page' => 1
]);

// Exercise 2
$performances = $api->callAPI('extend/data/events', [
    'city_id' => 1, //NYC
    'category_id' => 3, // Theatre
    'start_date' => '2024-05-20',
    'end_date' => '2024-06-01',
    'min_price' => 120,
    'limit' => 10,
    'page' => 1
]);

// Pending to refactor and move to class
ob_start();
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=exercise1.csv');
header('Pragma: no-cache');
header('Expires: 0');

$csvHeader = ['Event', 'URL', 'Venue', 'Category'];

$rawEventList = json_encode($eventList);
$data = json_decode($rawEventList, true);

ob_end_clean();
$output = fopen('php://output', 'w');
fputcsv($output, $csvHeader);

foreach($data as $data_item) {
    fputcsv($output, $data_item);
}

fclose( $output );
exit;