<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class StoreListResource extends JsonResource
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
            'name' => $this->name,
            'code' => $this->code,
            'ntn' => $this->ntn,
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
