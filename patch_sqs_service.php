<?php
$content = file_get_contents('src/app/Services/SqsQueueService.php');

$addition = <<<EOT

    /**
     * Receive messages from SQS queue in batches.
     *
     * @param  string  \$queueUrl  The URL of the SQS queue.
     * @param  int  \$maxMessages  The maximum number of messages to return.
     * @return array Array of messages with 'ReceiptHandle' and 'Body'.
     */
    public function receiveMessageBatch(string \$queueUrl, int \$maxMessages = 10): array
    {
        try {
            \$result = \$this->client->receiveMessage([
                'QueueUrl' => \$queueUrl,
                'MaxNumberOfMessages' => \$maxMessages,
                'WaitTimeSeconds' => 5, // Short polling
            ]);

            return \$result->get('Messages') ?? [];
        } catch (AwsException \$e) {
            Log::error('Failed to receive message batch from SQS.', [
                'queueUrl' => \$queueUrl,
                'error' => \$e->getMessage(),
            ]);

            return [];
        } catch (\Exception \$e) {
            Log::error('Unexpected error receiving message batch from SQS.', [
                'queueUrl' => \$queueUrl,
                'error' => \$e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Delete messages from SQS queue in batches.
     *
     * @param  string  \$queueUrl  The URL of the SQS queue.
     * @param  array  \$receiptHandles  An array of ReceiptHandles to delete.
     * @return bool True if successful, false otherwise.
     */
    public function deleteMessageBatch(string \$queueUrl, array \$receiptHandles): bool
    {
        if (empty(\$receiptHandles)) {
            return true;
        }

        try {
            \$chunks = array_chunk(\$receiptHandles, 10);
            \$allSuccessful = true;

            foreach (\$chunks as \$chunkIndex => \$chunk) {
                \$entries = [];
                foreach (\$chunk as \$index => \$receiptHandle) {
                    \$entries[] = [
                        'Id' => 'del_' . \$chunkIndex . '_' . \$index . '_' . uniqid(),
                        'ReceiptHandle' => \$receiptHandle,
                    ];
                }

                \$result = \$this->client->deleteMessageBatch([
                    'QueueUrl' => \$queueUrl,
                    'Entries' => \$entries,
                ]);

                if (!empty(\$result->get('Failed'))) {
                    \$allSuccessful = false;
                    Log::error('Failed to delete some messages in batch from SQS.', [
                        'queueUrl' => \$queueUrl,
                        'failed' => \$result->get('Failed'),
                    ]);
                }
            }

            return \$allSuccessful;
        } catch (AwsException \$e) {
            Log::error('Failed to delete message batch from SQS.', [
                'queueUrl' => \$queueUrl,
                'error' => \$e->getMessage(),
            ]);

            return false;
        } catch (\Exception \$e) {
            Log::error('Unexpected error deleting message batch from SQS.', [
                'queueUrl' => \$queueUrl,
                'error' => \$e->getMessage(),
            ]);

            return false;
        }
    }
}
EOT;

$content = preg_replace('/}\s*$/', $addition, $content);
file_put_contents('src/app/Services/SqsQueueService.php', $content);
