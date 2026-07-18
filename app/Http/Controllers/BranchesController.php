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
    /**
     * @OA\Get(
     *     path="/branches",
     *     summary="Index",
     *     tags={"Branches"},
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

    /**
     * @OA\Get(
     *     path="/branches/create",
     *     summary="Create",
     *     tags={"Branches"},
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

    /**
     * @OA\Post(
     *     path="/branches/save",
     *     summary="Save",
     *     tags={"Branches"},
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
    function save(StoreBranchRequest $request){
        $this->authorize('branch_save');
        try {
            Branch::create($request->all());
            return response()->json('Branch saved successfully.');
        } catch (\Throwable $th) {
            return  response()->json(['error' => 'Error in saving store.'],422);            
        }
    }

    /**
     * @OA\Get(
     *     path="/branches/edit/{id}",
     *     summary="Edit",
     *     tags={"Branches"},
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
    function edit($id){
        $this->authorize('branch_edit');
        try {
            $store = Branch::find($id);
            return  response()->json($store);
        } catch (\Throwable $th) {
            return  response()->json(['error' => 'Error in saving product.'],422);
        }
    }

    /**
     * @OA\Put(
     *     path="/branches/update/{id}",
     *     summary="Update",
     *     tags={"Branches"},
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

