<?php

namespace App\Http\Controllers;


use App\Models\Customer;
use App\Services\CustomerService;
use App\Services\RouterService;
use App\Services\PackageService;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class CustomerController extends Controller
{
    protected CustomerService $customerService;
    protected RouterService $routerService;
    protected PackageService $packageService;

    public function __construct(
        CustomerService $customerService,
        RouterService $routerService,
        PackageService $packageService
    ) {
        $this->customerService = $customerService;
        $this->routerService = $routerService;
        $this->packageService = $packageService;
    }

    public function index()
    {
        Gate::authorize('viewAny', Customer::class);

        $customers = Customer::with(['router', 'package', 'currentInvoice.payment'])->latest()->get();
        $routers = $this->routerService->getAllRouters()->where('is_active', true)->values();
        $packages = $this->packageService->getAllPackages()->where('is_active', true)->values();

        return Inertia::render('Customers/Index', [
            'customers' => $customers,
            'routers' => $routers,
            'packages' => $packages,
            'canManage' => auth()->user()->hasAnyRole(['Super Admin', 'Owner', 'Admin', 'Customer Service']),
        ]);
    }

    public function store(StoreCustomerRequest $request)
    {
        Gate::authorize('create', Customer::class);

        $this->customerService->createCustomer(
            $request->validated(),
            $request->file('ktp_photo'),
            $request->file('photo')
        );

        return redirect()->route('customers.index')->with('success', 'Pelanggan baru berhasil ditambahkan dan disinkronisasikan ke MikroTik.');
    }

    public function update(UpdateCustomerRequest $request, $id)
    {
        Gate::authorize('update', Customer::class);

        $this->customerService->updateCustomer(
            $id,
            $request->validated(),
            $request->file('ktp_photo'),
            $request->file('photo')
        );

        return redirect()->route('customers.index')->with('success', 'Data pelanggan berhasil diperbarui.');
    }

    public function destroy($id)
    {
        Gate::authorize('delete', Customer::class);

        $this->customerService->deleteCustomer($id);

        return redirect()->route('customers.index')->with('success', 'Pelanggan berhasil dihapus dari sistem dan MikroTik.');
    }

    public function toggleStatus(Request $request, $id)
    {
        Gate::authorize('update', Customer::class);

        $request->validate([
            'status' => ['required', 'in:active,isolir,suspended']
        ]);

        $this->customerService->toggleStatus($id, $request->status);

        return redirect()->route('customers.index')->with('success', 'Status pelanggan berhasil diperbarui di database dan MikroTik.');
    }

    public function syncSecrets($routerId)
    {
        Gate::authorize('create', Customer::class);

        $result = $this->customerService->getImportableSecrets($routerId);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'secrets' => $result['secrets']
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Gagal mengambil data dari MikroTik: ' . $result['error']
        ], 400);
    }

    public function import(Request $request)
    {
        Gate::authorize('create', Customer::class);

        $validated = $request->validate([
            'router_id' => ['required', 'exists:routers,id'],
            'package_id' => ['nullable', 'exists:packages,id'],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'whatsapp' => ['required', 'string', 'max:50'],
            'address' => ['required', 'string'],
            'pppoe_username' => ['required', 'string', 'max:255', 'unique:customers,pppoe_username'],
            'pppoe_password' => ['nullable', 'string', 'max:255'],
            'pppoe_secret_id' => ['required', 'string'],
            'pppoe_profile' => ['required', 'string'],
        ]);

        $this->customerService->importCustomer($validated);

        return redirect()->back()->with('success', 'Pelanggan berhasil diimport dari MikroTik.');
    }

    public function media($type, $filename)
    {
        if (!auth()->check() || !auth()->user()->hasAnyRole(['Super Admin', 'Owner', 'Admin', 'Customer Service', 'Kasir', 'Teknisi'])) {
            abort(403, 'Unauthorized access to customer media.');
        }

        if (!in_array($type, ['ktp', 'photo'])) {
            abort(404);
        }

        $path = $type . '/' . $filename;

        if (!Storage::disk('private')->exists($path)) {
            abort(404);
        }

        $file = Storage::disk('private')->get($path);
        $mime = Storage::disk('private')->mimeType($path);

        return response($file, 200)->header('Content-Type', $mime);
    }
}
