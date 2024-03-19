<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Deliverable extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'branch_id',
        'sr_number',
        'total_qty',
        'remarks',
        'invoice_id',
        'invoice_prefix',
        'invoice_sr_number',
        'date',
        'order_date',
        'created_at',
        'updated_at'
    ];

    public function store() {
        return $this->hasOne(Store::class,'id','store_id');
    }

    public function branch() {
        return $this->hasOne(Branch::class,'id','branch_id');
    }

    public function details()
    {
        return $this->hasMany(DeliverableDetail::class, 'deliverable_id', 'id');
    }
}
