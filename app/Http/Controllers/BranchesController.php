<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBranchRequest;
use App\Http\Requests\UpdateBranchRequest;
use App\Http\Resources\BranchListResource;
use App\Models\Branch;
use App\Models\City;
use App\Models\Store;
use Illuminate\Http\Request;

class BranchesController extends Controller
{
    function index(Request $request) {
        $this->authorize('branch_access');
            $branches = Branch::with('city')->paginate($request->item_per_page);;
            if($request->search){
                $branches = Branch::with('city')
                ->orWhere('code','LIKE',"%{$request->search}%")
                ->orWhere('phone','LIKE',"%{$request->search}%")
                ->orWhere('status','LIKE',"%{$request->search}%")
                ->orWhere('ntn','LIKE',"%{$request->search}%")
                ->orWhere('sale_tax_number','LIKE',"%{$request->search}%")
                ->paginate($request->item_per_page);
            }

            if($request->store_id != 0 && $request->store_id != 'null'){
                $branches = Branch::with('city')
                    ->where('store_id',$request->store_id)
                    ->paginate($request->item_per_page);
            }

            if($request->search && ($request->store_id != 0 && $request->store_id != 'null')){
                $branches = Branch::with('city')
                ->where('store_id',$request->store_id)
                ->where(function($q) use($request){
                    $q->orWhere('code','LIKE',"%{$request->search}%");
                    $q->orWhere('phone','LIKE',"%{$request->search}%");
                    $q->orWhere('status','LIKE',"%{$request->search}%");
                    $q->orWhere('ntn','LIKE',"%{$request->search}%");
                    $q->orWhere('sale_tax_number','LIKE',"%{$request->search}%");
                })->paginate($request->item_per_page);
            }

       
        return BranchListResource::collection($branches);
    }

    function create(){
        $this->authorize('branch_create');
        try {
            return response()->json([
                'cities' =>City::get(),
                'stores' => Store::get()
            ]);
        } catch (\Throwable $th) {
            return  response()->json(['error' => 'Error in saving store.'],422);            
        }
    }

    function save(StoreBranchRequest $request){
        $this->authorize('branch_save');
        try {
            Branch::create($request->all());
            return response()->json('Branch saved successfully.');
        } catch (\Throwable $th) {
            return  response()->json(['error' => 'Error in saving store.'],422);            
        }
    }

    function edit($id){
        $this->authorize('branch_edit');
        try {
            $store = Branch::find($id);
            return  response()->json($store);
        } catch (\Throwable $th) {
            return  response()->json(['error' => 'Error in saving product.'],422);
        }
    }

function update(UpdateBranchRequest $request, $id)   {
        $this->authorize('branch_edit');
        try {
            $product = Branch::find($id);
            $product->update($request->all());
            return  response()->json('Branch updated successfully.');
        } catch (\Throwable $th) {
            return  response()->json(['error' => 'Error in saving store.'],422);
        }
    }
}

