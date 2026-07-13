<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Package;
use App\Services\PackageService;
use App\Services\RouterService;
use App\Http\Requests\StorePackageRequest;
use App\Http\Requests\UpdatePackageRequest;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class PackageController extends Controller
{
    protected PackageService $packageService;
    protected RouterService $routerService;

    public function __construct(PackageService $packageService, RouterService $routerService)
    {
        $this->packageService = $packageService;
        $this->routerService = $routerService;
    }

    public function index()
    {
        Gate::authorize('viewAny', Package::class);

        $packages = $this->packageService->getAllPackages();
        $routers = $this->routerService->getAllRouters()->where('is_active', true)->values();

        return Inertia::render('Packages/Index', [
            'packages' => $packages,
            'routers' => $routers,
            'canManage' => auth()->user()->hasAnyRole(['Super Admin', 'Owner']),
        ]);
    }

    public function store(StorePackageRequest $request)
    {
        Gate::authorize('create', Package::class);

        $this->packageService->createPackage($request->validated());

        return redirect()->route('packages.index')->with('success', 'Paket internet berhasil ditambahkan.');
    }

    public function update(UpdatePackageRequest $request, $id)
    {
        $package = $this->packageService->getPackageById($id);
        Gate::authorize('update', $package);

        $this->packageService->updatePackage($id, $request->validated());

        return redirect()->route('packages.index')->with('success', 'Paket internet berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $package = $this->packageService->getPackageById($id);
        Gate::authorize('delete', $package);

        $this->packageService->deletePackage($id);

        return redirect()->route('packages.index')->with('success', 'Paket internet berhasil dihapus.');
    }

    public function syncProfiles($routerId)
    {
        Gate::authorize('create', Package::class);

        $result = $this->packageService->getProfilesFromRouter($routerId);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'profiles' => $result['profiles']
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Gagal mensinkronisasikan profil dari MikroTik: ' . $result['error']
        ], 400);
    }
}
