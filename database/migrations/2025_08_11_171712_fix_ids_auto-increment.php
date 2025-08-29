<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->integer('id')->change();
        });

        Schema::table('invoice_items', function (Blueprint $table) {
            $table->integer('id')->change();
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->ulid('id')->change();
        });
    }

    public function down()
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex(['id']);
        });
        Schema::table('payloads', function (Blueprint $table) {
            $table->dropIndex(['id']);
        });
        Schema::table('invoices_items', function (Blueprint $table) {
            $table->dropIndex(['id']);
        });
    }
};
