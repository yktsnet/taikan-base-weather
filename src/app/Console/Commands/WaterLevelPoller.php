<?php

namespace App\Console\Commands;

use App\Models\Station;
use App\Services\SqsQueueService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class WaterLevelPoller extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:poll-water-level';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Poll mock water level data and send to SQS';

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
        $this->info('Starting water level polling...');

        // Fetch valid station codes from the database
        // Assuming all stations are valid for this example
        $stations = Station::all();

        if ($stations->isEmpty()) {
            $this->warn('No stations found.');

            return;
        }

        $queueUrl = config('services.sqs.water_level_queue', env('AWS_SQS_WATER_LEVEL_QUEUE_URL', ''));

        if (empty($queueUrl)) {
            $this->error('Water level SQS queue URL is not configured.');

            return;
        }

        $now = Carbon::now();

        foreach ($stations as $station) {
            // Generate mock water level data based on time or station properties
            // Base level between 0.5 and 2.0
            $baseLevel = 1.0 + (crc32($station->code) % 10) / 10.0;

            // Add some variation based on current hour to simulate tide
            $variation = sin($now->hour * M_PI / 6) * 0.5;

            // Random fluctuation
            $fluctuation = (rand(-10, 10) / 100.0);

            $waterLevel = max(0, round($baseLevel + $variation + $fluctuation, 2));

            $eventData = [
                'station_code' => $station->code,
                'observed_at' => $now->format('Y-m-d H:i:s'),
                'level_m' => $waterLevel,
            ];

            $this->info("Sending water level data for station {$station->code}...");
            $success = $this->sqsService->sendMessage($queueUrl, $eventData);

            if ($success) {
                Log::info("Successfully polled and sent water level data for {$station->code}");
            } else {
                Log::error("Failed to send water level data for {$station->code}");
            }
        }

        $this->info('Finished water level polling.');
    }
}
