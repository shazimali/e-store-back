<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DeliverableEditResource extends JsonResource
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
            'store_id' => $this->store_id,
            'products'=> PurchaseRelationProductsResource::collection($this->details),
            'total_qty' => $this->total_qty,
            'created_at' => $this->created_at->toDateString(),
        ];
    }
}
