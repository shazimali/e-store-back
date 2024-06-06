<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseRelationProductsResource extends JsonResource
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
            'id' => $this->product->id,
            'date' => $this->date,
            'name'=> $this->product->name,
            'store_name'=> $this->product->store->name,
            'code'=> $this->product->code,
            'sku'=> $this->product->sku,
            'available_qty'=> $this->product->availableQty(),
            'qty' => $this->qty,
            'price' => $this->price,
            'created_at' => $this->created_at->toDateString(),
        ];
    }
}
