<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDiscountRequest;
use App\Http\Requests\StoreVoucherRequest;
use App\Http\Requests\UpdateDiscountRequest;
use App\Http\Resources\BranchListResource;
use App\Http\Resources\DiscountEditResource;
use App\Http\Resources\DiscountListResource;
use App\Http\Resources\StoreListResource;
use App\Models\Branch;
use App\Models\Discount;
use App\Models\Invoice;
use App\Models\Store;
use App\Models\Voucher;
use Illuminate\Http\Request;

class DiscountsController extends Controller
{
    /**
     * @OA\Get(
     *     path="/discounts",
     *     summary="Index",
     *     tags={"Discounts"},
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

        $discounts = Discount::paginate($request->item_per_page);
        
        if($request->search){
            $discounts = Discount::where('store_id','LIKE',"%{$request->search}%")
            ->orWhere('date','LIKE',"%{$request->search}%")
            ->paginate($request->item_per_page);
        }

        return DiscountListResource::collection($discounts);
    }

    /**
     * @OA\Post(
     *     path="/discounts/store",
     *     summary="Store",
     *     tags={"Discounts"},
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
    function store(StoreDiscountRequest $request) {
        
        $this->authorize('deliverable_save');
        $invoice_detail = Invoice::where('invoice_id',$request->invoice_id)->first();
        $data = $request->all();
        $data['inv_id'] = $invoice_detail->id;
        // $data['invoice_id'] =  Discount::count() > 0 ?  Discount::latest('id')->first()->invoice_id+1 :6000;
        $discount= Discount::create($data);
        return response()->json( $discount->id);
    }

    /**
     * @OA\Get(
     *     path="/discounts/fetch-stores",
     *     summary="Fetch Stores",
     *     tags={"Discounts"},
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
     *     path="/discounts/fetch-store-branches/{id}",
     *     summary="Fetch Branches By Store I D",
     *     tags={"Discounts"},
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
     *     path="/discounts/fetch-branch-balance-and-invoices/{id}",
     *     summary="Fetch Balance And Invoices By Branch I D",
     *     tags={"Discounts"},
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
    function fetchBalanceAndInvoicesByBranchID($branch_id) {
        $invoices = Invoice::where('branch_id',$branch_id)->get();
        $discounts = Discount::where('branch_id',$branch_id)->sum('amount');
        $vouchers = Voucher::where('branch_id',$branch_id)->sum('amount');
        return response()->json([
            'balance' => $invoices->sum('total_price') - ($discounts + $vouchers),
            'invoices' => $invoices->pluck('invoice_id')
        ]);
    }


    /**
     * @OA\Get(
     *     path="/discounts/edit/{id}",
     *     summary="Edit",
     *     tags={"Discounts"},
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
        return response()->json(new DiscountEditResource(Discount::find($id)));
    }

    /**
     * @OA\Put(
     *     path="/discounts/update/{id}",
     *     summary="Update",
     *     tags={"Discounts"},
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
    function update($id, UpdateDiscountRequest $request) {
            $discount = Discount::where('id',$id)->first();
            $invoice_detail = Invoice::where('invoice_id',$request->invoice_id)->first();
            $discount->update([
                'date' => $request->date,
                'inv_id'=> $invoice_detail->id,
                'store_id' => $request->store_id,
                'invoice_id' => $request->invoice_id,
                'branch_id' => $request->branch_id,
                'amount' => $request->amount,
                'remarks' => $request->remarks,
            ]);
        return response()->json($discount->id);
    }
}
