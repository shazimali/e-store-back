<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDeliverableRequest;
use App\Http\Requests\UpdateDeliverableRequest;
use App\Http\Resources\BranchListResource;
use App\Http\Resources\DeliverableEditResource;
use App\Http\Resources\DeliverableListResource;
use App\Http\Resources\DeliverableShowResource;
use App\Http\Resources\Products\ProductsListResource;
use App\Http\Resources\StoreListResource;
use App\Models\Branch;
use App\Models\CompanyDetail;
use App\Models\Deliverable;
use App\Models\DeliverableDetail;
use App\Models\Product;
use App\Models\PurchaseDetail;
use App\Models\Store;
use Illuminate\Http\Request;

class DeliverablesController extends Controller
{
    function index(Request $request) {
        $this->authorize('deliverable_access');
        $deliverables = Deliverable::notReturn()->paginate($request->item_per_page);
        
        if($request->search){
            $deliverables = Deliverable::notReturn()->where('store_id','LIKE',"%{$request->search}%")
            ->orWhere('total_qty','LIKE',"%{$request->search}%")
            ->orWhere('date','LIKE',"%{$request->search}%")
            ->paginate($request->item_per_page);
        }

        return DeliverableListResource::collection($deliverables);
    }

    function create() {
        $this->authorize('deliverable_create');
        return response()->json(StoreListResource::collection(Store::all()));
    }

    function fetchStoreBranchesProducts($id) {
        $this->authorize('deliverable_create');
        return response()->json([
            'products' => ProductsListResource::collection(Product::where('store_id',$id)->get()),
            'branches' => BranchListResource::collection(Branch::where('store_id',$id)->get())
        ]); 
    }

    function fetchStoreBranchesProductsEdit($store_id,$deliverable_id) {
        $this->authorize('deliverable_edit');
        $edit_products = [];
        $store_products = Product::where('store_id',$store_id)->get();
        foreach ($store_products as $key => $pr) {
            $product_block = [
                'id' => $pr->id,
                'sku' => $pr->sku,           
                'code' => $pr->code,           
                'store' => $pr->store ? $pr->store->name : [],           
                'sale_price' => $pr->sale_price,     
                'available_qty'=> $this->availableQtyEdit($pr->id,$deliverable_id),       
                'name' => $pr->name,           
                'status' => $pr->status,
                'created_at' => $pr->created_at->toDateString()
            ];

            array_push($edit_products, $product_block);
        }
        return response()->json([
            'products' => $edit_products,
            'branches' => BranchListResource::collection(Branch::where('store_id',$store_id)->get())
        ]); 
    }

    function availableQtyEdit($product_id, $deliverable_id){
        $purchase = PurchaseDetail::where('product_id',$product_id)->sum('qty');
        $delivered = Deliverable::where('id','!=',$deliverable_id)
                    ->where('is_return',0)
                    ->with('details')
                    ->get();
        $delivered_qty = 0;
        foreach ($delivered as $key => $del) {
            foreach ($del->details as $key => $detail) {
                if($detail->product_id == $product_id){
                    $delivered_qty +=  $detail->qty;
                }
            }
        }
        return $purchase - $delivered_qty;
    }

    function store(StoreDeliverableRequest $request) {
        $this->authorize('deliverable_save');
        $data = $request->all();
        $data['invoice_id'] =  Deliverable::notReturn()->notCleared()->count() > 0 ?  Deliverable::notReturn()->notCleared()->orderBy('invoice_sr_number','DESC')->first()->invoice_id+1 :1;
        $data['invoice_prefix'] =  CompanyDetail::first()->del_px;
        $data['invoice_sr_number'] =  $data['invoice_prefix'].str_pad($data['invoice_id'],5,'0',STR_PAD_LEFT);
        $deliverable = Deliverable::create($data);

        foreach ($request->products as $key => $product) {
            DeliverableDetail::create([
                'deliverable_id' => $deliverable->id,
                'product_id' => $product['id'],
                'qty' => $product['qty'],
                'price' => $product['price'],
                'date' => $request['date']
            ]);
        }
        return  response()->json($deliverable->id);
    }

    function fetchDeliverableById($id) {
        return new DeliverableShowResource(Deliverable::where('id',$id)->first());
    }

    function edit($id) {
        $this->authorize('deliverable_edit');
        try {
            return new  DeliverableEditResource(Deliverable::where('id',$id)->with('details')->first());
        } catch (\Throwable $th) {
            return  response()->json(['error' => 'Error in getting deliverable.'],422);
        }

        
    }

    function update(UpdateDeliverableRequest $request, $id) {
        $this->authorize('deliverable_edit');
        $deliverable = Deliverable::where('id',$id)->first();
        $deliverable->update([
                "date" => $request->date,
                "order_date" => $request->order_date,
                "store_id" => $request->store_id,
                "branch_id" => $request->branch_id,
                "sr_number" => $request->sr_number,
                "remarks" => $request->remarks,
                "total_qty" => $request->total_qty
        ]);

        DeliverableDetail::where('deliverable_id',$deliverable->id)->delete();
        
        foreach ($request->products as $key => $product) {
            DeliverableDetail::create([
                'deliverable_id' => $deliverable->id,
                'product_id' => $product['id'],
                'qty' => $product['qty'],
                'price' => $product['price'],
                'date' => $request['date']
            ]);
        }

        return  response()->json('Deliverable updated successfully!');
    }
}
