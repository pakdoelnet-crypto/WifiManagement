<?php

namespace App\Http\Controllers;

use App\Models\WhatsappSetting;
use App\Models\WhatsappLog;
use App\Models\Customer;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WhatsappCenterController extends Controller
{
    public function index()
    {
        $settings = WhatsappSetting::all()->pluck('value', 'key');
        $logs = WhatsappLog::latest()->limit(100)->get();
        $customersCount = Customer::count();

        // Default setting values if not set
        $defaultKeys = [
            'reminder_h_minus_3_active' => '1',
            'reminder_h_plus_1_active' => '1',
            'reminder_template' => "Halo Kak {name},\n\nTagihan internet Anda sebesar {amount} untuk periode {period} akan jatuh tempo pada {due_date}.\n\nLink Nota: {link}\n\nTerima kasih.",
            'broadcast_template' => "Info Pelanggan PAK DOEL NET:\n\n{message}",
        ];

        foreach ($defaultKeys as $key => $val) {
            if (!isset($settings[$key])) {
                $settings[$key] = $val;
            }
        }

        return Inertia::render('Whatsapp/Index', [
            'settings' => $settings,
            'logs' => $logs,
            'customersCount' => $customersCount,
        ]);
    }

    public function updateSettings(Request $request)
    {
        $settings = $request->validate([
            'reminder_h_minus_3_active' => 'required|string',
            'reminder_h_plus_1_active' => 'required|string',
            'reminder_template' => 'required|string',
            'broadcast_template' => 'required|string',
        ]);

        foreach ($settings as $key => $val) {
            WhatsappSetting::updateOrCreate(
                ['key' => $key],
                ['value' => $val]
            );
        }

        return redirect()->back()->with('success', 'Setelan WhatsApp berhasil disimpan.');
    }

    public function sendManual(Request $request)
    {
        $validated = $request->validate([
            'phone' => 'required|string',
            'message' => 'required|string',
        ]);

        // Record Log
        WhatsappLog::create([
            'phone_number' => $validated['phone'],
            'message' => $validated['message'],
            'status' => 'sent',
            'type' => 'manual',
            'created_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Pesan uji coba berhasil direkam di logs.');
    }

    public function broadcast(Request $request)
    {
        $validated = $request->validate([
            'message' => 'required|string',
        ]);

        $customers = Customer::where('status', 'active')->get();
        
        foreach ($customers as $customer) {
            $msg = str_replace('{message}', $validated['message'], $request->input('template', '{message}'));
            $msg = str_replace('{name}', $customer->name, $msg);
            
            WhatsappLog::create([
                'phone_number' => $customer->whatsapp,
                'message' => $msg,
                'status' => 'sent',
                'type' => 'broadcast',
                'created_at' => now(),
            ]);
        }

        return redirect()->back()->with('success', 'Broadcast berhasil disebarkan ke ' . $customers->count() . ' pelanggan.');
    }
}
