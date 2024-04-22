<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceEditResource extends JsonResource
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
            'invoice_id' => $this->invoice_id,
            'date' => $this->date,
            'remarks' => $this->remarks,
            'branch_id' => $this->branch_id,
            'sr_number' => $this->sr_number,
            'store_id' => $this->store_id,
            'discount' => $this->discount,
            'products'=> PurchaseRelationProductsResource::collection($this->details),
            'total_qty' => $this->total_qty,
            'total_price' => $this->total_price,
        ];
    }
}
