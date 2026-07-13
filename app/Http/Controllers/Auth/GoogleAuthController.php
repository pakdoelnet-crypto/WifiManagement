<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Spatie\Permission\Models\Role;

class GoogleAuthController extends Controller
{
    /**
     * Redirect the user to the Google authentication page.
     */
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Obtain the user information from Google.
     */
    public function handleGoogleCallback()
    {
        try {
            $socialUser = Socialite::driver('google')->user();
        } catch (\Exception $e) {
            return redirect()->route('login')->withErrors([
                'email' => 'Gagal melakukan otentikasi dengan Google. Silakan coba kembali.',
            ]);
        }

        // Check if user with same email exists
        $user = User::where('email', $socialUser->getEmail())->first();

        if ($user) {
            // Update google_id if not present
            if (empty($user->google_id)) {
                $user->update([
                    'google_id' => $socialUser->getId(),
                ]);
            }

            // Auto-assign Super Admin for pakdoelnet@gmail.com
            if ($user->email === 'pakdoelnet@gmail.com' && !$user->hasRole('Super Admin')) {
                $user->syncRoles(['Super Admin']);
            }
        } else {
            // Create user and auto-assign role
            $user = User::create([
                'name' => $socialUser->getName(),
                'email' => $socialUser->getEmail(),
                'password' => bcrypt(Str::random(24)),
                'google_id' => $socialUser->getId(),
            ]);

            // Assign Super Admin for pakdoelnet@gmail.com, otherwise CS
            if ($user->email === 'pakdoelnet@gmail.com') {
                $user->assignRole('Super Admin');
            } else {
                if (Role::where('name', 'Customer Service')->exists()) {
                    $user->assignRole('Customer Service');
                }
            }
        }

        // Check active status
        if (isset($user->is_active) && !$user->is_active) {
            return redirect()->route('login')->withErrors([
                'email' => 'Akun Anda dinonaktifkan. Silakan hubungi admin.',
            ]);
        }

        // Login user
        Auth::login($user, true);

        return redirect()->intended('/dashboard');
    }
}
