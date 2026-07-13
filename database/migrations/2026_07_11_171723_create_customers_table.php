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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('router_id')->constrained('routers')->onDelete('cascade');
            $table->foreignId('package_id')->nullable()->constrained('packages')->onDelete('set null');
            $table->string('name');
            $table->string('phone');
            $table->string('whatsapp');
            $table->string('email')->nullable();
            $table->string('ktp_number')->nullable();
            $table->string('ktp_photo_path')->nullable();
            $table->string('photo_path')->nullable();
            $table->text('address');
            $table->decimal('lat', 10, 8)->nullable();
            $table->decimal('lng', 11, 8)->nullable();
            $table->string('pppoe_username')->unique();
            $table->string('pppoe_password')->nullable(); // nullable if imported and secret password is not read or empty
            $table->string('pppoe_secret_id')->nullable();
            $table->enum('status', ['active', 'isolir', 'suspended'])->default('active');
            $table->enum('source', ['imported', 'created_by_app']);
            $table->date('joined_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
