<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'total_qty',
        'invoice_id',
        'total_price',
        'created_at',
        'updated_at'
    ];

    /**
     * Get all of the details for the Purchase
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function details()
    {
        return $this->hasMany(PurchaseDetail::class, 'purchase_id', 'id');
    }
}
