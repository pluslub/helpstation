<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaffDefaultSchedule extends Model
{
    protected $table = 'staff_default_schedule';

    protected $fillable = [
        'staff_id',
        'day_of_week',
        'is_am_off',
        'is_pm_off',
        'start_time',
        'end_time',
    ];

    protected function casts(): array
    {
        return [
            'is_am_off' => 'boolean',
            'is_pm_off' => 'boolean',
            'start_time' => 'datetime',
            'end_time' => 'datetime',
        ];
    }

    public function staff()
    {
        return $this->belongsTo(Sfaff::class);
    }
}
