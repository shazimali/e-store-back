<?php

namespace App\Http\Resources;

use App\Models\CompanyDetail;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductInvoiceShowResource extends JsonResource
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
            'name' => $this->name,
            'image' => $this->image,
            'sale_price' => $this->sale_price,
            'sku' => $this->sku,
            'code' => $this->code,
            'status' => $this->status,
            'ext_tax' => CompanyDetail::take(1)->first()->ext_tax,
            'sale_tax' => CompanyDetail::take(1)->first()->sale_tax
        ];
    }
}
