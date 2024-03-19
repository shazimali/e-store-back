<?php

namespace App\Http\Resources;

use App\Models\CompanyDetail;
use Faker\Provider\ar_EG\Company;
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
            'remarks' => $this->remarks,
            'sr_number' => $this->sr_number,
            'city' => $this->branch->city,
            'store' => new StoreResource($this->branch->store) ,
            'order_date' => $this->order_date,
            'customer' => new StoreResource($this->branch) ,
            'company' => new CompanyResource(CompanyDetail::where('id',1)->first()),
            'products'=> PurchaseRelationProductsResource::collection($this->details),
            'total_qty' => $this->total_qty,
            // 'created_at' => $this->created_at->toDateString(),
        ];
    }
}
