<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\StorePurchaseRequest;
use App\Http\Resources\Products\ProductsListResource;
use App\Http\Resources\PurchaseEditResource;
use App\Http\Resources\PurchaseListResource;
use App\Http\Resources\PurchaseShowPrintResource;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseDetail;
use Illuminate\Http\Request;

class PurchasesController extends Controller
{
    /**
     * @OA\Get(
     *     path="/purchases",
     *     summary="Index",
     *     tags={"Purchases"},
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
        $this->authorize('purchase_access');
        $purchases = Purchase::paginate($request->item_per_page);
        
        if($request->search){
            $purchases = Purchase::where('invoice_id','LIKE',"%{$request->search}%")
            ->orWhere('total_qty','LIKE',"%{$request->search}%")
            ->orWhere('total_price','LIKE',"%{$request->search}%")
            ->orWhere('date','LIKE',"%{$request->search}%")
            ->paginate($request->item_per_page);
        }

        return PurchaseListResource::collection($purchases);
    }

    /**
     * @OA\Get(
     *     path="/purchases/create",
     *     summary="Create",
     *     tags={"Purchases"},
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
        $this->authorize('purchase_create');

        return ProductsListResource::collection(Product::all());
    }

    /**
     * @OA\Post(
     *     path="/purchases/store",
     *     summary="Store",
     *     tags={"Purchases"},
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
    function store(StorePurchaseRequest $request) {
        $this->authorize('purchase_save');

        $data = $request->all();
        $data['invoice_id'] = Purchase::count() > 0 ? Purchase::latest('id')->first()->id+1 : 1;
        $purchase = Purchase::create($data);
        foreach ($request->products as $key => $product) {
            PurchaseDetail::create([
                'purchase_id' => $purchase->id,
                'product_id' => $product['id'],
                'qty' => $product['qty'],
                'price' => $product['price'],
                'date' => $request['date']
            ]);
        }
        return  response()->json('Purchase completed successfully!');
    }

    /**
     * @OA\Get(
     *     path="/purchases/edit/{id}",
     *     summary="Edit",
     *     tags={"Purchases"},
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
        $this->authorize('purchase_edit');

        try {
            return new  PurchaseEditResource(Purchase::where('id',$id)->with('details')->first());
        } catch (\Throwable $th) {
            return  response()->json(['error' => 'Error in getting purchase.'],422);
        }

        
    }

    /**
     * @OA\Put(
     *     path="/purchases/update/{id}",
     *     summary="Update",
     *     tags={"Purchases"},
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
    function update(StorePurchaseRequest $request, $id) {
        $this->authorize('purchase_edit');
        
        $purchase = Purchase::where('id',$id)->first();
        $purchase->update([
                "date" => $request->date,
                "total_qty" => $request->total_qty,
                "total_price" => $request->total_price,
        ]);

        PurchaseDetail::where('purchase_id',$purchase->id)->delete();
        
        foreach ($request->products as $key => $product) {
            PurchaseDetail::create([
                'purchase_id' => $purchase->id,
                'product_id' => $product['id'],
                'qty' => $product['qty'],
                'price' => $product['price'],
                'date' => $request['date']
            ]);
        }

        return  response()->json('Purchase updated successfully!');
    }

    /**
     * @OA\Get(
     *     path="/purchases/fetch-for-print/{id}",
     *     summary="Fetch For Print",
     *     tags={"Purchases"},
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
    function fetchForPrint($id){
        return new PurchaseShowPrintResource(Purchase::findOrFail($id));
    }
}
