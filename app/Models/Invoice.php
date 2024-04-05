<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'sr_number',
        'store_id',
        'date',
        'discount',
        'branch_id',
        'total_qty',
        'total_price',
        'remarks',
        'is_ex_tax'
    ];


    public function store() {
        return $this->hasOne(Store::class,'id','store_id');
    }

    public function branch() {
        return $this->hasOne(Branch::class,'id','branch_id');
    }

    public function details()
    {
        return $this->hasMany(InvoiceDetail::class, 'invoice_id', 'id');
    }
}
