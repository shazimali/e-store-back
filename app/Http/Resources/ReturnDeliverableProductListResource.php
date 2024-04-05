<?php

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Resources\Json\JsonResource;

class ReturnDeliverableProductListResource extends JsonResource
{
   

    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'sku' => $this->sku,           
            'code' => $this->code, 
            'store' => $this->store ? $this->store->name : [],           
            'sale_price' => $this->sale_price,     
            'available_qty'=> $this->availableQty(),       
            'name' => $this->name,           
            'status' => $this->status,
            'created_at' => $this->created_at->toDateString()
        ];
    }
}
