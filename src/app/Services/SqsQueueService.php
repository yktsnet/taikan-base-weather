<?php

namespace App\Services;

use Aws\Exception\AwsException;
use Aws\Sqs\SqsClient;
use Illuminate\Support\Facades\Log;

class SqsQueueService
{
    protected SqsClient $client;

    public function __construct()
    {
        $config = [
            'region' => config('services.sqs.region', env('AWS_DEFAULT_REGION', 'ap-northeast-1')),
            'version' => 'latest',
        ];

        // LocalStack などのカスタムエンドポイントが指定されている場合は設定
        if (env('AWS_ENDPOINT')) {
            $config['endpoint'] = env('AWS_ENDPOINT');
        }

        // 認証情報が指定されている場合は設定
        if (env('AWS_ACCESS_KEY_ID') && env('AWS_SECRET_ACCESS_KEY')) {
            $config['credentials'] = [
                'key' => env('AWS_ACCESS_KEY_ID'),
                'secret' => env('AWS_SECRET_ACCESS_KEY'),
            ];
        }

        $this->client = new SqsClient($config);
    }

    /**
     * Send messages to SQS queue in batches.
     *
     * @param  string  $queueUrl  The URL of the SQS queue.
     * @param  array  $messages  An array of messages to send.
     * @return bool True if successful, false otherwise.
     */
    public function sendMessageBatch(string $queueUrl, array $messages): bool
    {
        if (empty($messages)) {
            return true;
        }

        try {
            $chunks = array_chunk($messages, 10);
            $allSuccessful = true;

            foreach ($chunks as $chunkIndex => $chunk) {
                $entries = [];
                foreach ($chunk as $index => $messageData) {
                    $entries[] = [
                        'Id' => 'msg_'.$chunkIndex.'_'.$index.'_'.uniqid(),
                        'MessageBody' => json_encode($messageData, JSON_UNESCAPED_UNICODE),
                    ];
                }

                $result = $this->client->sendMessageBatch([
                    'QueueUrl' => $queueUrl,
                    'Entries' => $entries,
                ]);

                if (! empty($result->get('Failed'))) {
                    $allSuccessful = false;
                    Log::error('Failed to send some messages in batch to SQS.', [
                        'queueUrl' => $queueUrl,
                        'failed' => $result->get('Failed'),
                    ]);
                }
            }

            if ($allSuccessful) {
                Log::info('Successfully sent message batch to SQS.', [
                    'queueUrl' => $queueUrl,
                    'count' => count($messages),
                ]);
            }

            return $allSuccessful;
        } catch (AwsException $e) {
            Log::error('Failed to send message batch to SQS.', [
                'queueUrl' => $queueUrl,
                'error' => $e->getMessage(),
                'awsErrorType' => $e->getAwsErrorType(),
                'awsErrorCode' => $e->getAwsErrorCode(),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('Unexpected error sending message batch to SQS.', [
                'queueUrl' => $queueUrl,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Send message to SQS queue.
     *
     * @param  string  $queueUrl  The URL of the SQS queue.
     * @param  array  $messageData  The data to send (will be JSON encoded).
     * @return bool True if successful, false otherwise.
     */
    public function sendMessage(string $queueUrl, array $messageData): bool
    {
        try {
            $result = $this->client->sendMessage([
                'QueueUrl' => $queueUrl,
                'MessageBody' => json_encode($messageData, JSON_UNESCAPED_UNICODE),
            ]);

            Log::info('Successfully sent message to SQS.', [
                'queueUrl' => $queueUrl,
                'messageId' => $result->get('MessageId'),
            ]);

            return true;
        } catch (AwsException $e) {
            Log::error('Failed to send message to SQS.', [
                'queueUrl' => $queueUrl,
                'error' => $e->getMessage(),
                'awsErrorType' => $e->getAwsErrorType(),
                'awsErrorCode' => $e->getAwsErrorCode(),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('Unexpected error sending message to SQS.', [
                'queueUrl' => $queueUrl,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Receive messages from SQS queue in batches.
     *
     * @param  string  $queueUrl  The URL of the SQS queue.
     * @param  int  $maxMessages  The maximum number of messages to return.
     * @return array Array of messages with 'ReceiptHandle' and 'Body'.
     */
    public function receiveMessageBatch(string $queueUrl, int $maxMessages = 10): array
    {
        try {
            $result = $this->client->receiveMessage([
                'QueueUrl' => $queueUrl,
                'MaxNumberOfMessages' => $maxMessages,
                'WaitTimeSeconds' => 5, // Short polling
            ]);

            return $result->get('Messages') ?? [];
        } catch (AwsException $e) {
            Log::error('Failed to receive message batch from SQS.', [
                'queueUrl' => $queueUrl,
                'error' => $e->getMessage(),
            ]);

            return [];
        } catch (\Exception $e) {
            Log::error('Unexpected error receiving message batch from SQS.', [
                'queueUrl' => $queueUrl,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Delete messages from SQS queue in batches.
     *
     * @param  string  $queueUrl  The URL of the SQS queue.
     * @param  array  $receiptHandles  An array of ReceiptHandles to delete.
     * @return bool True if successful, false otherwise.
     */
    public function deleteMessageBatch(string $queueUrl, array $receiptHandles): bool
    {
        if (empty($receiptHandles)) {
            return true;
        }

        try {
            $chunks = array_chunk($receiptHandles, 10);
            $allSuccessful = true;

            foreach ($chunks as $chunkIndex => $chunk) {
                $entries = [];
                foreach ($chunk as $index => $receiptHandle) {
                    $entries[] = [
                        'Id' => 'del_'.$chunkIndex.'_'.$index.'_'.uniqid(),
                        'ReceiptHandle' => $receiptHandle,
                    ];
                }

                $result = $this->client->deleteMessageBatch([
                    'QueueUrl' => $queueUrl,
                    'Entries' => $entries,
                ]);

                if (! empty($result->get('Failed'))) {
                    $allSuccessful = false;
                    Log::error('Failed to delete some messages in batch from SQS.', [
                        'queueUrl' => $queueUrl,
                        'failed' => $result->get('Failed'),
                    ]);
                }
            }

            return $allSuccessful;
        } catch (AwsException $e) {
            Log::error('Failed to delete message batch from SQS.', [
                'queueUrl' => $queueUrl,
                'error' => $e->getMessage(),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('Unexpected error deleting message batch from SQS.', [
                'queueUrl' => $queueUrl,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get queue attributes (specifically message counts).
     *
     * @param  string  $queueUrl  The URL of the SQS queue.
     * @return array Array with 'pending' and 'in_flight' message counts.
     */
    public function getQueueAttributes(string $queueUrl): array
    {
        try {
            $result = $this->client->getQueueAttributes([
                'QueueUrl' => $queueUrl,
                'AttributeNames' => [
                    'ApproximateNumberOfMessages',
                    'ApproximateNumberOfMessagesNotVisible',
                ],
            ]);

            $attributes = $result->get('Attributes') ?? [];

            return [
                'pending' => isset($attributes['ApproximateNumberOfMessages']) ? (int) $attributes['ApproximateNumberOfMessages'] : 0,
                'in_flight' => isset($attributes['ApproximateNumberOfMessagesNotVisible']) ? (int) $attributes['ApproximateNumberOfMessagesNotVisible'] : 0,
            ];
        } catch (AwsException $e) {
            Log::error('Failed to get queue attributes from SQS.', [
                'queueUrl' => $queueUrl,
                'error' => $e->getMessage(),
            ]);

            return [
                'pending' => 0,
                'in_flight' => 0,
            ];
        } catch (\Exception $e) {
            Log::error('Unexpected error getting queue attributes from SQS.', [
                'queueUrl' => $queueUrl,
                'error' => $e->getMessage(),
            ]);

            return [
                'pending' => 0,
                'in_flight' => 0,
            ];
        }
    }

    /**
     * Redrive messages from DLQ to the appropriate main queues based on content.
     */
    public function redriveDlqQueue(string $dlqUrl, string $waterQueueUrl, string $weatherQueueUrl, int $maxCount = 100): int
    {
        $redrivenCount = 0;
        $batchSize = 10;

        while ($redrivenCount < $maxCount) {
            $messages = $this->receiveMessageBatch($dlqUrl, $batchSize);
            if (empty($messages)) {
                break;
            }

            $waterMessages = [];
            $waterHandles = [];
            $weatherMessages = [];
            $weatherHandles = [];

            foreach ($messages as $message) {
                $body = json_decode($message['Body'], true) ?? [];
                $handle = $message['ReceiptHandle'];

                if (isset($body['level_m'])) {
                    $waterMessages[] = $body;
                    $waterHandles[] = $handle;
                } elseif (isset($body['precipitation_mm']) || isset($body['temperature_c'])) {
                    $weatherMessages[] = $body;
                    $weatherHandles[] = $handle;
                } else {
                    Log::warning('Unknown message structure in DLQ, routing to water level queue by default.', ['body' => $body]);
                    $waterMessages[] = $body;
                    $waterHandles[] = $handle;
                }
            }

            if (! empty($waterMessages)) {
                if ($this->sendMessageBatch($waterQueueUrl, $waterMessages)) {
                    $this->deleteMessageBatch($dlqUrl, $waterHandles);
                    $redrivenCount += count($waterMessages);
                } else {
                    Log::error('Failed to send water level messages during DLQ redrive.');
                    break;
                }
            }

            if (! empty($weatherMessages)) {
                if ($this->sendMessageBatch($weatherQueueUrl, $weatherMessages)) {
                    $this->deleteMessageBatch($dlqUrl, $weatherHandles);
                    $redrivenCount += count($weatherMessages);
                } else {
                    Log::error('Failed to send weather messages during DLQ redrive.');
                    break;
                }
            }

            if (count($messages) < $batchSize) {
                break;
            }
        }

        return $redrivenCount;
    }
}
