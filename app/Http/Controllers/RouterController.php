<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Router;
use App\Services\RouterService;
use App\Http\Requests\StoreRouterRequest;
use App\Http\Requests\UpdateRouterRequest;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class RouterController extends Controller
{
    protected RouterService $routerService;

    public function __construct(RouterService $routerService)
    {
        $this->routerService = $routerService;
    }

    public function index()
    {
        Gate::authorize('viewAny', Router::class);

        $routers = $this->routerService->getAllRouters();

        return Inertia::render('Routers/Index', [
            'routers' => $routers,
            'canManage' => auth()->user()->hasAnyRole(['Super Admin', 'Owner']),
        ]);
    }

    public function store(StoreRouterRequest $request)
    {
        Gate::authorize('create', Router::class);

        $this->routerService->createRouter($request->validated());

        return redirect()->route('routers.index')->with('success', 'Router berhasil ditambahkan.');
    }

    public function update(UpdateRouterRequest $request, $id)
    {
        $router = $this->routerService->getRouterById($id);
        Gate::authorize('update', $router);

        $this->routerService->updateRouter($id, $request->validated());

        return redirect()->route('routers.index')->with('success', 'Router berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $router = $this->routerService->getRouterById($id);
        Gate::authorize('delete', $router);

        $this->routerService->deleteRouter($id);

        return redirect()->route('routers.index')->with('success', 'Router berhasil dihapus.');
    }

    public function testConnection($id)
    {
        $router = $this->routerService->getRouterById($id);
        Gate::authorize('update', $router);

        $result = $this->routerService->testConnection($id);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => 'Koneksi ke router berhasil!',
                'data' => $result['data']
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Gagal terhubung ke router: ' . $result['error']
        ], 400);
    }
}
