<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveStoreRequest;
use App\Http\Requests\UpdateStoreRequest;
use App\Http\Resources\StoreListResource;
use App\Models\City;
use App\Models\Store;
use Illuminate\Http\Request;

class StoresController extends Controller
{
    function index(Request $request) {
        $this->authorize('store_access');
        $stores = Store::with('city')->paginate($request->item_per_page);
        
        if($request->search){
            $stores = Store::where('name','LIKE',"%{$request->search}%")
            ->orWhere('code','LIKE',"%{$request->search}%")
            ->orWhere('email','LIKE',"%{$request->search}%")
            ->orWhere('phone','LIKE',"%{$request->search}%")
            ->orWhere('status','LIKE',"%{$request->search}%")
            ->paginate($request->item_per_page);
        }
        return StoreListResource::collection($stores);
    }

    function create(){
        $this->authorize('store_create');
        try {
            return response()->json(City::get());
        } catch (\Throwable $th) {
            return  response()->json(['error' => 'Error in saving store.'],422);            
        }
    }

    function save(SaveStoreRequest $request){
        $this->authorize('store_save');
        try {
            Store::create($request->all());
            return response()->json('Store saved successfully.');
        } catch (\Throwable $th) {
            return  response()->json(['error' => 'Error in saving store.'],422);            
        }
    }

    function edit($id){
        $this->authorize('store_edit');
        try {
            $store = Store::find($id);
            return  response()->json($store);
        } catch (\Throwable $th) {
            return  response()->json(['error' => 'Error in saving product.'],422);
        }
    }

function update(UpdateStoreRequest $request, $id)   {
        $this->authorize('store_edit');
        try {
            $product = Store::find($id);
            $product->update($request->all());
            return  response()->json('Store updated successfully.');
        } catch (\Throwable $th) {
            return  response()->json(['error' => 'Error in saving store.'],422);
        }
    }
}
