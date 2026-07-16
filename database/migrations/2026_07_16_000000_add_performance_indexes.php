<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->index('store_id');
            $table->index('branch_id');
            $table->index('date');
        });

        Schema::table('invoice_details', function (Blueprint $table) {
            $table->index('invoice_id');
            $table->index('product_id');
        });

        Schema::table('deliverables', function (Blueprint $table) {
            $table->index('store_id');
            $table->index('branch_id');
            $table->index('invoice_id');
        });

        Schema::table('deliverable_details', function (Blueprint $table) {
            $table->index('deliverable_id');
            $table->index('product_id');
        });

        Schema::table('return_invoice_details', function (Blueprint $table) {
            $table->index('invoice_id');
            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex(['store_id']);
            $table->dropIndex(['branch_id']);
            $table->dropIndex(['date']);
        });

        Schema::table('invoice_details', function (Blueprint $table) {
            $table->dropIndex(['invoice_id']);
            $table->dropIndex(['product_id']);
        });

        Schema::table('deliverables', function (Blueprint $table) {
            $table->dropIndex(['store_id']);
            $table->dropIndex(['branch_id']);
            $table->dropIndex(['invoice_id']);
        });

        Schema::table('deliverable_details', function (Blueprint $table) {
            $table->dropIndex(['deliverable_id']);
            $table->dropIndex(['product_id']);
        });

        Schema::table('return_invoice_details', function (Blueprint $table) {
            $table->dropIndex(['invoice_id']);
            $table->dropIndex(['product_id']);
        });
    }
};
