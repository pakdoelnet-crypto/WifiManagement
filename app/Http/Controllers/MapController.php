<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\NetworkPoint;
use App\Models\FiberRoute;
use App\Models\Router;
use App\Models\Package;
use App\Models\PppActiveSession;
use Inertia\Inertia;

class MapController extends Controller
{
    public function index()
    {
        if (!auth()->user()->hasAnyRole(['Super Admin', 'Owner', 'Admin', 'Customer Service', 'Kasir', 'Teknisi'])) {
            abort(403, 'Unauthorized access to network map.');
        }

        $customers = Customer::with('package')->get();
        $onlineUsernames = PppActiveSession::pluck('pppoe_username')->toArray();
        $networkPoints = NetworkPoint::all();
        $fiberRoutes = FiberRoute::with(['fromPoint', 'toPoint'])->get();
        
        $routers = Router::where('is_active', true)->get();
        $packages = Package::where('is_active', true)->get();

        return Inertia::render('NetworkMap/Index', [
            'customers' => $customers,
            'onlineUsernames' => $onlineUsernames,
            'networkPoints' => $networkPoints,
            'fiberRoutes' => $fiberRoutes,
            'routers' => $routers,
            'packages' => $packages,
            'canManage' => auth()->user()->hasAnyRole(['Super Admin', 'Owner', 'Admin']),
        ]);
    }

    public function getLiveStatus()
    {
        $onlineUsernames = PppActiveSession::pluck('pppoe_username')->toArray();
        $customers = Customer::select('id', 'pppoe_username', 'status')->get();
        return response()->json([
            'onlineUsernames' => $onlineUsernames,
            'customers' => $customers
        ]);
    }
}
