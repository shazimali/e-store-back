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
            'id' => $this->id,
            'date' => $this->date,
            'name'=> $this->product->name,
            'code'=> $this->product->code,
            'sku'=> $this->product->sku,
            'qty' => $this->qty,
            'price' => $this->price,
            'created_at' => $this->created_at->toDateString(),
        ];
    }
}
