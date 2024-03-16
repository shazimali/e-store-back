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
        Schema::table('deliverables', function (Blueprint $table) {
            $table->string('sr_number')->unique()->after('invoice_id');
            $table->integer('branch_id')->after('store_id');
            $table->date('order_date')->nullable()->after('date');
            $table->string('invoice_prefix')->after('invoice_id');
            $table->string('invoice_sr_number')->after('invoice_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('deliverables', function (Blueprint $table) {
            $table->dropColumn(['sr_number','branch_id','order_date','invoice_prefix','invoice_sr_number']);

        });
    }
};
