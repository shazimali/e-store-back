<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CompanyResource extends JsonResource
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
            'ntn' => $this->ntn,
            'strn' => $this->strn,
            'address' => $this->address,
            'phone' => $this->phone,
            'del_px' => $this->del_px,
        ];
    }
}
