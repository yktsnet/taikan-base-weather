<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Alert extends Model
{
    protected $fillable = ['station_id', 'triggered_at', 'level', 'level_m', 'notified'];

    public function station()
    {
        return $this->belongsTo(Station::class);
    }
}
