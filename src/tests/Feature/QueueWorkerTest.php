<?php

use App\Jobs\ProcessWaterLevelEvent;
use App\Jobs\ProcessWeatherEvent;
use App\Mail\AlertNotification;
use App\Models\Station;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

test('queue worker runs correctly for danger level', function () {
    Mail::fake();

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

    $data = [
        'station_code' => 'TEST01',
        'level_m' => 5.5,
        'observed_at' => '2023-01-01 12:00:00',
    ];

    $job = new ProcessWaterLevelEvent($data);
    $job->handle();

    $this->assertDatabaseHas('water_levels', [
        'station_id' => $station->id,
        'level_m' => 5.5,
        'alert_status' => 'danger',
    ]);

    $this->assertDatabaseHas('alerts', [
        'station_id' => $station->id,
        'level' => 'danger',
        'level_m' => 5.5,
    ]);

    Mail::assertSent(AlertNotification::class, function ($mail) use ($station) {
        return $mail->station->id === $station->id;
    });
});

test('queue worker runs correctly for warning level', function () {
    Mail::fake();

    $station = Station::create([
        'code' => 'TEST02',
        'name' => 'Test Station 2',
        'river_name' => 'Test River 2',
        'prefecture' => 'Tokyo',
        'lat' => 35.6895,
        'lng' => 139.6917,
        'warning_level' => 3.0,
        'danger_level' => 5.0,
    ]);

    $data = [
        'station_code' => 'TEST02',
        'level_m' => 3.5,
        'observed_at' => '2023-01-01 12:00:00',
    ];

    $job = new ProcessWaterLevelEvent($data);
    $job->handle();

    $this->assertDatabaseHas('water_levels', [
        'station_id' => $station->id,
        'level_m' => 3.5,
        'alert_status' => 'warning',
    ]);

    $this->assertDatabaseHas('alerts', [
        'station_id' => $station->id,
        'level' => 'warning',
        'level_m' => 3.5,
    ]);

    Mail::assertSent(AlertNotification::class);
});

test('queue worker runs correctly for caution level', function () {
    Mail::fake();

    $station = Station::create([
        'code' => 'TEST03',
        'name' => 'Test Station 3',
        'river_name' => 'Test River 3',
        'prefecture' => 'Tokyo',
        'lat' => 35.6895,
        'lng' => 139.6917,
        'warning_level' => 3.0,
        'danger_level' => 5.0,
    ]);

    $data = [
        'station_code' => 'TEST03',
        'level_m' => 2.5, // 3.0 * 0.8 = 2.4, so 2.5 is caution
        'observed_at' => '2023-01-01 12:00:00',
    ];

    $job = new ProcessWaterLevelEvent($data);
    $job->handle();

    $this->assertDatabaseHas('water_levels', [
        'station_id' => $station->id,
        'level_m' => 2.5,
        'alert_status' => 'caution',
    ]);

    $this->assertDatabaseHas('alerts', [
        'station_id' => $station->id,
        'level' => 'caution',
        'level_m' => 2.5,
    ]);

    Mail::assertSent(AlertNotification::class);
});

test('queue worker runs correctly for normal level', function () {
    Mail::fake();

    $station = Station::create([
        'code' => 'TEST04',
        'name' => 'Test Station 4',
        'river_name' => 'Test River 4',
        'prefecture' => 'Tokyo',
        'lat' => 35.6895,
        'lng' => 139.6917,
        'warning_level' => 3.0,
        'danger_level' => 5.0,
    ]);

    $data = [
        'station_code' => 'TEST04',
        'level_m' => 1.0,
        'observed_at' => '2023-01-01 12:00:00',
    ];

    $job = new ProcessWaterLevelEvent($data);
    $job->handle();

    $this->assertDatabaseHas('water_levels', [
        'station_id' => $station->id,
        'level_m' => 1.0,
        'alert_status' => 'normal',
    ]);

    $this->assertDatabaseMissing('alerts', [
        'station_id' => $station->id,
    ]);

    Mail::assertNothingSent();
});

test('weather queue worker runs correctly', function () {
    $station = Station::create([
        'code' => 'TEST05',
        'name' => 'Test Station 5',
        'river_name' => 'Test River 5',
        'prefecture' => 'Tokyo',
        'lat' => 35.6895,
        'lng' => 139.6917,
    ]);

    $data = [
        'station_code' => 'TEST05',
        'precipitation_mm' => 12.5,
        'temperature_c' => 25.4,
        'observed_at' => '2023-01-01 12:00:00',
    ];

    $job = new ProcessWeatherEvent($data);
    $job->handle();

    $this->assertDatabaseHas('weather_records', [
        'station_id' => $station->id,
        'precipitation_mm' => 12.5,
        'temperature_c' => 25.4,
    ]);
});
