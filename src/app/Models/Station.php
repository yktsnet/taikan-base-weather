<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Station extends Model
{
    // Mock station model for polling
    protected $fillable = ['code', 'name', 'river_name', 'prefecture', 'lat', 'lng', 'warning_level', 'danger_level'];
}
