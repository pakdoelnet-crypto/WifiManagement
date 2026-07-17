<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $tenant = null;
        $host = $request->getHost();
        
        // Simple subdomain check (excluding IP addresses and www)
        if (!filter_var($host, FILTER_VALIDATE_IP) && count(explode('.', $host)) > 2) {
            $subdomain = explode('.', $host)[0];
            if ($subdomain !== 'www') {
                $tenant = \App\Models\Tenant::where('subdomain', $subdomain)->first();
            }
        }
        
        // Fallback to authenticated user's tenant
        if (!$tenant && $request->user()) {
            $tenant = $request->user()->tenant;
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user() ? array_merge($request->user()->toArray(), [
                    'roles' => $request->user()->getRoleNames(),
                    'permissions' => $request->user()->getAllPermissions()->pluck('name'),
                    'tenant_name' => $tenant ? $tenant->nama_usaha : 'PAK DOEL NET',
                ]) : null,
            ],
            'tenant' => $tenant ? [
                'nama_usaha' => $tenant->nama_usaha,
                'subdomain' => $tenant->subdomain,
                'logo_url' => $tenant->logo_url,
            ] : null,
            'appVersion' => config('app.version', '1.0.0'),
        ];
    }
}
