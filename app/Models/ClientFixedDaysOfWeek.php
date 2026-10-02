<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientFixedDaysOfWeek extends Model
{
    public $timestamps = false;

    protected $table = 'client_fixed_days_of_week';

    protected $fillable = [
        'client_id',
        'day_of_week',
        'fixed_start_time',
        'fixed_end_time',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
