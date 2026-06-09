<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WeatherRecord extends Model
{
    protected $fillable = ['station_id', 'observed_at', 'precipitation_mm', 'temperature_c'];
}
