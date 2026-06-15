<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ArchiveWaterLevelToS3 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:archive-water-level {--date= : アーカイブ対象日(YYYY-MM-DD)。デフォルトは前日}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Archive yesterday\'s (or specified date) water levels to S3 as CSV';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dateStr = $this->option('date');

        try {
            $targetDate = $dateStr ? Carbon::parse($dateStr) : Carbon::yesterday();
        } catch (\Exception $e) {
            $this->error('Invalid date format. Please use YYYY-MM-DD.');

            return 1;
        }

        $dateFormatted = $targetDate->format('Y-m-d');
        $this->info("Archiving water levels for date: {$dateFormatted}");

        $waterLevels = DB::table('water_levels')
            ->join('stations', 'water_levels.station_id', '=', 'stations.id')
            ->select(
                'water_levels.observed_at',
                'stations.code as station_code',
                'stations.name as station_name',
                'stations.river_name',
                'water_levels.level_m',
                'water_levels.alert_status'
            )
            ->whereDate('water_levels.observed_at', $dateFormatted)
            ->orderBy('water_levels.observed_at')
            ->get();

        if ($waterLevels->isEmpty()) {
            $this->info("No water level data found for {$dateFormatted}.");

            return 0;
        }

        $csvHeaders = ['observed_at', 'station_code', 'station_name', 'river_name', 'level_m', 'alert_status'];

        // Open memory stream to write CSV
        $handle = fopen('php://temp', 'r+');
        if ($handle === false) {
            $this->error('Failed to open memory stream for CSV creation.');

            return 1;
        }

        fputcsv($handle, $csvHeaders);

        foreach ($waterLevels as $row) {
            fputcsv($handle, [
                $row->observed_at,
                $row->station_code,
                $row->station_name,
                $row->river_name,
                $row->level_m,
                $row->alert_status,
            ]);
        }

        rewind($handle);
        $csvContent = stream_get_contents($handle);
        fclose($handle);

        $year = $targetDate->format('Y');
        $month = $targetDate->format('m');
        $dateStrForFileName = $targetDate->format('Ymd');
        $s3Path = "water-levels/{$year}/{$month}/water_levels_{$dateStrForFileName}.csv";

        try {
            Storage::disk('s3')->put($s3Path, $csvContent);
            $this->info("Successfully archived data to S3 at {$s3Path}");
            Log::info("Archived water levels for {$dateFormatted} to S3 path {$s3Path}");
        } catch (\Exception $e) {
            $this->error('Failed to upload to S3: '.$e->getMessage());
            Log::error("Failed to archive water levels for {$dateFormatted} to S3: ".$e->getMessage());

            return 1;
        }

        return 0;
    }
}
