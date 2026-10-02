<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Clients extends Model
{
    use SoftDeletes;

    protected $table = 'clients';

    protected $fillable = [
        'last_name',
        'wheelchair_required',
        'interval_unit',
        'interval_value',
        'fixed_start_date',
    ];

    protected function casts(): array
    {
        return [
            'wheelchair_required' => 'boolean',
            'fixed_start_date' => 'date',
        ];
    }

    public function defaultStaff()
    {
        return $this->belongsToMany(Staff::class, 'client_default_staff');
    }

    public function fixedDaysOfWeek()
    {
        return $this->hasMany(ClientFixedDayOfWeek::class);
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }
}
