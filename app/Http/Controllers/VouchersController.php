<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVoucherRequest;
use App\Http\Resources\BranchListResource;
use App\Http\Resources\StoreListResource;
use App\Http\Resources\VoucherEditResource;
use App\Http\Resources\VoucherListResource;
use App\Http\Resources\VoucherShowResource;
use App\Models\Branch;
use App\Models\Discount;
use App\Models\Invoice;
use App\Models\InvoiceDetail;
use App\Models\Store;
use App\Models\Voucher;
use Illuminate\Http\Request;

class VouchersController extends Controller
{

    /**
     * @OA\Get(
     *     path="/vouchers",
     *     summary="Index",
     *     tags={"Vouchers"},
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
        
        $this->authorize('deliverable_save');

        $vouchers = Voucher::paginate($request->item_per_page);
        if($request->search){
            $vouchers = Voucher::where('store_id','LIKE',"%{$request->search}%")
            ->orWhere('total_qty','LIKE',"%{$request->search}%")
            ->orWhere('date','LIKE',"%{$request->search}%")
            ->paginate($request->item_per_page);
        }

        return VoucherListResource::collection($vouchers);
    }

    /**
     * @OA\Post(
     *     path="/vouchers/store",
     *     summary="Store",
     *     tags={"Vouchers"},
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
    function store(StoreVoucherRequest $request) {
        
        $this->authorize('deliverable_save');
        $data = $request->all();
        $data['invoice_id'] =  Voucher::count() > 0 ?  Voucher::latest('id')->first()->invoice_id+1 :5000;
        $voucher= Voucher::create($data);
        return response()->json( $voucher->id);
    }

    /**
     * @OA\Get(
     *     path="/vouchers/fetch-stores",
     *     summary="Fetch Stores",
     *     tags={"Vouchers"},
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
    function fetchStores() {
        return response()->json(StoreListResource::collection(Store::get()));
    }

    /**
     * @OA\Get(
     *     path="/vouchers/fetch-store-branches/{id}",
     *     summary="Fetch Branches By Store I D",
     *     tags={"Vouchers"},
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
    function fetchBranchesByStoreID($branch_id) {
        return response()->json(BranchListResource::collection(Branch::where('store_id',$branch_id)->get()));
    }

    /**
     * @OA\Get(
     *     path="/vouchers/fetch-branch-balance/{id}",
     *     summary="Fetch Balance By Branch I D",
     *     tags={"Vouchers"},
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
    function fetchBalanceByBranchID($branch_id) {
        $total = Invoice::where('branch_id',$branch_id)->sum('total_price');
        $vouchers = Voucher::where('branch_id',$branch_id)->sum('amount');
        $discounts = Discount::where('branch_id',$branch_id)->sum('amount');
        $collected = $vouchers +  $discounts;
        return response()->json($total - $collected);
    }

    /**
     * @OA\Get(
     *     path="/vouchers/fetch-voucher/{id}",
     *     summary="Fetch Voucher By I D",
     *     tags={"Vouchers"},
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
    function fetchVoucherByID($id) {
        return response()->json(new VoucherShowResource(Voucher::findOrFail($id)));
    }

    /**
     * @OA\Get(
     *     path="/vouchers/edit/{id}",
     *     summary="Edit",
     *     tags={"Vouchers"},
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
        return response()->json(new VoucherEditResource(Voucher::find($id)));
    }

    /**
     * @OA\Put(
     *     path="/vouchers/update/{id}",
     *     summary="Update",
     *     tags={"Vouchers"},
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
    function update($id, StoreVoucherRequest $request) {
            $voucher = Voucher::where('id',$id)->first();
            $voucher->update([
                'date' => $request->date,
                'store_id' => $request->store_id,
                'branch_id' => $request->branch_id,
                'amount' => $request->amount,
                'remarks' => $request->remarks,
            ]);
        return response()->json($voucher->id);
    }
}
