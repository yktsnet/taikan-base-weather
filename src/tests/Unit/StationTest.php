<?php

use App\Models\Station;

test('station model exists', function () {
    $station = new Station;
    expect($station)->toBeInstanceOf(Station::class);
});

test('station model calculates alert levels correctly', function () {
    $station = new Station;
    $station->warning_level = 3.0;
    $station->danger_level = 5.0;

    expect($station->determineAlertLevel(1.0))->toBe('normal');
    expect($station->determineAlertLevel(2.5))->toBe('caution');
    expect($station->determineAlertLevel(3.5))->toBe('warning');
    expect($station->determineAlertLevel(5.5))->toBe('danger');
});
