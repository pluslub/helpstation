<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Staff extends Model
{
    use SoftDeletes;

    protected $table = 'staff';
    protected $hidden = ['password', 'failed_login_count', 'last_failed_login_at', 'locked_until'];

    protected $fillable = [
        'login_id',
        'password',
        'name',
        'role',
        'annual_work_hour_limit',
        'failed_login_count',
        'last_failed_login_at',
        'locked_until',
    ];

    protected function casts(): array
    {
        return [
            'last_failed_login_at' => 'datetime',
            'locked_until' => 'datetime',
        ];
    }

    public function defaultSchedules()
    {
        return $this->hasMany(StaffDefaultSchedule::class);
    }

    public function shifts()
    {
        return $this->hasMany(Shift::class);
    }

    public function defaultClients()
    {
        return $this->belongsToMany(Client::class, 'client_default_staff');
    }
}
