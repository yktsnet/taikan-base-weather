<?php

namespace App\Console\Commands;

use App\Models\Station;
use App\Services\SqsQueueService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class LoadTestPoller extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:load-test-poller {--count=1000 : 送信するメッセージの総数} {--type=all : 送信するデータの種類(water|weather|all)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Load test poller to send large amounts of mock data to SQS in batches';

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
        $count = (int) $this->option('count');
        $type = $this->option('type');

        $this->info("Starting load test... Sending {$count} messages of type '{$type}'.");

        $stations = Station::pluck('code')->toArray();

        if (empty($stations)) {
            $this->error('No stations found in the database. Cannot generate data.');
            return;
        }

        $waterQueueUrl = config('services.sqs.water_level_queue', env('AWS_SQS_WATER_LEVEL_QUEUE_URL', ''));
        $weatherQueueUrl = config('services.sqs.weather_queue', env('AWS_SQS_WEATHER_QUEUE_URL', ''));

        if (($type === 'water' || $type === 'all') && empty($waterQueueUrl)) {
            $this->error('Water level SQS queue URL is not configured.');
            return;
        }

        if (($type === 'weather' || $type === 'all') && empty($weatherQueueUrl)) {
            $this->error('Weather SQS queue URL is not configured.');
            return;
        }

        $startTime = microtime(true);
        $totalSent = 0;

        $waterMessages = [];
        $weatherMessages = [];

        for ($i = 0; $i < $count; $i++) {
            $stationCode = $stations[array_rand($stations)];
            $now = Carbon::now();

            if ($type === 'water' || $type === 'all') {
                $baseLevel = 1.0 + (crc32($stationCode) % 10) / 10.0;
                $variation = sin($now->hour * M_PI / 6) * 0.5;
                $fluctuation = (rand(-10, 10) / 100.0);
                $waterLevel = max(0, round($baseLevel + $variation + $fluctuation, 2));

                $waterMessages[] = [
                    'station_code' => $stationCode,
                    'observed_at' => $now->format('Y-m-d H:i:s'),
                    'level_m' => $waterLevel,
                ];
            }

            if ($type === 'weather' || $type === 'all') {
                $baseTemp = 15.0; // Simple base temp
                $diurnalVariation = -cos(($now->hour - 3) * M_PI / 12) * 5.0;
                $tempFluctuation = (rand(-20, 20) / 10.0);
                $temperature = round($baseTemp + $diurnalVariation + $tempFluctuation, 1);

                $isRaining = rand(1, 100) > 80;
                $precipitation = $isRaining ? round(rand(1, 50) / 10.0, 1) : 0.0;

                $weatherMessages[] = [
                    'station_code' => $stationCode,
                    'observed_at' => $now->format('Y-m-d H:i:s'),
                    'precipitation_mm' => $precipitation,
                    'temperature_c' => $temperature,
                ];
            }

            // Send in chunks of 1000 to avoid memory issues if count is very large, but SQS batch limit is 10
            // SqsQueueService::sendMessageBatch handles the chunking to 10
            if (count($waterMessages) >= 1000) {
                if ($this->sqsService->sendMessageBatch($waterQueueUrl, $waterMessages)) {
                    $totalSent += count($waterMessages);
                }
                $waterMessages = [];
            }

            if (count($weatherMessages) >= 1000) {
                if ($this->sqsService->sendMessageBatch($weatherQueueUrl, $weatherMessages)) {
                    $totalSent += count($weatherMessages);
                }
                $weatherMessages = [];
            }
        }

        // Send remaining messages
        if (!empty($waterMessages)) {
            if ($this->sqsService->sendMessageBatch($waterQueueUrl, $waterMessages)) {
                $totalSent += count($waterMessages);
            }
        }

        if (!empty($weatherMessages)) {
            if ($this->sqsService->sendMessageBatch($weatherQueueUrl, $weatherMessages)) {
                $totalSent += count($weatherMessages);
            }
        }

        $endTime = microtime(true);
        $duration = $endTime - $startTime;
        $messagesPerSecond = $duration > 0 ? $totalSent / $duration : 0;

        $this->info(sprintf(
            "Load test completed! Sent %d messages in %.2f seconds (%.2f messages/second).",
            $totalSent,
            $duration,
            $messagesPerSecond
        ));
    }
}
