<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reservations extends Model
{
    protected $table = 'reservations';

    protected $fillable = [
        'client_id',
        'date',
        'start_time',
        'end_time',
        'support_type_id',
        'vehicle_id',
        'vehicle_type_choice',
        'remarks',
        'status',
        'vehicle_reassigned_flag',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'vehicle_reassigned_flag' => 'boolean',
        ];
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function supportType()
    {
        return $this->belongsTo(SupportType::class);
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function staffAssignments()
    {
        return $this->hasMany(ReservationStaff::class);
    }
}
