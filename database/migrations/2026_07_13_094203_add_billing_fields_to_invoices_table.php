<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('periode')->nullable()->after('amount');
            $table->decimal('penalty_amount', 12, 2)->default(0)->after('periode');
            $table->decimal('discount_amount', 12, 2)->default(0)->after('penalty_amount');
            $table->decimal('total_amount', 12, 2)->default(0)->after('discount_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['periode', 'penalty_amount', 'discount_amount', 'total_amount']);
        });
    }
};
