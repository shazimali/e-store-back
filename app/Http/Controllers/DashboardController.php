<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Discount;
use App\Models\Invoice;
use App\Models\Purchase;
use App\Models\Store;
use App\Models\Voucher;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(){
        try {
         
            return  response()->json([
                'total_states' => [
                    'sales' => Invoice::sum('total_price') - Discount::sum('amount'),
                    'purchases' => Purchase::sum('total_price'),
                    // 'sales_invoices' => Invoice::count(),
                    // 'deliverables' => Deliverable::notReturn()->sum('total_qty'),
                    // 'return_deliverables' => Deliverable::return()->sum('total_qty'),
                    'debit' => Voucher::sum('amount') - Discount::sum('amount'),
                    'credit' => Invoice::sum('total_price') - Voucher::sum('amount'),
                    'branches' => Branch::count(),
                    'stores' => Store::count(),
                    'invoiced_sale' => DB::select("call invoiced_sale"),
                    'invoiced_purchase' => DB::select("call invoiced_purchase"),
                ],
                'yearly_states' => [
                    'sales' => Invoice::whereYear('date',Carbon::now())->sum('total_price') - Discount::whereYear('date',Carbon::now())->sum('amount'),
                    'purchases' => Purchase::whereYear('date',Carbon::now())->sum('total_price'),
                    // 'sales_invoices' => Invoice::whereYear('date',Carbon::now())->count(),
                    // 'deliverables' => Deliverable::notReturn()->whereYear('date',Carbon::now())->sum('total_qty'),
                    // 'return_deliverables' => Deliverable::return()->whereYear('date',Carbon::now())->sum('total_qty'),
                    'debit' => Voucher::whereYear('date',Carbon::now())->sum('amount') - Discount::whereYear('date',Carbon::now())->sum('amount'),
                    'credit' => Invoice::whereYear('date',Carbon::now())->sum('total_price') - Voucher::whereYear('date',Carbon::now())->sum('amount'),
                    'branches' => Branch::count(),
                    'stores' => Store::count(),
                    'invoiced_sale' => DB::select("call invoiced_sale"),
                    'invoiced_purchase' => DB::select("call invoiced_purchase"),
                ],
                'monthly_states' => [
                    'sales' => Invoice::whereMonth('date',Carbon::now())->sum('total_price') - Discount::whereMonth('date',Carbon::now())->sum('amount'),
                    'purchases' => Purchase::whereMonth('date',Carbon::now())->sum('total_price'),
                    // 'sales_invoices' => Invoice::whereMonth('date',Carbon::now())->count(),
                    // 'deliverables' => Deliverable::notReturn()->whereMonth('date',Carbon::now())->sum('total_qty'),
                    // 'return_deliverables' => Deliverable::return()->whereMonth('date',Carbon::now())->sum('total_qty'),
                    'debit' => Voucher::whereMonth('date',Carbon::now())->sum('amount') - Discount::whereMonth('date',Carbon::now())->sum('amount'),
                    'credit' => Invoice::whereMonth('date',Carbon::now())->sum('total_price') - Voucher::whereMonth('date',Carbon::now())->sum('amount'),
                    'branches' => Branch::count(),
                    'invoiced_sale' => DB::select("call invoiced_sale"),
                    'stores' => Store::count(),
                    'invoiced_purchase' => DB::select("call invoiced_purchase"),
                ],
                // 'store_name' => Store::where('id',$id)->first()->name,
                'yearly_sales' =>  DB::select("call yearly_sales"),
                'monthly_sales' => DB::select("call monthly_sales"),
                'stores' => DB::select("call stores"),
                'top_store' => DB::select('call heigh_sale_store')
                // 'yearly_deliverables' => DB::select("call store_yearly_deliverables($id)"),
                // 'monthly_deliverables' => DB::select("call store_monthly_deliverables($id)"),
            ]);
            } catch (\Throwable $th) {
                return  response()->json($th->getMessage());
        
            }
    }
    
}
