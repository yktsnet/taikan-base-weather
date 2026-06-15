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
        $this->client = new SqsClient([
            'region' => config('services.sqs.region', env('AWS_DEFAULT_REGION', 'ap-northeast-1')),
            'version' => 'latest',
        ]);
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
}
