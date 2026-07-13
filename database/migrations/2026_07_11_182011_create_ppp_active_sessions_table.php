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
        Schema::create('ppp_active_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('router_id')->constrained('routers')->onDelete('cascade');
            $table->foreignId('customer_id')->nullable()->constrained('customers')->onDelete('set null');
            $table->string('pppoe_username');
            $table->string('ip_address');
            $table->string('uptime');
            $table->string('caller_id')->nullable(); // MAC address / caller id
            $table->string('interface_name')->nullable();
            $table->timestamp('last_seen_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ppp_active_sessions');
    }
};
