<?php

namespace App\Http\Resources;

use App\Models\CompanyDetail;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceShowResource extends JsonResource
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
            'date' => $this->date,
            'remarks' => $this->remarks,
            'sr_number' => $this->sr_number,
            'invoice_id' => $this->invoice_id,
            'is_ex_tax' => $this->is_ex_tax,
            'city' => $this->branch->city,
            'store' => new StoreResource($this->branch->store) ,
            'customer' => new StoreResource($this->branch) ,
            'company' => new CompanyResource(CompanyDetail::where('id',1)->first()),
            'products'=> InvoicePrintProductsResource::collection($this->details),
            'total_qty' => $this->total_qty,
            'total_price' => $this->total_price,
        ];
    }
}
