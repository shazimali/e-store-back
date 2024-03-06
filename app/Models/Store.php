<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'status',
        'code',
        'phone',
        'city_id',
        'email'
    ];

    public function city()  {
        return  $this->hasOne(City::class,'id', 'city_id');
    }
}
