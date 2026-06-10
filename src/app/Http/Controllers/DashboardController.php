<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Models\Station;
use App\Models\WaterLevel;
use App\Models\WeatherRecord;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        // Fetch all stations with their latest water level and latest weather record
        $stations = Station::with(['latestWaterLevel', 'latestWeatherRecord'])->get();

        return Inertia::render('Dashboard', [
            'stations' => $stations,
        ]);
    }

    public function show($id)
    {
        $station = Station::findOrFail($id);

        // Fetch recent 24 records of water levels and weather records
        $waterLevels = WaterLevel::where('station_id', $id)
            ->orderBy('observed_at', 'desc')
            ->limit(24)
            ->get();

        $weatherRecords = WeatherRecord::where('station_id', $id)
            ->orderBy('observed_at', 'desc')
            ->limit(24)
            ->get();

        return Inertia::render('StationDetail', [
            'station' => $station,
            'water_levels' => $waterLevels,
            'weather_records' => $weatherRecords,
        ]);
    }

    public function alerts()
    {
        // Fetch all alerts with related station, ordered by triggered_at desc
        $alerts = Alert::with('station')
            ->orderBy('triggered_at', 'desc')
            ->get();

        return Inertia::render('AlertHistory', [
            'alerts' => $alerts,
        ]);
    }
}
