<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\StorePurchaseRequest;
use App\Http\Resources\Products\ProductsListResource;
use App\Http\Resources\PurchaseEditResource;
use App\Http\Resources\PurchaseListResource;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseDetail;
use Illuminate\Http\Request;

class PurchasesController extends Controller
{
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

    function create() {
        $this->authorize('purchase_create');

        return ProductsListResource::collection(Product::all());
    }

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

    function edit($id) {
        $this->authorize('purchase_edit');

        try {
            return new  PurchaseEditResource(Purchase::where('id',$id)->with('details')->first());
        } catch (\Throwable $th) {
            return  response()->json(['error' => 'Error in getting purchase.'],422);
        }

        
    }

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
}
