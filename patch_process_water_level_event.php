<?php
$content = <<<'EOT'
<?php

namespace App\Jobs;

use App\Mail\AlertNotification;
use App\Models\Alert;
use App\Models\Station;
use App\Models\WaterLevel;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ProcessWaterLevelEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected array $data;

    /**
     * Create a new job instance.
     * Expects an array of water level events for bulk processing.
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

        $waterLevelsToInsert = [];
        $alertsToInsert = [];
        $now = Carbon::now();

        // Used to track which events generated alerts to fetch them later for notifications
        $alertsInfo = [];

        foreach ($this->data as $event) {
            $station = $stations->get($event['station_code']);

            if (! $station) {
                Log::warning("Station not found for code: {$event['station_code']}");
                continue;
            }

            $level = $event['level_m'];
            $dangerLevel = $station->danger_level;
            $warningLevel = $station->warning_level;

            $alertStatus = 'normal';

            if ($dangerLevel !== null && $level >= $dangerLevel) {
                $alertStatus = 'danger';
            } elseif ($warningLevel !== null && $level >= $warningLevel && ($dangerLevel === null || $level < $dangerLevel)) {
                $alertStatus = 'warning';
            } elseif ($warningLevel !== null && $level >= $warningLevel * 0.8 && $level < $warningLevel) {
                $alertStatus = 'caution';
            }

            $waterLevelsToInsert[] = [
                'station_id' => $station->id,
                'observed_at' => $event['observed_at'],
                'level_m' => $level,
                'alert_status' => $alertStatus,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (in_array($alertStatus, ['caution', 'warning', 'danger'])) {
                $alertsToInsert[] = [
                    'station_id' => $station->id,
                    'triggered_at' => $event['observed_at'],
                    'level' => $alertStatus,
                    'level_m' => $level,
                    'notified' => false,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                $alertsInfo[] = [
                    'station' => $station,
                    'triggered_at' => $event['observed_at'],
                    'level' => $alertStatus,
                ];
            }
        }

        if (!empty($waterLevelsToInsert)) {
            WaterLevel::insert($waterLevelsToInsert);
        }

        if (!empty($alertsToInsert)) {
            Alert::insert($alertsToInsert);

            // Fetch the newly inserted alerts to send emails.
            // Matching them by station_id and triggered_at.
            // Need to handle potential multiple alerts per station in the same batch.
            $alertQuery = Alert::query();
            foreach ($alertsInfo as $info) {
                $alertQuery->orWhere(function ($query) use ($info) {
                    $query->where('station_id', $info['station']->id)
                          ->where('triggered_at', $info['triggered_at'])
                          ->where('level', $info['level']);
                });
            }
            $insertedAlerts = $alertQuery->where('notified', false)->get();

            $alertsToUpdate = [];

            foreach ($insertedAlerts as $alert) {
                $station = $stations->where('id', $alert->station_id)->first();
                if ($station) {
                    try {
                        Mail::to('admin@example.com')->send(new AlertNotification($station, $alert));
                        $alertsToUpdate[] = $alert->id;
                    } catch (\Exception $e) {
                        Log::error('Failed to send alert email: '.$e->getMessage());
                    }
                }
            }

            if (!empty($alertsToUpdate)) {
                Alert::whereIn('id', $alertsToUpdate)->update(['notified' => true]);
            }
        }
    }
}
EOT;

file_put_contents('src/app/Jobs/ProcessWaterLevelEvent.php', $content);
