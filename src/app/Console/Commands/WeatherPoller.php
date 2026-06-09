<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Station;
use App\Services\SqsQueueService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class WeatherPoller extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:poll-weather';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Poll mock weather data and send to SQS';

    protected SqsQueueService $sqsService;

    public function __construct(SqsQueueService $sqsService)
    {
        parent::__construct();
        $this->sqsService = $sqsService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting weather polling...');

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

        $now = Carbon::now();

        foreach ($stations as $station) {
            // Generate mock weather data based on station latitude and time
            // Base temperature based on latitude (rough approximation)
            $latitude = $station->lat ?? 35.0; // Default to somewhere in Japan
            $baseTemp = 30.0 - abs($latitude - 30) * 0.5;

            // Add diurnal variation
            $diurnalVariation = -cos(($now->hour - 3) * M_PI / 12) * 5.0;

            // Random fluctuation
            $tempFluctuation = (rand(-20, 20) / 10.0);

            $temperature = round($baseTemp + $diurnalVariation + $tempFluctuation, 1);

            // Mock precipitation: mostly 0, occasionally rain
            $isRaining = rand(1, 100) > 80;
            $precipitation = $isRaining ? round(rand(1, 50) / 10.0, 1) : 0.0;

            $eventData = [
                'station_code' => $station->code,
                'observed_at' => $now->format('Y-m-d H:i:s'),
                'precipitation_mm' => $precipitation,
                'temperature_c' => $temperature,
            ];

            $this->info("Sending weather data for station {$station->code}...");
            $success = $this->sqsService->sendMessage($queueUrl, $eventData);

            if ($success) {
                Log::info("Successfully polled and sent weather data for {$station->code}");
            } else {
                Log::error("Failed to send weather data for {$station->code}");
            }
        }

        $this->info('Finished weather polling.');
    }
}
