<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WaterLevel extends Model
{
    protected $fillable = ['station_id', 'observed_at', 'level_m', 'alert_status'];
}
