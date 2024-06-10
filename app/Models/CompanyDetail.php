<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'ntn',
        'strn',
        'address',
        'del_px',
        'del_adjust',
        'inv_adjust',
        'sale_tax',
        'ext_tax',
        'phone',
        'created_at',
        'updated_at'
    ];
}
