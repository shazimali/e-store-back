<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class InvoicePrintProductsResource extends JsonResource
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
            'id' => $this->product->id,
            'name' => $this->product->name,
            'image' => $this->product->image,
            'sale_price' => $this->product->sale_price,
            'sku' => $this->product->sku,
            'code' => $this->product->code,
            'status' => $this->product->status,
            'qty' => $this->qty,
            'price' => $this->price,
            'ext_tax' => $this->ext_tax,
            'sale_tax' => $this->sale_tax
        ];
    }
}
