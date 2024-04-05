<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'sku',
        'code',
        'status',
        'store_id',
        'sale_price',
        'created_at',
        'updated_at'
    ];

    /**
     * Get the store associated with the Product
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function store()
    {
        return $this->hasOne(Store::class, 'id', 'store_id');
    }

    /**
     * Get all of the comments for the Product
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function deliverables()
    {
        return $this->hasMany(DeliverableDetail::class, 'product_id', 'id');
    }
    public function invoices()
    {
        return $this->hasMany(InvoiceDetail::class, 'product_id', 'id');
    }

    function availableQty() {
        $purchase = PurchaseDetail::where('product_id',$this->id)->sum('qty');
        $delivered = Deliverable::with('details')->where('is_return',0)->get();
        $delivered_qty = 0;
        foreach ($delivered as $key => $del) {
            foreach ($del->details as $key => $detail) {
                if($detail->product_id == $this->id){
                    $delivered_qty =+  $detail->qty;
                }
            }
        }
        return $purchase - $delivered_qty;
    }
}
