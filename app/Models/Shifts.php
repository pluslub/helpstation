<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shifts extends Model
{
    public $timestamps = false;

    protected $table = 'shift';

    protected $fillable = [
        'staff_id',
        'target_year_month',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
        ];
    }

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }

    public function details()
    {
        return $this->hasMany(ShiftDetail::class);
    }
}
