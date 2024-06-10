<?php

namespace App\Http\Resources;

use App\Models\Deliverable;
use App\Models\DeliverableDetail;
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
            'in_stock' => $this->deliverables ? $this->inStockQty($this->deliverables) : 0,
            'out_stock' => $this->invoices ? $this->invoices->sum('qty'): 0,
            'total' => $this->inStockQty($this->deliverables) - $this->invoices->sum('qty')
        ];
    }

    function inStockQty($deliverables) {
        $delIds =  Deliverable::notReturn()->whereIn('id',$deliverables->pluck('deliverable_id'))->pluck('id');
        $reDelIds =  Deliverable::return()->whereIn('id',$deliverables->pluck('deliverable_id'))->pluck('id');
        return DeliverableDetail::whereIn('deliverable_id',$delIds)->sum('qty') -  DeliverableDetail::whereIn('deliverable_id',$reDelIds)->sum('qty');
    }
}
