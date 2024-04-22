<?php

namespace App\Http\Resources;

use App\Models\CompanyDetail;
use Illuminate\Http\Resources\Json\JsonResource;

class VoucherShowResource extends JsonResource
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
            'amount' => $this->amount,
            'invoice_id' => $this->invoice_id,
            'store' => new StoreResource($this->store) ,
            'customer' => new StoreResource($this->branch) ,
            'company' => new CompanyResource(CompanyDetail::where('id',1)->first()),
        ];
    }
}
