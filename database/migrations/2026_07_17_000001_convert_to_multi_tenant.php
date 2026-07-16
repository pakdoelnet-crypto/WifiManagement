<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Create tenants table
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('nama_usaha');
            $table->string('subdomain')->unique();
            $table->string('status')->default('aktif'); // aktif, nonaktif, trial
            $table->string('paket_langganan')->nullable();
            $table->string('logo_url')->nullable();
            $table->timestamps();
        });

        // 2. Insert default tenant for existing data
        DB::table('tenants')->insert([
            'id' => 1,
            'nama_usaha' => 'PAK DOEL NET',
            'subdomain' => 'pakdoelnet',
            'status' => 'aktif',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 3. List of tables to add tenant_id to
        $tables = [
            'users',
            'routers',
            'packages',
            'customers',
            'network_points',
            'fiber_routes',
            'invoices',
            'payments',
            'expenses',
            'odps',
            'tickets',
            'inventory_items',
            'inventory_logs',
            'whatsapp_settings',
            'whatsapp_logs',
            'ping_logs',
            'audit_logs',
            'customer_session_logs',
            'ppp_active_sessions'
        ];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->foreignId('tenant_id')->default(1)->constrained('tenants')->onDelete('cascade');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = [
            'ppp_active_sessions',
            'customer_session_logs',
            'audit_logs',
            'ping_logs',
            'whatsapp_logs',
            'whatsapp_settings',
            'inventory_logs',
            'inventory_items',
            'tickets',
            'odps',
            'expenses',
            'payments',
            'invoices',
            'fiber_routes',
            'network_points',
            'customers',
            'packages',
            'routers',
            'users'
        ];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'tenant_id')) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    if (DB::getDriverName() !== 'sqlite') {
                        $table->dropForeign([$tableName . '_tenant_id_foreign']);
                    }
                    $table->dropColumn('tenant_id');
                });
            }
        }

        Schema::dropIfExists('tenants');
    }
};
