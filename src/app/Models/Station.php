<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Station extends Model
{
    // Mock station model for polling
    protected $fillable = ['code', 'name', 'river_name', 'prefecture', 'lat', 'lng', 'warning_level', 'danger_level'];

    public function latestWaterLevel()
    {
        return $this->hasOne(WaterLevel::class)->latestOfMany('observed_at');
    }

    public function latestWeatherRecord()
    {
        return $this->hasOne(WeatherRecord::class)->latestOfMany('observed_at');
    }

    public function determineAlertLevel(float $level): string
    {
        $dangerLevel = $this->danger_level;
        $warningLevel = $this->warning_level;

        if ($dangerLevel !== null && $level >= $dangerLevel) {
            return 'danger';
        } elseif ($warningLevel !== null && $level >= $warningLevel && ($dangerLevel === null || $level < $dangerLevel)) {
            return 'warning';
        } elseif ($warningLevel !== null && $level >= $warningLevel * 0.8 && $level < $warningLevel) {
            return 'caution';
        }

        return 'normal';
    }
}
