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
use Illuminate\Support\Facades\Cache;

class DeliverablesController extends Controller
{
    /**
     * @OA\Get(
     *     path="/deliverables",
     *     summary="Index",
     *     tags={"Deliverables"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful response"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error"
     *     )
     * )
     */
    function index(Request $request) {
        $this->authorize('deliverable_access');
        // $deliverables = Deliverable::notReturn()->paginate($request->item_per_page);
        
        // if($request->search){
        //     $deliverables = Deliverable::notReturn()->where('store_id','LIKE',"%{$request->search}%")
        //     ->orWhere('total_qty','LIKE',"%{$request->search}%")
        //     ->orWhere('date','LIKE',"%{$request->search}%")
        //     ->paginate($request->item_per_page);
        // }

        $search = $request->search;
        $store_id = $request->store_id;
        $branch_id = $request->branch_id;

        $query = Deliverable::query();
        $query->when($search, function($q) use($search){
           return $q->orWhere('total_qty','LIKE',"%{$search}%")
                    ->orWhere('date','LIKE',"%{$search}%");
        });

        $query->when($store_id, function($q) use($store_id){
            return  $q->where('store_id',$store_id);
        });

        $query->when($branch_id, function($q) use($branch_id){
            return  $q->where('branch_id',$branch_id);
        });

        $deliverables = $query->notReturn()->orderBy('id','DESC')->paginate($request->item_per_page);

        return DeliverableListResource::collection($deliverables);
    }

    /**
     * @OA\Get(
     *     path="/deliverables/create",
     *     summary="Create",
     *     tags={"Deliverables"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful response"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error"
     *     )
     * )
     */
    function create() {
        $this->authorize('deliverable_create');
        return response()->json(StoreListResource::collection(Store::all()));
    }

    /**
     * @OA\Get(
     *     path="/deliverables/store-branches-products/{id}",
     *     summary="Fetch Store Branches Products",
     *     tags={"Deliverables"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error"
     *     )
     * )
     */
    function fetchStoreBranchesProducts($id) {
        $this->authorize('deliverable_create');
        return response()->json([
            'products' => ProductsListResource::collection(Product::where('store_id',$id)->get()),
            'branches' => BranchListResource::collection(Branch::where('store_id',$id)->get())
        ]); 
    }

    /**
     * @OA\Get(
     *     path="/deliverables/store-branches-products-edit/{store_id}/{deliverable_id}",
     *     summary="Fetch Store Branches Products Edit",
     *     tags={"Deliverables"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="store_id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="deliverable_id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error"
     *     )
     * )
     */
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

    /**
     * @OA\Post(
     *     path="/deliverables/store",
     *     summary="Store",
     *     tags={"Deliverables"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="placeholder", type="string", example="value")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error"
     *     )
     * )
     */
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
        
        Cache::forget("branch.products.{$deliverable->branch_id}");

        return  response()->json($deliverable->id);
    }

    /**
     * @OA\Get(
     *     path="/deliverables/fetch-deliverable-for-print/{id}",
     *     summary="Fetch Deliverable By Id",
     *     tags={"Deliverables"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error"
     *     )
     * )
     */
    function fetchDeliverableById($id) {
        return new DeliverableShowResource(Deliverable::where('id',$id)->first());
    }

    /**
     * @OA\Get(
     *     path="/deliverables/edit/{id}",
     *     summary="Edit",
     *     tags={"Deliverables"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error"
     *     )
     * )
     */
    function edit($id) {
        $this->authorize('deliverable_edit');
        try {
            return new  DeliverableEditResource(Deliverable::where('id',$id)->with('details')->first());
        } catch (\Throwable $th) {
            return  response()->json(['error' => 'Error in getting deliverable.'],422);
        }

        
    }

    /**
     * @OA\Put(
     *     path="/deliverables/update/{id}",
     *     summary="Update",
     *     tags={"Deliverables"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="placeholder", type="string", example="value")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error"
     *     )
     * )
     */
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

        Cache::forget("branch.products.{$deliverable->branch_id}");
        if ($deliverable->branch_id != $request->branch_id) {
            Cache::forget("branch.products.{$request->branch_id}");
        }

        return  response()->json('Deliverable updated successfully!');
    }
}
