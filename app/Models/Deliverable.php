<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Deliverable extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'total_qty',
        'invoice_id',
        'date',
        'created_at',
        'updated_at'
    ];

    public function store() {
        return $this->hasOne(Store::class,'id','store_id');
    }

    public function details()
    {
        return $this->hasMany(DeliverableDetail::class, 'deliverable_id', 'id');
    }
}
