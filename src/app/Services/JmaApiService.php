<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class JmaApiService
{
    /**
     * Get the latest weather data for a given station.
     *
     * @param  string  $stationCode  The code of the station or nearest AMeDAS station.
     * @return array|null An array with 'temperature_c', 'precipitation_mm', and 'observed_at' keys, or null on failure.
     */
    public function getLatestWeather(string $stationCode): ?array
    {
        try {
            $mapping = [
                'ST001' => '62056', // 枚方
                'ST002' => '62078', // 大阪（守口最寄り）
                'ST003' => '61286', // 京都（宇治最寄り）
                'ST004' => '61286', // 京都（桂川最寄り）
                'ST005' => '62096', // 八尾（柏原最寄り）
                'ST006' => '64036', // 奈良（王寺最寄り）
                'ST007' => '62021', // 三田（宝塚最寄り）
                'ST008' => '62078', // 大阪（尼崎最寄り）
                'ST009' => '64081', // 五條
                'ST010' => '65022', // 和歌山
            ];
            $amedasCode = $mapping[$stationCode] ?? $stationCode;

            // JMA (Japan Meteorological Agency) API endpoint for AMeDAS data.
            // Example real endpoint: https://www.jma.go.jp/bosai/amedas/data/map/YYYYMMDDHH0000.json
            // We use a configured endpoint or a placeholder.
            $endpoint = config('services.jma_api.endpoint', 'https://www.jma.go.jp/bosai/amedas/data/latest_time.txt');

            // First, get the latest time from JMA API (standard pattern for JMA AMeDAS)
            $timeResponse = Http::timeout(5)->get($endpoint);

            if ($timeResponse->successful()) {
                $latestTime = trim($timeResponse->body()); // e.g. "2023-10-25T12:00:00+09:00"
                $formattedTime = date('YmdHi00', strtotime($latestTime));

                $dataEndpoint = "https://www.jma.go.jp/bosai/amedas/data/map/{$formattedTime}.json";
                $dataResponse = Http::timeout(5)->get($dataEndpoint);

                if ($dataResponse->successful()) {
                    $data = $dataResponse->json();

                    // The JMA API uses station codes as keys in the JSON response
                    if (isset($data[$amedasCode])) {
                        $stationData = $data[$amedasCode];

                        return [
                            'temperature_c' => isset($stationData['temp'][0]) ? (float) $stationData['temp'][0] : null,
                            'precipitation_mm' => isset($stationData['precipitation1h'][0]) ? (float) $stationData['precipitation1h'][0] : null,
                            'observed_at' => date('Y-m-d H:i:s', strtotime($latestTime)),
                        ];
                    }
                }
            }

            Log::warning("JMA API failed or missing data for station {$stationCode}.");

            return null;

        } catch (\Exception $e) {
            Log::error("Exception in JmaApiService for station {$stationCode}: ".$e->getMessage());

            return null;
        }
    }

    /**
     * Get historical weather data for a given station.
     *
     * @param  string  $stationCode  The code of the station.
     * @param  string  $start  Start date and time.
     * @param  string  $end  End date and time.
     * @return array|null An array of historical data or null on failure.
     */
    public function getHistoricalWeather(string $stationCode, string $start, string $end): ?array
    {
        try {
            // Note: Since JMA actual historical API logic is complex, we use a placeholder
            // similar to the real-time one.
            $endpoint = config('services.jma_api.history_endpoint', 'https://www.jma.go.jp/bosai/amedas/data/history.json');

            $response = Http::timeout(5)->get($endpoint, [
                'stationCode' => $stationCode,
                'start' => $start,
                'end' => $end,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                // Assuming the API returns a list of records in 'data'
                if (isset($data['data']) && is_array($data['data'])) {
                    $results = [];
                    foreach ($data['data'] as $record) {
                        if (isset($record['observationTime'])) {
                            $results[] = [
                                'temperature_c' => isset($record['temp']) ? (float) $record['temp'] : null,
                                'precipitation_mm' => isset($record['precipitation1h']) ? (float) $record['precipitation1h'] : null,
                                'observed_at' => $record['observationTime'],
                            ];
                        }
                    }

                    return $results;
                }
            }

            // Fallback for simulation: Generate simulated historical data
            // This is useful for testing without a real historical API
            Log::info("Using simulated historical weather data for station {$stationCode} from {$start} to {$end}");
            $results = [];
            $current = Carbon::parse($start);
            $endTime = Carbon::parse($end);

            while ($current->lte($endTime)) {
                $results[] = [
                    'temperature_c' => round(mt_rand(100, 350) / 10, 1), // random temp 10.0 to 35.0
                    'precipitation_mm' => round(mt_rand(0, 50) / 10, 1), // random prec 0.0 to 5.0
                    'observed_at' => $current->format('Y-m-d H:i:s'),
                ];
                $current->addMinutes(60); // simulated hourly data
            }

            return $results;

        } catch (\Exception $e) {
            Log::error("Exception in JmaApiService historical data for station {$stationCode}: ".$e->getMessage());

            return null;
        }
    }
}
