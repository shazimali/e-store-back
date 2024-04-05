<?php

namespace App\Http\Resources\Products;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductsListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
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
