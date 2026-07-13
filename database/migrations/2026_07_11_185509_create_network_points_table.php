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
        Schema::create('network_points', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['odc', 'odp', 'tiang', 'htb']);
            $table->string('name');
            $table->decimal('lat', 10, 8);
            $table->decimal('lng', 11, 8);
            $table->integer('capacity')->nullable(); // ports count
            $table->integer('radius_meters')->nullable(); // signal / wire coverage
            $table->foreignId('parent_id')->nullable()->constrained('network_points')->onDelete('set null');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('network_points');
    }
};
