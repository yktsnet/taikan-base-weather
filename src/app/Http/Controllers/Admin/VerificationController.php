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
use Illuminate\Support\Facades\Storage;

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
        $waterMetrics = ! empty($waterQueueUrl) ? $this->sqsService->getQueueAttributes($waterQueueUrl) : ['pending' => 0, 'in_flight' => 0];
        $weatherMetrics = ! empty($weatherQueueUrl) ? $this->sqsService->getQueueAttributes($weatherQueueUrl) : ['pending' => 0, 'in_flight' => 0];
        $dlqMetrics = ! empty($dlqQueueUrl) ? $this->sqsService->getQueueAttributes($dlqQueueUrl) : ['pending' => 0, 'in_flight' => 0];

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
            ],
        ]);
    }

    /**
     * Redrive messages from DLQ to primary queues.
     */
    public function redriveDlq(Request $request): JsonResponse
    {
        $waterQueueUrl = env('AWS_SQS_WATER_LEVEL_QUEUE_URL', '');
        $weatherQueueUrl = env('AWS_SQS_WEATHER_QUEUE_URL', '');
        $dlqQueueUrl = env('AWS_SQS_DLQ_QUEUE_URL', '');

        if (empty($dlqQueueUrl) || empty($waterQueueUrl) || empty($weatherQueueUrl)) {
            return response()->json([
                'status' => 'error',
                'message' => 'SQS Queue URLs are not fully configured.',
            ], 500);
        }

        try {
            $count = $this->sqsService->redriveDlqQueue($dlqQueueUrl, $waterQueueUrl, $weatherQueueUrl);

            return response()->json([
                'status' => 'success',
                'message' => "{$count}件のメッセージを再投入しました。",
            ]);
        } catch (\Exception $e) {
            Log::error('Unexpected error during DLQ redrive', ['error' => $e->getMessage()]);

            return response()->json([
                'status' => 'error',
                'message' => '再投入処理中にエラーが発生しました。',
            ], 500);
        }
    }

    /**
     * Get list of S3 daily CSV archives.
     */
    public function getS3Archives(Request $request): JsonResponse
    {
        try {
            $files = Storage::disk('s3')->allFiles('water-levels');
            $archives = [];

            foreach ($files as $file) {
                // Skip non-csv files if any
                if (! str_ends_with($file, '.csv')) {
                    continue;
                }

                $sizeBytes = Storage::disk('s3')->size($file);
                $lastModified = Storage::disk('s3')->lastModified($file);

                $archives[] = [
                    'path' => $file,
                    'filename' => basename($file),
                    'size' => $this->formatBytes($sizeBytes),
                    'last_modified' => Carbon::createFromTimestamp($lastModified)->toDateTimeString(),
                ];
            }

            // Sort by last modified descending
            usort($archives, function ($a, $b) {
                return strcmp($b['last_modified'], $a['last_modified']);
            });

            return response()->json($archives);
        } catch (\Exception $e) {
            Log::error('Failed to get S3 archives', ['error' => $e->getMessage()]);

            return response()->json([
                'status' => 'error',
                'message' => 'アーカイブ一覧の取得に失敗しました。',
            ], 500);
        }
    }

    /**
     * Download an S3 daily CSV archive via Laravel proxy.
     */
    public function downloadS3Archive(Request $request)
    {
        $request->validate([
            'path' => ['required', 'string', 'regex:/^water-levels\/[0-9a-zA-Z_\/\.-]+\.csv$/'],
        ]);

        $path = $request->input('path');

        if (! Storage::disk('s3')->exists($path)) {
            abort(404, '指定されたアーカイブファイルが見つかりません。');
        }

        return Storage::disk('s3')->download($path);
    }

    /**
     * Helper to format bytes to human readable form.
     */
    protected function formatBytes(int $bytes, int $precision = 1): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, $precision).' '.$units[$pow];
    }
}
