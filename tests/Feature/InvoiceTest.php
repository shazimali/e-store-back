<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\City;
use App\Models\CompanyDetail;
use App\Models\Deliverable;
use App\Models\DeliverableDetail;
use App\Models\Invoice;
use App\Models\InvoiceDetail;
use App\Models\Product;
use App\Models\Store;
use App\Repositories\InvoiceRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class InvoiceTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
        
        // Setup a default CompanyDetail for tax tests
        CompanyDetail::create([
            'company_name' => 'Everyday Store',
            'phone' => '123456',
            'address' => 'Test Address',
            'del_px' => 'DEL',
            'ext_tax' => 2,
            'sale_tax' => 17,
        ]);
    }

    private function createStore($code = 'STORE-01')
    {
        $city = new City();
        $city->title = 'Test City';
        $city->save();

        return Store::create([
            'name' => 'Test Store',
            'code' => $code,
            'city_id' => $city->id,
            'status' => 'ACTIVE'
        ]);
    }

    private function createBranch($store, $code = 'BR-01')
    {
        return Branch::create([
            'name' => 'Test Branch',
            'store_id' => $store->id,
            'code' => $code,
            'status' => 'ACTIVE',
            'city_id' => $store->city_id
        ]);
    }

    public function test_invoice_repository_paginated_list()
    {
        $store = $this->createStore('STORE-01');
        $branch = $this->createBranch($store, 'BR-01');

        Invoice::create([
            'invoice_id' => 3000,
            'sr_number' => 'SR-3000',
            'store_id' => $store->id,
            'branch_id' => $branch->id,
            'total_qty' => 5,
            'total_price' => 500.00,
            'remarks' => 'Remarks',
            'date' => '2026-07-16',
        ]);

        $repo = new InvoiceRepository();
        $list = $repo->paginatedList([], 10);

        $this->assertCount(1, $list);
        $this->assertEquals(3000, $list->first()->invoice_id);
    }

    public function test_invoice_available_quantity_calculation()
    {
        $store = $this->createStore('STORE-02');
        $branch = $this->createBranch($store, 'BR-02');
        $product = Product::create([
            'name' => 'Test Product',
            'sku' => 'SKU-001',
            'code' => 'CODE-001',
            'store_id' => $store->id,
            'sale_price' => 100.00,
            'status' => 'ACTIVE'
        ]);

        // 1. Deliver 10 products
        $deliverable = Deliverable::create([
            'store_id' => $store->id,
            'branch_id' => $branch->id,
            'total_qty' => 10,
            'date' => '2026-07-16',
            'is_return' => 0,
            'invoice_id' => 0,
            'sr_number' => 'SR-DEL-01',
            'invoice_prefix' => 'DEL',
            'invoice_sr_number' => 'DEL00001'
        ]);
        DeliverableDetail::create([
            'deliverable_id' => $deliverable->id,
            'product_id' => $product->id,
            'qty' => 10,
            'date' => '2026-07-16'
        ]);

        // 2. Invoice 3 products
        $invoice = Invoice::create([
            'invoice_id' => 3000,
            'sr_number' => 'SR-3000',
            'store_id' => $store->id,
            'branch_id' => $branch->id,
            'total_qty' => 3,
            'total_price' => 300.00,
            'remarks' => 'Remarks',
            'date' => '2026-07-16',
        ]);
        InvoiceDetail::create([
            'invoice_id' => $invoice->id,
            'product_id' => $product->id,
            'qty' => 3,
            'price' => 100.00,
            'sale_tax' => 17,
            'ext_tax' => 2,
            'date' => '2026-07-16'
        ]);

        $repo = new InvoiceRepository();
        $qtyMap = $repo->getAvailableQtyMap($branch->id);

        // Expected available = 10 (delivered) - 3 (invoiced) = 7
        $this->assertEquals(7, $qtyMap[$product->id]);
    }

    public function test_fetch_branch_products_uses_caching()
    {
        $store = $this->createStore('STORE-03');
        $branch = $this->createBranch($store, 'BR-03');
        $product = Product::create([
            'name' => 'Test Product',
            'sku' => 'SKU-001',
            'code' => 'CODE-001',
            'store_id' => $store->id,
            'sale_price' => 100.00,
            'status' => 'ACTIVE'
        ]);

        // Deliver 5
        $deliverable = Deliverable::create([
            'store_id' => $store->id,
            'branch_id' => $branch->id,
            'total_qty' => 5,
            'date' => '2026-07-16',
            'is_return' => 0,
            'invoice_id' => 0,
            'sr_number' => 'SR-DEL-02',
            'invoice_prefix' => 'DEL',
            'invoice_sr_number' => 'DEL00002'
        ]);
        DeliverableDetail::create([
            'deliverable_id' => $deliverable->id,
            'product_id' => $product->id,
            'qty' => 5,
            'date' => '2026-07-16'
        ]);

        // Clear cache first
        Cache::forget("branch.products.{$branch->id}");

        // Hit the controller method / route helper
        $response = $this->actingAs($this->createUser())->getJson("/api/invoices/fetch-branch-products/{$branch->id}");
        $response->assertOk();

        // Verify the response structure and data
        $data = $response->json();
        $this->assertCount(1, $data);
        $this->assertEquals(5, $data[0]['available_qty']);

        // Cache should now be populated
        $this->assertTrue(Cache::has("branch.products.{$branch->id}"));
    }
}
