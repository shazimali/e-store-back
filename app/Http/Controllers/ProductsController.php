<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\Products\ProductsListResource;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductsController extends Controller
{
    function index(Request $request)  {
        $this->authorize('product_access');
        
        $products = Product::paginate($request->item_per_page);
        
        if($request->search){
            $products = Product::where('name','LIKE',"%{$request->search}%")
            ->orWhere('code','LIKE',"%{$request->search}%")
            ->orWhere('sku','LIKE',"%{$request->search}%")
            ->orWhere('status','LIKE',"%{$request->search}%")
            ->paginate($request->item_per_page);
        }

        return ProductsListResource::collection($products);
    }

    function store(StoreProductRequest  $request)   {
        $this->authorize('product_create');
        try {
            Product::create($request->all());
            return  response()->json('Product saved successfully.');
        } catch (\Throwable $th) {
            return  response()->json(['error' => 'Error in saving product.'],422);
        }

    }

    function edit($id)   {
        $this->authorize('product_edit');
        
        try {
            $product = Product::find($id);
            return  response()->json($product);
        } catch (\Throwable $th) {
            return  response()->json(['error' => 'Error in saving product.'],422);
        }

    }

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
}
