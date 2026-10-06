<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupportType extends Model
{
    use SoftDeletes;

    protected $table = 'support_type';

    protected $fillable = [
        'name',
        'dispatch_priority'
    ];

    public function reservations(){
        return $this->hasMany(Reservation::class);
    }
}
