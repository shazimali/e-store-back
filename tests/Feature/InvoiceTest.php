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

    public function test_fbr_job_is_dispatched_when_fbr_enabled()
    {
        // Fake the Queue
        \Illuminate\Support\Facades\Queue::fake();

        // Enable FBR
        config(['services.fbr.enabled' => true]);

        $store = $this->createStore('STORE-F1');
        $branch = $this->createBranch($store, 'BR-F1');
        $product = Product::create([
            'name' => 'FBR Product',
            'sku' => 'SKU-FBR',
            'code' => 'CODE-FBR',
            'store_id' => $store->id,
            'sale_price' => 100.00,
            'status' => 'ACTIVE'
        ]);

        $service = app(\App\Services\InvoiceService::class);
        $invoice = $service->store([
            'store_id' => $store->id,
            'branch_id' => $branch->id,
            'total_qty' => 1,
            'total_price' => 100.00,
            'remarks' => 'FBR test enabled',
            'date' => '2026-07-18',
            'sr_number' => 'SR-FBR-01'
        ], [
            [
                'id' => $product->id,
                'qty' => 1,
                'price' => 100.00,
                'sale_tax' => 17,
                'ext_tax' => 2
            ]
        ]);

        // Assert FBR job was dispatched to queue
        \Illuminate\Support\Facades\Queue::assertPushed(\App\Jobs\Fbr\SubmitSaleInvoiceToFbrJob::class, function ($job) use ($invoice) {
            return $job->invoice->id === $invoice->id;
        });
    }

    public function test_fbr_status_set_to_not_applicable_when_fbr_disabled()
    {
        // Disable FBR
        config(['services.fbr.enabled' => false]);

        $store = $this->createStore('STORE-F2');
        $branch = $this->createBranch($store, 'BR-F2');
        $product = Product::create([
            'name' => 'FBR Product 2',
            'sku' => 'SKU-FBR2',
            'code' => 'CODE-FBR2',
            'store_id' => $store->id,
            'sale_price' => 100.00,
            'status' => 'ACTIVE'
        ]);

        $service = app(\App\Services\InvoiceService::class);
        $invoice = $service->store([
            'store_id' => $store->id,
            'branch_id' => $branch->id,
            'total_qty' => 1,
            'total_price' => 100.00,
            'remarks' => 'FBR test disabled',
            'date' => '2026-07-18',
            'sr_number' => 'SR-FBR-02'
        ], [
            [
                'id' => $product->id,
                'qty' => 1,
                'price' => 100.00,
                'sale_tax' => 17,
                'ext_tax' => 2
            ]
        ]);

        // Assert status was set directly to 'not_applicable' by listener
        $this->assertEquals('not_applicable', $invoice->fresh()->fbr_status);
    }


    public function test_fbr_job_success_updates_invoice_to_submitted()
    {
        // Fake the queue so storing the invoice does not trigger background jobs synchronously
        \Illuminate\Support\Facades\Queue::fake();

        // Fake outgoing FBR API requests
        \Illuminate\Support\Facades\Http::fake([
            'https://gw.fbr.gov.pk/*' => \Illuminate\Support\Facades\Http::response([
                'StatusCode' => '00',
                'Result' => 'FBR-IRN-12345678',
                'ErrorMessage' => null
            ], 200)
        ]);

        $store = $this->createStore('STORE-F3');
        $branch = $this->createBranch($store, 'BR-F3');
        $product = Product::create([
            'name' => 'FBR Product 3',
            'sku' => 'SKU-F3',
            'code' => 'CODE-F3',
            'store_id' => $store->id,
            'sale_price' => 100.00,
            'status' => 'ACTIVE'
        ]);

        // Create the invoice
        $service = app(\App\Services\InvoiceService::class);
        $invoice = $service->store([
            'store_id' => $store->id,
            'branch_id' => $branch->id,
            'total_qty' => 1,
            'total_price' => 100.00,
            'remarks' => 'FBR test success',
            'date' => '2026-07-18',
            'sr_number' => 'SR-FBR-03'
        ], [
            [
                'id' => $product->id,
                'qty' => 1,
                'price' => 100.00,
                'sale_tax' => 17,
                'ext_tax' => 2
            ]
        ]);

        // Configure FBR settings
        config(['services.fbr.enabled' => true]);
        config(['services.fbr.post_url' => 'https://gw.fbr.gov.pk/di_data/v1/di/postinvoicedata_sb']);

        // Execute the job handler manually
        $job = new \App\Jobs\Fbr\SubmitSaleInvoiceToFbrJob($invoice);
        app()->call([$job, 'handle']);

        // Assert database updates
        $invoice = $invoice->fresh();
        $this->assertEquals('submitted', $invoice->fbr_status);
        $this->assertEquals('FBR-IRN-12345678', $invoice->fbr_irn);
    }

    public function test_fbr_job_transient_failure_throws_exception_to_trigger_retry()
    {
        \Illuminate\Support\Facades\Queue::fake();

        // Fake a connection/timeout error response
        \Illuminate\Support\Facades\Http::fake([
            'https://gw.fbr.gov.pk/*' => \Illuminate\Support\Facades\Http::response([], 500)
        ]);

        $store = $this->createStore('STORE-F4');
        $branch = $this->createBranch($store, 'BR-F4');
        $product = Product::create([
            'name' => 'FBR Product 4',
            'sku' => 'SKU-F4',
            'code' => 'CODE-F4',
            'store_id' => $store->id,
            'sale_price' => 100.00,
            'status' => 'ACTIVE'
        ]);

        $service = app(\App\Services\InvoiceService::class);
        $invoice = $service->store([
            'store_id' => $store->id,
            'branch_id' => $branch->id,
            'total_qty' => 1,
            'total_price' => 100.00,
            'remarks' => 'FBR test transient',
            'date' => '2026-07-18',
            'sr_number' => 'SR-FBR-04'
        ], [
            [
                'id' => $product->id,
                'qty' => 1,
                'price' => 100.00,
                'sale_tax' => 17,
                'ext_tax' => 2
            ]
        ]);

        config(['services.fbr.enabled' => true]);
        config(['services.fbr.post_url' => 'https://gw.fbr.gov.pk/di_data/v1/di/postinvoicedata_sb']);

        $job = new \App\Jobs\Fbr\SubmitSaleInvoiceToFbrJob($invoice);

        // Assert that the job throws an exception (which signals the queue worker to retry)
        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/FBR Connection Failure/i');

        app()->call([$job, 'handle']);
    }

    public function test_fbr_job_permanent_validation_failure_does_not_retry()
    {
        \Illuminate\Support\Facades\Queue::fake();

        // Fake a 200 response containing validation rejected status '99'
        \Illuminate\Support\Facades\Http::fake([
            'https://gw.fbr.gov.pk/*' => \Illuminate\Support\Facades\Http::response([
                'StatusCode' => '99',
                'Result' => null,
                'ErrorMessage' => 'Invalid NTN or business registration code'
            ], 200)
        ]);

        $store = $this->createStore('STORE-F5');
        $branch = $this->createBranch($store, 'BR-F5');
        $product = Product::create([
            'name' => 'FBR Product 5',
            'sku' => 'SKU-F5',
            'code' => 'CODE-F5',
            'store_id' => $store->id,
            'sale_price' => 100.00,
            'status' => 'ACTIVE'
        ]);

        $service = app(\App\Services\InvoiceService::class);
        $invoice = $service->store([
            'store_id' => $store->id,
            'branch_id' => $branch->id,
            'total_qty' => 1,
            'total_price' => 100.00,
            'remarks' => 'FBR test permanent',
            'date' => '2026-07-18',
            'sr_number' => 'SR-FBR-05'
        ], [
            [
                'id' => $product->id,
                'qty' => 1,
                'price' => 100.00,
                'sale_tax' => 17,
                'ext_tax' => 2
            ]
        ]);

        config(['services.fbr.enabled' => true]);
        config(['services.fbr.post_url' => 'https://gw.fbr.gov.pk/di_data/v1/di/postinvoicedata_sb']);

        $job = new \App\Jobs\Fbr\SubmitSaleInvoiceToFbrJob($invoice);

        // Execute the job. It should NOT throw an exception because it is a permanent error.
        app()->call([$job, 'handle']);

        // Assert database updates status to failed, but doesn't crash the queue worker
        $invoice = $invoice->fresh();
        $this->assertEquals('failed', $invoice->fbr_status);
        $this->assertNull($invoice->fbr_irn);
    }
}



