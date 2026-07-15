<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CustomerAuthController extends Controller
{
    public function showLogin()
    {
        if (session()->has('customer_id')) {
            return redirect()->route('portal.dashboard');
        }
        return Inertia::render('CustomerPortal/Login');
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $customer = Customer::where('pppoe_username', $validated['username'])->first();

        if ($customer && $customer->pppoe_password === $validated['password']) {
            session(['customer_id' => $customer->id]);
            return redirect()->route('portal.dashboard');
        }

        return redirect()->back()->withErrors([
            'username' => 'Username PPPoE atau password salah.',
        ]);
    }

    public function logout()
    {
        session()->forget('customer_id');
        return redirect()->route('portal.login');
    }
}
