<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceListResoruce extends JsonResource
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
            'store' => $this->store->name,
            'discount' => $this->discount,
            'branch' => $this->branch->name,
            'total_qty' => $this->total_qty,
            'total_price' => $this->total_price,
            'created_at' => $this->created_at->toDateString(),
        ];
    }
}
