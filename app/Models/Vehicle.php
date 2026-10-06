<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vehicle extends Model
{
    use SoftDeletes;

    protected $table = 'vehicle';

    protected $fillable = [
        'name',
        'type',
        'is_unavailable',
    ];

    protected function casts(): array{
        return[
            is_unavailable => 'boolean',
        ];
    }

    public function reservation(){
        return $this->hasMany(Reservaton::class);
    }
}
