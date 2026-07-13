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
        Schema::create('fiber_routes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_point_id')->constrained('network_points')->onDelete('cascade');
            $table->foreignId('to_point_id')->constrained('network_points')->onDelete('cascade');
            $table->json('path_geojson')->nullable();
            $table->integer('length_meters')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fiber_routes');
    }
};
