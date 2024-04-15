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

class CSVExporter 
{
    public static function export(array $header, array $content, string $filename)
    {
        ob_start();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename='.$filename.'.csv');
        header('Pragma: no-cache');
        header('Expires: 0');

        ob_end_clean();
        $output = fopen('php://output', 'w');
        fputcsv($output, $header);

        foreach($content as $item) {
            fputcsv($output, $item);
        }

        fclose($output);
    }
}

class EventDataTransformer
{
    public static function e1FromRawToCSV($eventList): array
    {
        $output = [];

        foreach ($eventList as $event) {
            $eventVanue = $event['venue'];
            $eventCategory = $event['category'];
        
            $output[] = [
                $event['name'],
                $event['url'],
                $eventVanue['name'],
                $eventCategory['name']
            ];
        }

        return $output;
    }

    public static function e2FromRawToCSV($eventList): array
    {
        $output = [];

        foreach ($eventList as $event) {
            $output[] = [
                $event['id'],
                $event['name']
            ];
        }

        return $output;
    }
}

$api = new HelloTicketsAPI();

// Excercise 1
$rawAllNycEventList = $api->callAPI('events', [
    'city_id' => 1, //NYC
    'limit' => 0,
    'page' => 0
]);

CSVExporter::export(
    ['Event', 'URL', 'Venue', 'Category'],
    EventDataTransformer::e1FromRawToCSV($rawAllNycEventList['events']),
    'exercise1'
);

// Exercise 2
$exercise2rawData = $api->callAPI('extend/data/events', [
    'city_id' => 1, //NYC
    'category_id' => 3, // Theatre
    'start_date' => '2024-05-20',
    'end_date' => '2024-06-01',
    'limit' => 0,
    'page' => 0
]);

$performanceWithPriceList = array_filter($exercise2rawData['events'], function($event) {
    $eventPerformanceList = $event['performances'];
    foreach ($eventPerformanceList as $eventPerformance) {
        return $eventPerformance['price_range']['min_price'] < 120;
    }
});

CSVExporter::export(
    ['Id', 'Name'],
    EventDataTransformer::e2FromRawToCSV($performanceWithPriceList),
    'exercise2'
);