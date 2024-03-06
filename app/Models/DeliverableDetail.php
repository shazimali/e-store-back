<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliverableDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'deliverable_id',
        'product_id',
        'qty',
        'date',
        'created_at',
        'updated_at'
    ];
    public function product() {
        return $this->hasOne(Product::class,'id','product_id');
    }
}
