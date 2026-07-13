<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\User;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Find if user with email pakdoelnet@gmail.com exists
        $user = User::where('email', 'pakdoelnet@gmail.com')->first();
        if ($user) {
            // Ensure Super Admin role exists
            if (Role::where('name', 'Super Admin')->exists()) {
                $user->syncRoles(['Super Admin']);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No action needed for rollback
    }
};
