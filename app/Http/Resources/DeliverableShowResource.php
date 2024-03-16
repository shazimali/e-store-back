<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DeliverableShowResource extends JsonResource
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
            'invoice_sr_number' => $this->invoice_sr_number,
            'date' => $this->date,
            'order_date' => $this->date,
            'customer' => new StoreResource($this->branch) ,
            'products'=> PurchaseRelationProductsResource::collection($this->details),
            'total_qty' => $this->total_qty,
            // 'created_at' => $this->created_at->toDateString(),
        ];
    }
}
