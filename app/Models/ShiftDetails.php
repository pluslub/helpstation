<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShiftDetails extends Model
{
    public $timestamps = false;

    protected $table = 'shift_details';

    protected $fillable = [
        'shift_id',
        'date',
        'applied_start_time',
        'applied_end_time',
        'applied_am_off',
        'applied_pm_off',
        'applied_desired_off',
        'approval_flag',
        'admin_modified_flag',
        'modified_start_time',
        'modified_end_time',
        'modified_am_off',
        'modified_pm_off',
        'modified_approval_flag',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'applied_am_off' => 'boolean',
            'applied_pm_off' => 'boolean',
            'applied_desired_off' => 'boolean',
            'approval_flag' => 'boolean',
            'admin_modified_flag' => 'boolean',
            'modified_am_off' => 'boolean',
            'modified_pm_off' => 'boolean',
            'modified_approval_flag' => 'boolean',
        ];
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }
}
