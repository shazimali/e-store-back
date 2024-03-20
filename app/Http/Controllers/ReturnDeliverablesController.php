<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReturnDeliverableRequest;
use App\Http\Requests\UpdateReturnDeliverableRequest;
use App\Http\Resources\BranchListResource;
use App\Http\Resources\Products\ProductsListResource;
use App\Http\Resources\ReturnDeliverableEditResource;
use App\Http\Resources\ReturnDeliverableListResource;
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
        
        $deliverables = Deliverable::Return()->paginate($request->item_per_page);
        
        if($request->search){
            $deliverables = Deliverable::Return()->where('store_id','LIKE',"%{$request->search}%")
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

    function fetchStoreBranchesProducts($id) {
        $this->authorize('deliverable_create');
        return response()->json([
            'products' => ProductsListResource::collection(Product::where('store_id',$id)->get()),
            'branches' => BranchListResource::collection(Branch::where('store_id',$id)->get())
        ]); 
    }

    function store(StoreReturnDeliverableRequest $request) {
        $this->authorize('deliverable_save');
        $data = $request->all();
        $data['is_return'] = 1;
        $data['invoice_id'] =  Deliverable::Return()->count() > 0 ?  Deliverable::Return()->latest('id')->first()->invoice_id+1 :7000;
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
            return new  ReturnDeliverableEditResource(Deliverable::where('id',$id)->with('details')->first());
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
                'price' => $product['price'],
                'date' => $request['date']
            ]);
        }

        return  response()->json('Return Deliverable updated successfully!');
    }
}
