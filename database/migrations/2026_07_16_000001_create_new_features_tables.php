<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Expenses
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('category');
            $table->decimal('amount', 15, 2);
            $table->text('description')->nullable();
            $table->date('expense_date');
            $table->timestamps();
        });

        // 2. ODPs
        Schema::create('odps', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('capacity')->default(8);
            $table->double('lat')->nullable();
            $table->double('lng')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Add odp_id to customers
        Schema::table('customers', function (Blueprint $table) {
            $table->foreignId('odp_id')->nullable()->constrained('odps')->nullOnDelete();
        });

        // 3. Tickets
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_number')->unique();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->string('category');
            $table->string('priority')->default('medium'); // low, medium, high
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('open'); // open, diproses, selesai
            $table->timestamp('reported_at')->useCurrent();
            $table->text('notes')->nullable();
            $table->string('photo_path')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });

        // 4. Inventory Items
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('sku')->unique();
            $table->string('category'); // Router, Switch, ONU, OLT, Kabel, Adaptor, SFP, Splitter, Connector
            $table->integer('stock_qty')->default(0);
            $table->string('unit')->default('pcs');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // 5. Inventory Logs
        Schema::create('inventory_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->onDelete('cascade');
            $table->string('type'); // in, out
            $table->integer('quantity');
            $table->text('notes')->nullable();
            $table->foreignId('logged_by_user_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });

        // 6. WhatsApp Settings
        Schema::create('whatsapp_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        // 7. WhatsApp Logs
        Schema::create('whatsapp_logs', function (Blueprint $table) {
            $table->id();
            $table->string('phone_number');
            $table->text('message');
            $table->string('status')->default('sent'); // sent, failed
            $table->string('type'); // reminder_h-3, reminder_h+1, manual, broadcast
            $table->timestamp('created_at')->useCurrent();
        });

        // 8. Ping Logs
        Schema::create('ping_logs', function (Blueprint $table) {
            $table->id();
            $table->string('host');
            $table->integer('latency_ms')->nullable();
            $table->string('status')->default('green'); // green, yellow, red
            $table->timestamp('checked_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ping_logs');
        Schema::dropIfExists('whatsapp_logs');
        Schema::dropIfExists('whatsapp_settings');
        Schema::dropIfExists('inventory_logs');
        Schema::dropIfExists('inventory_items');
        Schema::dropIfExists('tickets');
        
        if (Schema::hasColumn('customers', 'odp_id')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropForeign(['odp_id']);
                $table->dropColumn('odp_id');
            });
        }
        
        Schema::dropIfExists('odps');
        Schema::dropIfExists('expenses');
    }
};
