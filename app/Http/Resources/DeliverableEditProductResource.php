<?php

namespace App\Http\Resources;

use App\Models\Deliverable;
use App\Models\PurchaseDetail;
use Illuminate\Http\Resources\Json\JsonResource;

class DeliverableEditProductResource extends JsonResource
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
            'date' => $this->date,
            'name'=> $this->product->name,
            'store_name'=> $this->product->store->name,
            'code'=> $this->product->code,
            'sku'=> $this->product->sku,
            'available_qty'=> $this->getAvailableQty($this->product->id, $this->deliverable_id),
            'qty' => $this->qty,
            'price' => $this->price,
            'created_at' => $this->created_at->toDateString(),
        ];
    }

    public function getAvailableQty($product_id, $deliverable_id){
        $purchase = PurchaseDetail::where('product_id',$product_id)->sum('qty');
        $delivered = Deliverable::where('id','!=',$deliverable_id)
                    ->where('is_return',0)
                    ->with('details')
                    ->get();
        $delivered_qty = 0;
        foreach ($delivered as $key => $del) {
            foreach ($del->details as $key => $detail) {
                if($detail->product_id == $product_id){
                    $delivered_qty =+  $detail->qty;
                }
            }
        }
        return $purchase - $delivered_qty;
    }
}
