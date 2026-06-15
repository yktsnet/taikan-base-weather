<?php
$file = 'src/app/Console/Commands/BulkQueueWorker.php';
$content = file_get_contents($file);
$content = str_replace(
    '$processedAny = $this->processQueue($waterQueueUrl, ProcessWaterLevelEvent::class, $maxMessages) || $processedAny;',
    '$processed = $this->processQueue($waterQueueUrl, ProcessWaterLevelEvent::class, $maxMessages); $processedAny = $processed || $processedAny;',
    $content
);
$content = str_replace(
    '$processedAny = $this->processQueue($weatherQueueUrl, ProcessWeatherEvent::class, $maxMessages) || $processedAny;',
    '$processed = $this->processQueue($weatherQueueUrl, ProcessWeatherEvent::class, $maxMessages); $processedAny = $processed || $processedAny;',
    $content
);
file_put_contents($file, $content);
