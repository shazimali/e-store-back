<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Discount;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Store;
use App\Models\Voucher;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        try {

            return  response()->json([
                'total_states' => [
                    'discounts' => DB::select('call invoiced_discounts'),
                    'sales' => DB::select("call invoiced_sale"),
                    'sale_without_tax' => $this->invoicedSales(),
                    'purchases' => $this->invoicedPurchase(), //DB::select("call invoiced_purchase"),
                    'profit' => $this->invoicedSales() -  $this->invoicedPurchase(),
                    'branches' => Branch::count(),
                    'stores' => Store::count()
                ],
                'yearly_states' => [
                    'sales' => DB::select('call current_year_invoiced_sales'),
                    'sale_without_tax' => $this->currentYearInvoicedSales(),
                    'purchases' => $this->currentYearInvoicedPurchase(), // DB::select('call current_year_invoiced_purchases'),
                    'profit' => $this->currentYearInvoicedSales() -  $this->currentYearInvoicedPurchase(),
                    'discounts' => DB::select('call current_year_invoiced_discounts'),
                    'branches' => Branch::count(),
                    'stores' => Store::count(),
                ],
                'monthly_states' => [
                    'sales' =>  DB::select('call current_month_invoiced_sales'),
                    'sale_without_tax' => $this->currentMonthInvoicedSales(),
                    'purchases' => $this->currentMonthInvoicedPurchase(), //DB::select('call current_month_invoiced_purchases'),
                    'profit' => $this->currentMonthInvoicedSales() -  $this->currentMonthInvoicedPurchase(),
                    'discounts' => DB::select('call current_month_invoiced_discounts'),
                    'branches' => Branch::count(),
                    'stores' => Store::count(),
                ],
                'last_month_states' => [
                    'sales' =>  DB::select('call last_month_invoiced_sales'),
                    'sale_without_tax' => $this->lastMonthInvoicedSales(),
                    'purchases' => $this->lastMonthInvoicedPurchase(), //DB::select('call current_month_invoiced_purchases'),
                    'profit' => $this->lastMonthInvoicedSales() -  $this->lastMonthInvoicedPurchase(),
                    'discounts' => DB::select('call last_month_invoiced_discounts'),
                    'branches' => Branch::count(),
                    'stores' => Store::count(),
                ],
                'yearly_sales' =>  DB::select("call yearly_sales"),
                'monthly_sales' => DB::select("call monthly_sales"),
                'stores' => DB::select("call stores"),
                'top_store' => DB::select('call heigh_sale_store'),
                'current_last_month_stores_sale' =>  DB::select('call current_last_month_stores_sale'),
                'top_last_month_store' => DB::select('call hiegh_sale_last_month_store')
            ]);
        } catch (\Throwable $th) {
            return  response()->json($th->getMessage());
        }
    }

    public function invoicedPurchase()
    {
        //fetch all related invoices 
        $invoices_query =  DB::table('invoices')->get();

        //fetch invoiced products ID's
        $products_query  = DB::table('invoice_details')->whereIn('invoice_id', $invoices_query->pluck('id'));
        if (request('product_id')) {
            $products_query->when(request('product_id'), function ($q) {
                return $q->where('product_id', request('product_id'));
            });
        }
        //fetch discounts details
        $invoiceIDs = $invoices_query->pluck('id');
        //fetch products details

        $products = Product::whereIn('id', $products_query->pluck('product_id'))
            ->with(['invoices' => function ($q) use ($invoiceIDs) {
                $q->whereIn('invoice_id', $invoiceIDs);
            }])->with('purchases', 'returnInvoices')
            ->get();

        $purchases = 0;
        foreach ($products as $key => $pr_item) {
            $mean_price = $pr_item->purchases->sum('price') / $pr_item->purchases->count();
            $purchases += $pr_item->invoices->sum('qty') * $mean_price;
            if ($pr_item->returnInvoices->count()) {
                $purchases -= $pr_item->returnInvoices->sum('qty') * $mean_price;
            }
        }

        return $purchases;
    }

    public function currentYearInvoicedPurchase()
    {
        //fetch all related invoices 
        $invoices_query =  DB::table('invoices')->whereYear('date', Carbon::now()->year)->get();

        //fetch invoiced products ID's
        $products_query  = DB::table('invoice_details')->whereIn('invoice_id', $invoices_query->pluck('id'));
        if (request('product_id')) {
            $products_query->when(request('product_id'), function ($q) {
                return $q->where('product_id', request('product_id'));
            });
        }
        //fetch discounts details
        $invoiceIDs = $invoices_query->pluck('id');
        //fetch products details

        $products = Product::whereIn('id', $products_query->pluck('product_id'))
            ->with(['invoices' => function ($q) use ($invoiceIDs) {
                $q->whereIn('invoice_id', $invoiceIDs);
            }])->with(['returnInvoices' => function ($q) {
                $q->whereYear('date', Carbon::now()->year);
            }])->with('purchases')
            ->get();

        $purchases = 0;
        foreach ($products as $key => $pr_item) {
            $mean_price = $pr_item->purchases->sum('price') / $pr_item->purchases->count();
            $purchases += $pr_item->invoices->sum('qty') * $mean_price;
            if ($pr_item->returnInvoices->count()) {
                $purchases -= $pr_item->returnInvoices->sum('qty') * $mean_price;
            }
        }

        return $purchases;
    }

    public function currentMonthInvoicedPurchase()
    {
        //fetch all related invoices 
        $invoices_query =  DB::table('invoices')
            ->whereMonth('date', Carbon::now()->month)
            ->whereYear('date', Carbon::now()->year)
            ->get();

        //fetch invoiced products ID's
        $products_query  = DB::table('invoice_details')->whereIn('invoice_id', $invoices_query->pluck('id'));
        if (request('product_id')) {
            $products_query->when(request('product_id'), function ($q) {
                return $q->where('product_id', request('product_id'));
            });
        }
        //fetch discounts details
        $invoiceIDs = $invoices_query->pluck('id');
        //fetch products details

        $products = Product::whereIn('id', $products_query->pluck('product_id'))
            ->with(['invoices' => function ($q) use ($invoiceIDs) {
                $q->whereIn('invoice_id', $invoiceIDs);
            }])->with(['returnInvoices' => function ($q) {
                $q->whereMonth('date', Carbon::now()->month);
            }])->with('purchases')
            ->get();

        $purchases = 0;
        foreach ($products as $key => $pr_item) {
            $mean_price = $pr_item->purchases->sum('price') / $pr_item->purchases->count();
            $purchases += $pr_item->invoices->sum('qty') * $mean_price;
            if ($pr_item->returnInvoices->count()) {
                $purchases -= $pr_item->returnInvoices->sum('qty') * $mean_price;
            }
        }

        return $purchases;
    }

    public function lastMonthInvoicedPurchase()
    {
        //fetch all related invoices 
        $invoices_query =  DB::table('invoices')
            ->whereYear('created_at', Carbon::now()->subMonth()->year)
            ->whereMonth('created_at', Carbon::now()->subMonth()->month)
            ->get();

        //fetch invoiced products ID's
        $products_query  = DB::table('invoice_details')->whereIn('invoice_id', $invoices_query->pluck('id'));
        if (request('product_id')) {
            $products_query->when(request('product_id'), function ($q) {
                return $q->where('product_id', request('product_id'));
            });
        }
        //fetch discounts details
        $invoiceIDs = $invoices_query->pluck('id');
        //fetch products details

        $products = Product::whereIn('id', $products_query->pluck('product_id'))
            ->with(['invoices' => function ($q) use ($invoiceIDs) {
                $q->whereIn('invoice_id', $invoiceIDs);
            }])->with(['returnInvoices' => function ($q) {
                $q->whereMonth('date', Carbon::now()->subMonth()->month);
            }])->with('purchases')
            ->get();

        $purchases = 0;
        foreach ($products as $key => $pr_item) {
            $mean_price = $pr_item->purchases->sum('price') / $pr_item->purchases->count();
            $purchases += $pr_item->invoices->sum('qty') * $mean_price;
            if ($pr_item->returnInvoices->count()) {
                $purchases -= $pr_item->returnInvoices->sum('qty') * $mean_price;
            }
        }

        return $purchases;
    }


    public function invoicedSales()
    {
        //fetch all related invoices 
        $invoices_query =  DB::table('invoices')->get();

        //fetch invoiced products ID's
        $products_query  = DB::table('invoice_details')->whereIn('invoice_id', $invoices_query->pluck('id'));
        if (request('product_id')) {
            $products_query->when(request('product_id'), function ($q) {
                return $q->where('product_id', request('product_id'));
            });
        }
        //fetch discounts details
        $invoiceIDs = $invoices_query->pluck('id');
        //fetch products details

        $products = Product::whereIn('id', $products_query->pluck('product_id'))
            ->with(['invoices' => function ($q) use ($invoiceIDs) {
                $q->whereIn('invoice_id', $invoiceIDs);
            }])->with('purchases', 'returnInvoices')
            ->get();

        $sales = 0;
        foreach ($products as $key => $pr_item) {
            $mean_price = $pr_item->invoices->sum('price') / $pr_item->invoices->count();
            $sales += $pr_item->invoices->sum('qty') * $mean_price;
            if ($pr_item->returnInvoices->count()) {
                $sales -= $pr_item->returnInvoices->sum('qty') * $mean_price;
            }
        }

        return $sales;
    }

    public function currentYearInvoicedSales()
    {
        //fetch all related invoices 
        $invoices_query =  DB::table('invoices')->whereYear('date', Carbon::now()->year)->get();

        //fetch invoiced products ID's
        $products_query  = DB::table('invoice_details')->whereIn('invoice_id', $invoices_query->pluck('id'));
        if (request('product_id')) {
            $products_query->when(request('product_id'), function ($q) {
                return $q->where('product_id', request('product_id'));
            });
        }
        //fetch discounts details
        $invoiceIDs = $invoices_query->pluck('id');
        //fetch products details

        $products = Product::whereIn('id', $products_query->pluck('product_id'))
            ->with(['invoices' => function ($q) use ($invoiceIDs) {
                $q->whereIn('invoice_id', $invoiceIDs);
            }])->with(['returnInvoices' => function ($q) {
                $q->whereYear('date', Carbon::now()->year);
            }])->with('purchases')
            ->get();

        $sales = 0;
        foreach ($products as $key => $pr_item) {
            $mean_price = $pr_item->invoices->sum('price') / $pr_item->invoices->count();
            $sales += $pr_item->invoices->sum('qty') * $mean_price;
            if ($pr_item->returnInvoices->count()) {
                $sales -= $pr_item->returnInvoices->sum('qty') * $mean_price;
            }
        }

        return $sales;
    }

    public function currentMonthInvoicedSales()
    {
        //fetch all related invoices 
        $invoices_query =  DB::table('invoices')
            ->whereMonth('date', Carbon::now()->month)
            ->whereYear('date', Carbon::now()->year)
            ->get();

        //fetch invoiced products ID's
        $products_query  = DB::table('invoice_details')->whereIn('invoice_id', $invoices_query->pluck('id'));
        if (request('product_id')) {
            $products_query->when(request('product_id'), function ($q) {
                return $q->where('product_id', request('product_id'));
            });
        }
        //fetch discounts details
        $invoiceIDs = $invoices_query->pluck('id');
        //fetch products details

        $products = Product::whereIn('id', $products_query->pluck('product_id'))
            ->with(['invoices' => function ($q) use ($invoiceIDs) {
                $q->whereIn('invoice_id', $invoiceIDs);
            }])->with(['returnInvoices' => function ($q) {
                $q->whereMonth('date', Carbon::now()->month);
            }])->with('purchases')
            ->get();

        $sales = 0;
        foreach ($products as $key => $pr_item) {
            $mean_price = $pr_item->invoices->sum('price') / $pr_item->invoices->count();
            $sales += $pr_item->invoices->sum('qty') * $mean_price;
            if ($pr_item->returnInvoices->count()) {
                $sales -= $pr_item->returnInvoices->sum('qty') * $mean_price;
            }
        }

        return $sales;
    }

    public function lastMonthInvoicedSales()
    {
        //fetch all related invoices 
        $invoices_query =  DB::table('invoices')
            ->whereYear('created_at', Carbon::now()->subMonth()->year)
            ->whereMonth('created_at', Carbon::now()->subMonth()->month)
            ->get();

        //fetch invoiced products ID's
        $products_query  = DB::table('invoice_details')->whereIn('invoice_id', $invoices_query->pluck('id'));
        if (request('product_id')) {
            $products_query->when(request('product_id'), function ($q) {
                return $q->where('product_id', request('product_id'));
            });
        }
        //fetch discounts details
        $invoiceIDs = $invoices_query->pluck('id');
        //fetch products details

        $products = Product::whereIn('id', $products_query->pluck('product_id'))
            ->with(['invoices' => function ($q) use ($invoiceIDs) {
                $q->whereIn('invoice_id', $invoiceIDs);
            }])->with(['returnInvoices' => function ($q) {
                $q->whereMonth('date', Carbon::now()->subMonth()->month);
            }])->with('purchases')
            ->get();

        $sales = 0;
        foreach ($products as $key => $pr_item) {
            $mean_price = $pr_item->invoices->sum('price') / $pr_item->invoices->count();
            $sales += $pr_item->invoices->sum('qty') * $mean_price;
            if ($pr_item->returnInvoices->count()) {
                $sales -= $pr_item->returnInvoices->sum('qty') * $mean_price;
            }
        }

        return $sales;
    }
}
