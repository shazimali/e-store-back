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
            $table->string('fbr_usin')->nullable()->after('invoice_id');
            $table->string('fbr_irn')->nullable()->after('fbr_usin');
            $table->tinyInteger('fbr_invoice_type')->default(2)->after('fbr_irn'); // 2 = Sale, 4 = Credit Note
            $table->string('fbr_ref_usin')->nullable()->after('fbr_invoice_type');
            $table->string('fbr_status')->default('pending')->after('fbr_ref_usin'); // 'pending' | 'submitted' | 'failed' | 'not_applicable'
            $table->json('fbr_response')->nullable()->after('fbr_status');
            $table->timestamp('fbr_submitted_at')->nullable()->after('fbr_response');
            $table->boolean('is_cancelled')->default(false)->after('fbr_submitted_at');
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
            $table->dropColumn([
                'fbr_usin',
                'fbr_irn',
                'fbr_invoice_type',
                'fbr_ref_usin',
                'fbr_status',
                'fbr_response',
                'fbr_submitted_at',
                'is_cancelled'
            ]);
        });
    }
};
