<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ReturnDeliverableListResource extends JsonResource
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
            'invoice_id' => $this->invoice_sr_number,
            'return_type' => $this->return_type,
            'date' => $this->date,
            'store' => $this->store->name,
            'total_qty' => $this->total_qty,
            'created_at' => $this->created_at->toDateString(),
        ];
    }
}
