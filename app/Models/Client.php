<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use SoftDeletes;

    protected $table = 'client';

    protected $fillable = [
        'last_name',
        'wheelchair_required',
        'interval_unit',
        'interval_value',
        'fixed_start_date',
    ];

    /**
     * MariaDBから取り出したデータを型変換する。
     * DBの型⇒PHPの型
     * INT⇒整数
     * VARCHAR・ENUM⇒文字列
     * BOOLEAN⇒整数（0/1）
     * DATE・DATETIME⇒文字列
     * TIME⇒文字列
     *
     * BOOLEANとDATE・DATETIME型だけ変換が必要。
     */
    protected function casts(): array
    {
        return [
            'wheelchair_required' => 'boolean',
            'fixed_start_date' => 'date:Y-m-d',
        ];
    }

    public function defaultStaff()
    {
        return $this->belongsToMany(Staff::class, 'client_default_staff');
    }

    public function fixedDaysOfWeek()
    {
        return $this->hasMany(ClientFixedDaysOfWeek::class);
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }
}
