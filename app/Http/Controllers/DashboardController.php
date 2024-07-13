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
                    'discounts' => DB::select('call invoiced_discounts'),
                    'sales' => DB::select("call invoiced_sale"),
                    'purchases' => DB::select("call invoiced_purchase"),
                    'branches' => Branch::count(),
                    'stores' => Store::count()
                ],
                'yearly_states' => [
                    'sales' => DB::select('call current_year_invoiced_sales'), 
                    'purchases' =>  DB::select('call current_year_invoiced_purchases'),
                    'discounts' => DB::select('call current_year_invoiced_discounts'),
                    'branches' => Branch::count(),
                    'stores' => Store::count(),
                ],
                'monthly_states' => [
                    'sales' =>  DB::select('call current_month_invoiced_sales'), 
                    'purchases' => DB::select('call current_month_invoiced_purchases'),
                    'discounts' => DB::select('call current_month_invoiced_discounts'),
                    'branches' => Branch::count(),
                    'stores' => Store::count(),
                ],
                'yearly_sales' =>  DB::select("call yearly_sales"),
                'monthly_sales' => DB::select("call monthly_sales"),
                'stores' => DB::select("call stores"),
                'top_store' => DB::select('call heigh_sale_store')
            ]);
            } catch (\Throwable $th) {
                return  response()->json($th->getMessage());
        
            }
    }
    
}
