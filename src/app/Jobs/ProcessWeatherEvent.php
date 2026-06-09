<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Station;
use App\Models\WeatherRecord;
use Illuminate\Support\Facades\Log;

class ProcessWeatherEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected array $data;

    /**
     * Create a new job instance.
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $station = Station::where('code', $this->data['station_code'])->first();

        if (!$station) {
            Log::warning("Station not found for code: {$this->data['station_code']}");
            return;
        }

        WeatherRecord::create([
            'station_id' => $station->id,
            'observed_at' => $this->data['observed_at'],
            'precipitation_mm' => $this->data['precipitation_mm'],
            'temperature_c' => $this->data['temperature_c'],
        ]);
    }
}
