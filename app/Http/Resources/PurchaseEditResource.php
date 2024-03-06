<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseEditResource extends JsonResource
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
            'invoice_id' => $this->invoice_id,
            'date' => $this->date,
            'products'=> PurchaseRelationProductsResource::collection($this->details),
            'total_qty' => $this->total_qty,
            'total_price' => $this->total_price,
            'created_at' => $this->created_at->toDateString(),
        ];
    }
}
