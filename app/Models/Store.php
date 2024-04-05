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
        'ntn',
        'discount',
        'is_sr',
        'sale_tax_number',
        'address',
        'code',
        'phone',
        'city_id',
        'email'
    ];

    public function city()  {
        return  $this->hasOne(City::class,'id', 'city_id');
    }

    /**
     * Get all of the comments for the Store
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function branches()
    {
        return $this->hasMany(Branch::class, 'store_id', 'id');
    }
}
