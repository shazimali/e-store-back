<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class StoreResource extends JsonResource
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
            'status' => $this->status,
            'code' => $this->code,
            'name' => $this->name,
            'ntn' => $this->ntn,
            'sku_name' => $this->sku_name,
            'discount' => $this->discount,
            'is_sr' => $this->is_sr,
            'sale_tax_number' => $this->sale_tax_number,
            'address' => $this->address,
            'phone' => $this->phone,
            'email' => $this->email,
            'created_at' => $this->created_at->toDateString(),
            'city' => $this->city->title
        ];
    }
}
