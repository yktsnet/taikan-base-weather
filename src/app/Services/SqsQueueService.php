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
