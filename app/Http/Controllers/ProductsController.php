<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Requests\UpdateProductSalePriceRequest;
use App\Http\Resources\Products\ProductsListResource;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Http\Request;

class ProductsController extends Controller
{
    /**
     * @OA\Get(
     *     path="/products",
     *     summary="Index",
     *     tags={"Products"},
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
    function index(Request $request)  {
        $this->authorize('product_access');
        
        $products = Product::paginate($request->item_per_page);
        
        if($request->search){
            $products = Product::where('name','LIKE',"%{$request->search}%")
            ->orWhere('code','LIKE',"%{$request->search}%")
            ->orWhere('sku','LIKE',"%{$request->search}%")
            ->orWhere('status','LIKE',"%{$request->search}%")
            ->orWhere('sale_price','LIKE',"%{$request->search}%")
            ->paginate($request->item_per_page);
        }

        if($request->store_id != 0 && $request->store_id != 'null'){
            $products = Product::where('store_id',$request->store_id)
                ->paginate($request->item_per_page);
        }

        if($request->search && ($request->store_id != 0 && $request->store_id != 'null')){
            $products = Product::where('store_id',$request->store_id)
            ->where(function($q) use($request){
                $q->orWhere('code','LIKE',"%{$request->search}%");
                $q->orWhere('sku','LIKE',"%{$request->search}%");
                $q->orWhere('status','LIKE',"%{$request->search}%");
                $q->orWhere('sale_price','LIKE',"%{$request->search}%");
            })->paginate($request->item_per_page);
        }

        return ProductsListResource::collection($products);
    }

    /**
     * @OA\Get(
     *     path="/products/create",
     *     summary="Create",
     *     tags={"Products"},
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
        $this->authorize('product_create');
        try {
            return  response()->json(Store::get());
        } catch (\Throwable $th) {
            return  response()->json(['error' => 'Error in saving store.'],422);
        }
    }

    /**
     * @OA\Post(
     *     path="/products/store",
     *     summary="Store",
     *     tags={"Products"},
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
    function store(StoreProductRequest  $request)   {
        $this->authorize('product_create');
        try {
            Product::create($request->all());
            return  response()->json('Product saved successfully.');
        } catch (\Throwable $th) {
            return  response()->json(['error' => 'Error in saving product.'],422);
        }

    }

    /**
     * @OA\Get(
     *     path="/products/edit/{id}",
     *     summary="Edit",
     *     tags={"Products"},
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
    function edit($id)   {
        $this->authorize('product_edit');
        
        try {
            $product = Product::find($id);
            return  response()->json($product);
        } catch (\Throwable $th) {
            return  response()->json(['error' => 'Error in saving product.'],422);
        }

    }

    /**
     * @OA\Post(
     *     path="/products/update/{id}",
     *     summary="Update",
     *     tags={"Products"},
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
    function update(UpdateProductRequest $request, $id)   {
        $this->authorize('product_update');
        try {
            $product = Product::find($id);
            $product->update($request->all());
            return  response()->json('Product updated successfully.');
        } catch (\Throwable $th) {
            return  response()->json(['error' => 'Error in saving product.'],422);
        }

    }

    /**
     * @OA\Put(
     *     path="/products/update/sale-price/{id}",
     *     summary="Update Sale Price",
     *     tags={"Products"},
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
    function updateSalePrice($id,UpdateProductSalePriceRequest $request) {
        $product = Product::find($id);
        $product->sale_price = $request->sale_price;
        $product->save();
        return  response()->json('Sale price updated successfully.');
    }
}
