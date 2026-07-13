<?php

namespace App\Http\Controllers;

use App\Services\NetworkPointService;
use Illuminate\Http\Request;

class NetworkPointController extends Controller
{
    protected NetworkPointService $service;

    public function __construct(NetworkPointService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        if (!auth()->user()->hasAnyRole(['Super Admin', 'Owner', 'Admin', 'Customer Service', 'Kasir', 'Teknisi'])) {
            abort(403);
        }

        $points = $this->service->getAllPoints();
        return response()->json($points);
    }

    public function store(Request $request)
    {
        if (!auth()->user()->hasAnyRole(['Super Admin', 'Owner', 'Admin'])) {
            abort(403, 'Unauthorized to manage infrastructure.');
        }

        $validated = $request->validate([
            'type' => ['required', 'in:odc,odp,tiang,htb'],
            'name' => ['required', 'string', 'max:255'],
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
            'capacity' => ['nullable', 'integer', 'min:0'],
            'radius_meters' => ['nullable', 'integer', 'min:0'],
            'parent_id' => ['nullable', 'exists:network_points,id'],
            'notes' => ['nullable', 'string'],
        ]);

        $point = $this->service->createPoint($validated);

        return response()->json([
            'success' => true,
            'message' => 'Titik infrastruktur berhasil ditambahkan.',
            'point' => $point
        ]);
    }

    public function update(Request $request, $id)
    {
        if (!auth()->user()->hasAnyRole(['Super Admin', 'Owner', 'Admin'])) {
            abort(403, 'Unauthorized to manage infrastructure.');
        }

        $validated = $request->validate([
            'type' => ['required', 'in:odc,odp,tiang,htb'],
            'name' => ['required', 'string', 'max:255'],
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
            'capacity' => ['nullable', 'integer', 'min:0'],
            'radius_meters' => ['nullable', 'integer', 'min:0'],
            'parent_id' => ['nullable', 'exists:network_points,id'],
            'notes' => ['nullable', 'string'],
        ]);

        $point = $this->service->updatePoint($id, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Titik infrastruktur berhasil diperbarui.',
            'point' => $point
        ]);
    }

    public function destroy($id)
    {
        if (!auth()->user()->hasAnyRole(['Super Admin', 'Owner', 'Admin'])) {
            abort(403, 'Unauthorized to manage infrastructure.');
        }

        $this->service->deletePoint($id);

        return response()->json([
            'success' => true,
            'message' => 'Titik infrastruktur berhasil dihapus.'
        ]);
    }
}
