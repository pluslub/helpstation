<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReservationStaff extends Model
{
    public $timestamps = false;

    protected $table = 'reservation_staff';

    protected $fillable = [
        'reservation_id',
        'staff_id',
    ];

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }
}
