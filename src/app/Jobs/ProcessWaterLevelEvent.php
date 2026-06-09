<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Station;
use App\Models\WaterLevel;
use App\Models\Alert;
use App\Mail\AlertNotification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class ProcessWaterLevelEvent implements ShouldQueue
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
        // $this->data = [
        //     'station_code' => 'xxx',
        //     'level_m' => 1.23,
        //     'observed_at' => '2023-01-01 12:00:00',
        // ];

        $station = Station::where('code', $this->data['station_code'])->first();

        if (!$station) {
            Log::warning("Station not found for code: {$this->data['station_code']}");
            return;
        }

        $level = $this->data['level_m'];
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

        WaterLevel::create([
            'station_id' => $station->id,
            'observed_at' => $this->data['observed_at'],
            'level_m' => $level,
            'alert_status' => $alertStatus,
        ]);

        if (in_array($alertStatus, ['caution', 'warning', 'danger'])) {
            $alert = Alert::create([
                'station_id' => $station->id,
                'triggered_at' => $this->data['observed_at'],
                'level' => $alertStatus,
                'level_m' => $level,
                'notified' => false,
            ]);

            // Dispatch mail
            try {
                // To configure later, hardcode for now
                Mail::to('admin@example.com')->send(new AlertNotification($station, $alert));

                $alert->update(['notified' => true]);
            } catch (\Exception $e) {
                Log::error("Failed to send alert email: " . $e->getMessage());
            }
        }
    }
}
