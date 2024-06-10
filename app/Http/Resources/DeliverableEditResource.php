<?php

namespace App\Http\Resources;

use App\Models\CompanyDetail;
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
            'invoice_sr_number' => $this->invoice_sr_number,
            'date' => $this->date,
            'remarks' => $this->remarks,
            'branch_id' => $this->branch_id,
            'sr_number' => $this->sr_number,
            'store_id' => $this->store_id,
            'order_date' => $this->order_date,
            'products'=> DeliverableEditProductResource::collection($this->details),
            'total_qty' => $this->total_qty,
        ];
    }
}
