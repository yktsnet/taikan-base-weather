<?php
$content = file_get_contents('src/tests/Feature/QueueWorkerTest.php');

$content = str_replace(
    '$job = new ProcessWaterLevelEvent($data);',
    '$job = new ProcessWaterLevelEvent([$data]);',
    $content
);

$content = str_replace(
    '$job = new ProcessWeatherEvent($data);',
    '$job = new ProcessWeatherEvent([$data]);',
    $content
);

file_put_contents('src/tests/Feature/QueueWorkerTest.php', $content);
