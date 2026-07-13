<?php

namespace App\Http\Controllers;

use App\Services\FiberRouteService;
use Illuminate\Http\Request;

class FiberRouteController extends Controller
{
    protected FiberRouteService $service;

    public function __construct(FiberRouteService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        if (!auth()->user()->hasAnyRole(['Super Admin', 'Owner', 'Admin', 'Customer Service', 'Kasir', 'Teknisi'])) {
            abort(403);
        }

        $routes = $this->service->getAllRoutes();
        return response()->json($routes);
    }

    public function store(Request $request)
    {
        if (!auth()->user()->hasAnyRole(['Super Admin', 'Owner', 'Admin'])) {
            abort(403, 'Unauthorized to manage cable routes.');
        }

        $validated = $request->validate([
            'from_point_id' => ['required', 'exists:network_points,id'],
            'to_point_id' => ['required', 'exists:network_points,id', 'different:from_point_id'],
            'path_geojson' => ['nullable', 'array'],
            'length_meters' => ['nullable', 'integer', 'min:0'],
        ]);

        $route = $this->service->createRoute($validated);

        return response()->json([
            'success' => true,
            'message' => 'Jalur fiber optic berhasil ditambahkan.',
            'route' => $route
        ]);
    }

    public function update(Request $request, $id)
    {
        if (!auth()->user()->hasAnyRole(['Super Admin', 'Owner', 'Admin'])) {
            abort(403, 'Unauthorized to manage cable routes.');
        }

        $validated = $request->validate([
            'from_point_id' => ['required', 'exists:network_points,id'],
            'to_point_id' => ['required', 'exists:network_points,id', 'different:from_point_id'],
            'path_geojson' => ['nullable', 'array'],
            'length_meters' => ['nullable', 'integer', 'min:0'],
        ]);

        $route = $this->service->updateRoute($id, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Jalur fiber optic berhasil diperbarui.',
            'route' => $route
        ]);
    }

    public function destroy($id)
    {
        if (!auth()->user()->hasAnyRole(['Super Admin', 'Owner', 'Admin'])) {
            abort(403, 'Unauthorized to manage cable routes.');
        }

        $this->service->deleteRoute($id);

        return response()->json([
            'success' => true,
            'message' => 'Jalur fiber optic berhasil dihapus.'
        ]);
    }
}
