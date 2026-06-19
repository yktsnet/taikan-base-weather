<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WaterLevel;
use App\Models\WeatherRecord;
use App\Services\SqsQueueService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VerificationController extends Controller
{
    protected SqsQueueService $sqsService;

    public function __construct(SqsQueueService $sqsService)
    {
        $this->sqsService = $sqsService;
    }

    /**
     * Trigger the load test command in the background.
     */
    public function loadTest(Request $request): JsonResponse
    {
        $request->validate([
            'count' => ['nullable', 'integer', 'min:1', 'max:10000'],
        ]);

        $count = $request->input('count', 1000);

        // Run the load test Artisan command in the background
        $artisanPath = base_path('artisan');
        $command = "php {$artisanPath} app:load-test-poller --count={$count} > /dev/null 2>&1 &";
        
        Log::info('Triggering load test command in background', ['command' => $command]);
        shell_exec($command);

        return response()->json([
            'status' => 'success',
            'message' => "Load test triggered in background for {$count} messages.",
        ]);
    }

    /**
     * Get queue metrics and DB write performance statistics.
     */
    public function getMetrics(Request $request): JsonResponse
    {
        $waterQueueUrl = env('AWS_SQS_WATER_LEVEL_QUEUE_URL', '');
        $weatherQueueUrl = env('AWS_SQS_WEATHER_QUEUE_URL', '');
        $dlqQueueUrl = env('AWS_SQS_DLQ_QUEUE_URL', '');

        // Fetch SQS message counts
        $waterMetrics = !empty($waterQueueUrl) ? $this->sqsService->getQueueAttributes($waterQueueUrl) : ['pending' => 0, 'in_flight' => 0];
        $weatherMetrics = !empty($weatherQueueUrl) ? $this->sqsService->getQueueAttributes($weatherQueueUrl) : ['pending' => 0, 'in_flight' => 0];
        $dlqMetrics = !empty($dlqQueueUrl) ? $this->sqsService->getQueueAttributes($dlqQueueUrl) : ['pending' => 0, 'in_flight' => 0];

        // Fetch DB write counts in the last 5 minutes
        $fiveMinutesAgo = Carbon::now()->subMinutes(5);
        $waterDbCount = WaterLevel::where('created_at', '>=', $fiveMinutesAgo)->count();
        $weatherDbCount = WeatherRecord::where('created_at', '>=', $fiveMinutesAgo)->count();

        return response()->json([
            'water_queue' => $waterMetrics,
            'weather_queue' => $weatherMetrics,
            'dlq' => $dlqMetrics,
            'db_records' => [
                'water_levels_count_5m' => $waterDbCount,
                'weather_records_count_5m' => $weatherDbCount,
            ]
        ]);
    }
}
