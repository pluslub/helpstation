<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaffDefautSchedules extends Model
{
    public $timestamps = false;

    protected $table = 'staff_default_schedules';

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
        ];
    }

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }
}
