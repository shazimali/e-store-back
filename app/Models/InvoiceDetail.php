<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'product_id',
        'qty',
        'price',
        'ext_tax',
        'sale_tax',
        'date',
        'created_at',
        'updated_at'
    ];
    public function product() {
        return $this->hasOne(Product::class,'id','product_id');
    }
    public function invoice() {
        return $this->hasOne(Invoice::class,'id','invoice_id');
    }
}
