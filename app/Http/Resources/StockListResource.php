<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class StockListResource extends JsonResource
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
            'title' => $this->name,
            'in_stock' => $this->deliverables ? $this->deliverables->sum('qty'): 0,
            'out_stock' => $this->invoices ? $this->invoices->sum('qty'): 0,
            'total' => $this->deliverables->sum('qty') - $this->invoices->sum('qty')
        ];
    }
}
