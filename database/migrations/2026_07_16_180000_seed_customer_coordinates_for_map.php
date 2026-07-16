<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Customer;
use App\Models\NetworkPoint;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $odps = NetworkPoint::all();
        $customers = Customer::whereNull('lat')->orWhereNull('lng')->get();

        if ($odps->isEmpty()) {
            return;
        }

        foreach ($customers as $idx => $cust) {
            $odp = $odps[$idx % $odps->count()];
            
            // Random offset ~15 to 45 meters
            $latOffset = (rand(-250, 250) / 1000000);
            $lngOffset = (rand(-250, 250) / 1000000);

            $cust->update([
                'lat' => $odp->lat + $latOffset,
                'lng' => $odp->lng + $lngOffset
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op
    }
};
