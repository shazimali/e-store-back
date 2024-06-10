<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReturnDeliverableRequest;
use App\Http\Requests\UpdateReturnDeliverableRequest;
use App\Http\Resources\BranchListResource;
use App\Http\Resources\Products\ProductsListResource;
use App\Http\Resources\ReturnDeliverableEditResource;
use App\Http\Resources\ReturnDeliverableListResource;
use App\Http\Resources\ReturnDeliverableProductListResource;
use App\Http\Resources\ReturnDeliverableShowResource;
use App\Http\Resources\StoreListResource;
use App\Models\Branch;
use App\Models\CompanyDetail;
use App\Models\Deliverable;
use App\Models\DeliverableDetail;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Http\Request;

class ReturnDeliverablesController extends Controller
{
    function index(Request $request) {
        $this->authorize('deliverable_access');
        
        $deliverables = Deliverable::return()->paginate($request->item_per_page);
        
        if($request->search){
            $deliverables = Deliverable::return()->where('store_id','LIKE',"%{$request->search}%")
            ->orWhere('total_qty','LIKE',"%{$request->search}%")
            ->orWhere('date','LIKE',"%{$request->search}%")
            ->paginate($request->item_per_page);
        }

        return ReturnDeliverableListResource::collection($deliverables);
    }

    function create() {
        $this->authorize('deliverable_create');
        return response()->json(StoreListResource::collection(Store::all()));
    }

    function getBranchesByStoreId($id) {
        $this->authorize('deliverable_create');
        return  BranchListResource::collection(Branch::where('store_id',$id)->get());
    }

    function store(StoreReturnDeliverableRequest $request) {
        $this->authorize('deliverable_save');
        $data = $request->all();
        $data['is_return'] = 1;
        $data['invoice_id'] =  Deliverable::return()->count() > 0 ?  Deliverable::return()->orderBy('invoice_sr_number')->first()->invoice_id+1 :7000;
        $data['invoice_prefix'] =  CompanyDetail::first()->del_px;
        $data['invoice_sr_number'] =  $data['invoice_id'];
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
        return new ReturnDeliverableShowResource(Deliverable::where('id',$id)->first());
    }

    function edit($id) {
        $this->authorize('purchase_edit');
        try {
            // return new  ReturnDeliverableEditResource(Deliverable::where('id',$id)->with('details')->first());
            $return_deliverable = Deliverable::where('id',$id)->with('details')->first();
            $all_products = [];
            foreach ($return_deliverable->details as $key => $detail) {
                $product_block = [
                    'id' => $detail->product->id,
                    'sku' => $detail->product->sku,           
                    'code' => $detail->product->code, 
                    'qty' => $detail->qty, 
                    'store' => $detail->product->store ? $detail->product->store->name : [],           
                    'sale_price' => $detail->product->sale_price,     
                    'available_qty'=> $this->returnAvailableQtyEditVersion( $detail->product->id, $return_deliverable->branch_id,$return_deliverable->id),       
                    'name' => $detail->product->name,           
                ];
                array_push($all_products, $product_block);
            }

            $data = [
                'invoice_sr_number' => $return_deliverable->invoice_sr_number,
                'date' => $return_deliverable->date,
                'remarks' => $return_deliverable->remarks,
                'return_type' => $return_deliverable->return_type,
                'branch_id' => $return_deliverable->branch_id,
                'sr_number' => $return_deliverable->sr_number,
                'store_id' => $return_deliverable->store_id,
                'order_date' => $return_deliverable->order_date,
                'products'=> $all_products,
                'total_qty' => $return_deliverable->total_qty,
            ];

            return response()->json($data);


        } catch (\Throwable $th) {
            return  response()->json(['error' => 'Error in getting deliverable.'],422);
        }

        
    }

    function update(UpdateReturnDeliverableRequest $request, $id) {
        $this->authorize('deliverable_edit');
        $deliverable = Deliverable::where('id',$id)->first();
        $deliverable->update([
                "date" => $request->date,
                "order_date" => $request->order_date,
                "store_id" => $request->store_id,
                "return_type" => $request->return_type,
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
                'date' => $request['date']
            ]);
        }

        return  response()->json('Return Deliverable updated successfully!');
    }

    function getProductsByBranchId($branch_id) {
            $productIds = [];
            $deliverables = Deliverable::with('details')->where('branch_id',$branch_id)
                            ->where('is_return',0)->get();
            foreach ($deliverables as $key => $deliverable) {
                foreach ($deliverable->details as $key => $detail) {
                    array_push($productIds, $detail->product_id);
                }
            }

            if(count($productIds)){
                $products = Product::whereIn('id',$productIds)->get();
                $all_products = [];
                foreach ($products as $key => $product) {
                    $product_block = [
                        'id' => $product->id,
                        'sku' => $product->sku,           
                        'code' => $product->code, 
                        'store' => $product->store ? $product->store->name : [],           
                        'sale_price' => $product->sale_price,     
                        'available_qty'=> $this->returnAvailableQty( $product->id, $branch_id),       
                        'name' => $product->name,           
                        'status' => $product->status,
                        'created_at' => $product->created_at->toDateString()
                    ];
                    array_push($all_products,$product_block);
                }
                return response()->json($all_products,200);
            }
            return [];
    }

    function returnAvailableQty($product_id,$branch_id) {
       
        //delivered
        $delivered = Deliverable::with('details')
        ->where('is_return',0)->where('branch_id',$branch_id)->get();
        $delivered_qty = 0;
        foreach ($delivered as $key => $del) {
            foreach ($del->details as $key => $detail) {
                if($detail->product_id == $product_id){
                    $delivered_qty =+  $detail->qty;
                }
            }
        }
        //return delivered
        $return_delivered = Deliverable::with('details')
        ->where('is_return',1)->where('branch_id',$branch_id)->get();
        $return_delivered_qty = 0;
        foreach ($return_delivered as $key => $del) {
            foreach ($del->details as $key => $detail) {
                if($detail->product_id == $product_id){
                    $return_delivered_qty =+  $detail->qty;
                }
            }
        }
        return $delivered_qty - $return_delivered_qty;
    }
    function returnAvailableQtyEditVersion($product_id,$branch_id,$deliverable_id) {
       
        //delivered
        $delivered = Deliverable::with('details')
        ->where('is_return',0)->where('branch_id',$branch_id)->get();
        $delivered_qty = 0;
        foreach ($delivered as $key => $del) {
            foreach ($del->details as $key => $detail) {
                if($detail->product_id == $product_id){
                    $delivered_qty =+  $detail->qty;
                }
            }
        }
        //return delivered
        $return_delivered = Deliverable::with('details')
                        ->where('is_return',1)->where('id','!=',$deliverable_id)
                        ->where('branch_id',$branch_id)->get();
        $return_delivered_qty = 0;
        foreach ($return_delivered as $key => $del) {
            foreach ($del->details as $key => $detail) {
                if($detail->product_id == $product_id){
                    $return_delivered_qty =+  $detail->qty;
                }
            }
        }
        return $delivered_qty - $return_delivered_qty;
    }
}
