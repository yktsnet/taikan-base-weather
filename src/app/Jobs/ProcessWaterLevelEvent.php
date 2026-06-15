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
use Illuminate\Support\Facades\DB;
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

        $insertedAlertsIds = [];
        $stationsForMail = null;

        DB::transaction(function () use (&$insertedAlertsIds, &$stationsForMail) {
            $stationCodes = array_column($this->data, 'station_code');
            $stations = Station::whereIn('code', $stationCodes)->lockForUpdate()->get()->keyBy('code');
            $stationsForMail = $stations;

            $waterLevelsToInsert = [];
            $alertsToInsert = [];
            $now = Carbon::now();

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
                    // Check if an alert already exists to prevent duplicates
                    $exists = Alert::where('station_id', $station->id)
                        ->where('triggered_at', $event['observed_at'])
                        ->where('level', $alertStatus)
                        ->exists();

                    // Also check if we already added it to alertsToInsert in this batch
                    $alreadyInBatch = false;
                    foreach ($alertsToInsert as $a) {
                        if ($a['station_id'] == $station->id && $a['triggered_at'] == $event['observed_at'] && $a['level'] == $alertStatus) {
                            $alreadyInBatch = true;
                            break;
                        }
                    }

                    if (! $exists && ! $alreadyInBatch) {
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
            }

            if (! empty($waterLevelsToInsert)) {
                WaterLevel::insert($waterLevelsToInsert);
            }

            if (! empty($alertsToInsert)) {
                Alert::insert($alertsToInsert);

                $insertedAlerts = Alert::where('notified', false)->where(function ($query) use ($alertsInfo) {
                    foreach ($alertsInfo as $info) {
                        $query->orWhere(function ($subQuery) use ($info) {
                            $subQuery->where('station_id', $info['station']->id)
                                ->where('triggered_at', $info['triggered_at'])
                                ->where('level', $info['level']);
                        });
                    }
                })->get();

                $insertedAlertsIds = $insertedAlerts->pluck('id')->toArray();
            }
        });

        // Send emails outside the transaction to avoid holding locks
        if (! empty($insertedAlertsIds) && $stationsForMail) {
            $alerts = Alert::whereIn('id', $insertedAlertsIds)->get();
            $alertsToUpdate = [];
            foreach ($alerts as $alert) {
                $station = $stationsForMail->where('id', $alert->station_id)->first();
                if ($station) {
                    try {
                        Mail::to('admin@example.com')->send(new AlertNotification($station, $alert));
                        $alertsToUpdate[] = $alert->id;
                    } catch (\Exception $e) {
                        Log::error('Failed to send alert email: '.$e->getMessage());
                    }
                }
            }
            if (! empty($alertsToUpdate)) {
                Alert::whereIn('id', $alertsToUpdate)->update(['notified' => true]);
            }
        }
    }
}
