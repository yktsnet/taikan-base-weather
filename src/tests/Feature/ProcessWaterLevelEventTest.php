<?php

use App\Jobs\ProcessWaterLevelEvent;
use App\Models\Station;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

test('queue worker skips notification if skip_notification is true', function () {
    Mail::fake();

    $station = Station::create([
        'code' => 'TEST06',
        'name' => 'Test Station 6',
        'river_name' => 'Test River 6',
        'prefecture' => 'Tokyo',
        'lat' => 35.6895,
        'lng' => 139.6917,
        'warning_level' => 3.0,
        'danger_level' => 5.0,
    ]);

    $data = [
        'station_code' => 'TEST06',
        'level_m' => 5.5, // Danger level
        'observed_at' => '2023-01-01 12:00:00',
        'skip_notification' => true,
    ];

    $job = new ProcessWaterLevelEvent([$data]);
    $job->handle();

    $this->assertDatabaseHas('water_levels', [
        'station_id' => $station->id,
        'level_m' => 5.5,
        'alert_status' => 'danger',
    ]);

    $this->assertDatabaseHas('alerts', [
        'station_id' => $station->id,
        'level' => 'danger',
        'notified' => true, // Ensure we marked it as notified
    ]);

    // Ensure email is not sent
    Mail::assertNothingSent();
});
