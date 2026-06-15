<?php
$content = <<<'EOT'
<?php

namespace App\Jobs;

use App\Models\Station;
use App\Models\WeatherRecord;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessWeatherEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected array $data;

    /**
     * Create a new job instance.
     * Expects an array of weather events for bulk processing.
     * For backward compatibility, if a single event is passed, it is wrapped in an array.
     */
    public function __construct(array $data)
    {
        // Wrap in array if it's a single event (checking for 'station_code' key)
        if (isset($data['station_code'])) {
            $this->data = [$data];
        } else {
            $this->data = $data;
        }
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if (empty($this->data)) {
            return;
        }

        $stationCodes = array_column($this->data, 'station_code');
        $stations = Station::whereIn('code', $stationCodes)->get()->keyBy('code');

        $recordsToInsert = [];
        $now = Carbon::now();

        foreach ($this->data as $event) {
            $station = $stations->get($event['station_code']);

            if (! $station) {
                Log::warning("Station not found for code: {$event['station_code']}");
                continue;
            }

            $recordsToInsert[] = [
                'station_id' => $station->id,
                'observed_at' => $event['observed_at'],
                'precipitation_mm' => $event['precipitation_mm'],
                'temperature_c' => $event['temperature_c'],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (!empty($recordsToInsert)) {
            WeatherRecord::insert($recordsToInsert);
        }
    }
}
EOT;

file_put_contents('src/app/Jobs/ProcessWeatherEvent.php', $content);
