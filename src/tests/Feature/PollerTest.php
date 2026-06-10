<?php

use App\Models\Station;
use App\Services\SqsQueueService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Mockery\MockInterface;

uses(RefreshDatabase::class);

test('water level poller runs successfully', function () {
    Http::fake();

    $station = Station::create([
        'code' => 'TEST01',
        'name' => 'Test Station',
        'river_name' => 'Test River',
        'prefecture' => 'Tokyo',
        'lat' => 35.6895,
        'lng' => 139.6917,
        'warning_level' => 3.0,
        'danger_level' => 5.0,
    ]);

    $sqsServiceMock = $this->mock(SqsQueueService::class, function (MockInterface $mock) {
        $mock->shouldReceive('sendMessage')->once()->andReturn(true);
    });

    config(['services.sqs.water_level_queue' => 'https://sqs.ap-northeast-1.amazonaws.com/123456789012/test-queue']);

    $exitCode = Artisan::call('app:poll-water-level');

    expect($exitCode)->toBe(0);
});

test('weather poller runs successfully', function () {
    Http::fake();

    $station = Station::create([
        'code' => 'TEST01',
        'name' => 'Test Station',
        'river_name' => 'Test River',
        'prefecture' => 'Tokyo',
        'lat' => 35.6895,
        'lng' => 139.6917,
    ]);

    $sqsServiceMock = $this->mock(SqsQueueService::class, function (MockInterface $mock) {
        $mock->shouldReceive('sendMessage')->once()->andReturn(true);
    });

    config(['services.sqs.weather_queue' => 'https://sqs.ap-northeast-1.amazonaws.com/123456789012/test-weather-queue']);

    $exitCode = Artisan::call('app:poll-weather');

    expect($exitCode)->toBe(0);
});
