<?php

namespace App\Console\Commands;

use App\Models\Station;
use App\Services\JmaApiService;
use App\Services\SqsQueueService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class WeatherPoller extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:poll-weather {--start=} {--end=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Poll real weather data and send to SQS. Use --start and --end for historical backfill.';

    protected SqsQueueService $sqsService;

    protected JmaApiService $jmaApiService;

    public function __construct(SqsQueueService $sqsService, JmaApiService $jmaApiService)
    {
        parent::__construct();
        $this->sqsService = $sqsService;
        $this->jmaApiService = $jmaApiService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $start = $this->option('start');
        $end = $this->option('end');

        $isHistorical = $start && $end;

        if ($isHistorical) {
            $this->info("Starting historical weather polling from {$start} to {$end}...");
        } else {
            $this->info('Starting real weather polling...');
        }

        // Fetch valid stations from the database
        $stations = Station::all();

        if ($stations->isEmpty()) {
            $this->warn('No stations found.');

            return;
        }

        $queueUrl = config('services.sqs.weather_queue', env('AWS_SQS_WEATHER_QUEUE_URL', ''));

        if (empty($queueUrl)) {
            $this->error('Weather SQS queue URL is not configured.');

            return;
        }

        foreach ($stations as $station) {
            $this->info("Fetching weather data for station {$station->code} via API...");

            if ($isHistorical) {
                $historicalData = $this->jmaApiService->getHistoricalWeather($station->code, $start, $end);

                if ($historicalData) {
                    foreach ($historicalData as $apiData) {
                        $eventData = [
                            'station_code' => $station->code,
                            'observed_at' => $apiData['observed_at'],
                            'precipitation_mm' => $apiData['precipitation_mm'] ?? 0.0,
                            'temperature_c' => $apiData['temperature_c'] ?? null,
                            'skip_notification' => true,
                        ];

                        $success = $this->sqsService->sendMessage($queueUrl, $eventData);

                        if ($success) {
                            Log::info("Successfully polled and sent historical weather data for {$station->code} at {$apiData['observed_at']}");
                        } else {
                            Log::error("Failed to send historical weather data for {$station->code} to SQS");
                        }
                    }
                } else {
                    $this->warn("No historical weather data returned for {$station->code}");
                    Log::warning("No historical weather data returned from JmaApiService for station {$station->code}");
                }
            } else {
                $apiData = $this->jmaApiService->getLatestWeather($station->code);

                if ($apiData) {
                    $eventData = [
                        'station_code' => $station->code,
                        'observed_at' => $apiData['observed_at'],
                        'precipitation_mm' => $apiData['precipitation_mm'] ?? 0.0,
                        'temperature_c' => $apiData['temperature_c'] ?? null,
                    ];

                    $success = $this->sqsService->sendMessage($queueUrl, $eventData);

                    if ($success) {
                        Log::info("Successfully polled and sent real weather data for {$station->code}");
                    } else {
                        Log::error("Failed to send weather data for {$station->code} to SQS");
                    }
                } else {
                    $this->warn("No weather data returned for {$station->code}");
                    Log::warning("No weather data returned from JmaApiService for station {$station->code}");
                }
            }
        }

        $this->info('Finished weather polling.');
    }
}
