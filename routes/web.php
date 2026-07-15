<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

use App\Http\Controllers\Auth\GoogleAuthController;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return redirect()->route('login');
});

Route::get('/auth/google', [GoogleAuthController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');

use App\Http\Controllers\DashboardController;

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');
Route::get('/routers/{id}/live-resources', [DashboardController::class, 'getRouterResources'])->middleware(['auth'])->name('routers.live-resources');
Route::get('/routers/{id}/interfaces', [DashboardController::class, 'getRouterInterfaces'])->middleware(['auth'])->name('routers.interfaces');
Route::get('/routers/{id}/active-traffic', [DashboardController::class, 'activeRouterTraffic'])->middleware(['auth'])->name('routers.active-traffic');

use App\Http\Controllers\RouterController;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\PppActiveSessionController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\MapController;
use App\Http\Controllers\NetworkPointController;
use App\Http\Controllers\FiberRouteController;
use App\Http\Controllers\PppSecretController;

Route::middleware('auth')->group(function () {
    // PPPoE Secret Management Routes
    Route::get('/ppp-secrets', [PppSecretController::class, 'index'])->name('ppp-secrets.index');
    Route::post('/ppp-secrets', [PppSecretController::class, 'store'])->name('ppp-secrets.store');
    Route::post('/ppp-secrets/toggle', [PppSecretController::class, 'toggle'])->name('ppp-secrets.toggle');
    Route::delete('/ppp-secrets', [PppSecretController::class, 'destroy'])->name('ppp-secrets.destroy');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Routers Management Routes
    Route::get('/routers', [RouterController::class, 'index'])->name('routers.index');
    Route::post('/routers', [RouterController::class, 'store'])->name('routers.store');
    Route::put('/routers/{id}', [RouterController::class, 'update'])->name('routers.update');
    Route::delete('/routers/{id}', [RouterController::class, 'destroy'])->name('routers.destroy');
    Route::post('/routers/{id}/test-connection', [RouterController::class, 'testConnection'])->name('routers.test-connection');

    // Packages Management Routes
    Route::get('/packages', [PackageController::class, 'index'])->name('packages.index');
    Route::post('/packages', [PackageController::class, 'store'])->name('packages.store');
    Route::put('/packages/{id}', [PackageController::class, 'update'])->name('packages.update');
    Route::delete('/packages/{id}', [PackageController::class, 'destroy'])->name('packages.destroy');
    Route::get('/packages/sync-profiles/{routerId}', [PackageController::class, 'syncProfiles'])->name('packages.sync-profiles');

    // Customers Management Routes
    Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::post('/customers', [CustomerController::class, 'store'])->name('customers.store');
    Route::post('/customers/{id}/update', [CustomerController::class, 'update'])->name('customers.update'); // using POST for file upload support in multipart/form-data requests
    Route::delete('/customers/{id}', [CustomerController::class, 'destroy'])->name('customers.destroy');
    Route::post('/customers/{id}/toggle-status', [CustomerController::class, 'toggleStatus'])->name('customers.toggle-status');
    Route::get('/customers/sync-secrets/{routerId}', [CustomerController::class, 'syncSecrets'])->name('customers.sync-secrets');
    Route::post('/customers/import', [CustomerController::class, 'import'])->name('customers.import');
    Route::get('/customers/media/{type}/{filename}', [CustomerController::class, 'media'])->name('customers.media');

    // Realtime PPPoE Active Sessions Routes
    Route::get('/online-customers', [PppActiveSessionController::class, 'index'])->name('online-customers.index');
    Route::post('/online-customers/disconnect', [PppActiveSessionController::class, 'disconnect'])->name('online-customers.disconnect');
    Route::get('/connection-history', [\App\Http\Controllers\CustomerSessionLogController::class, 'index'])->name('connection-history.index');

    // Audit Logs Routes
    Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');

    // Network Map Routes
    Route::get('/network-map', [MapController::class, 'index'])->name('network-map.index');

    // Network Points API Routes
    Route::get('/network-points', [NetworkPointController::class, 'index'])->name('network-points.index');
    Route::post('/network-points', [NetworkPointController::class, 'store'])->name('network-points.store');
    Route::post('/network-points/{id}', [NetworkPointController::class, 'update'])->name('network-points.update');
    Route::delete('/network-points/{id}', [NetworkPointController::class, 'destroy'])->name('network-points.destroy');

    // Fiber Routes API Routes
    Route::get('/fiber-routes', [FiberRouteController::class, 'index'])->name('fiber-routes.index');
    Route::post('/fiber-routes', [FiberRouteController::class, 'store'])->name('fiber-routes.store');
    Route::post('/fiber-routes/{id}', [FiberRouteController::class, 'update'])->name('fiber-routes.update');
    Route::delete('/fiber-routes/{id}', [FiberRouteController::class, 'destroy'])->name('fiber-routes.destroy');

    // Roles & Permissions & Users Management Routes (Super Admin/Owner only)
    Route::middleware('can:roles.manage')->group(function () {
        Route::get('/roles', [\App\Http\Controllers\RoleController::class, 'index'])->name('roles.index');
        Route::post('/roles', [\App\Http\Controllers\RoleController::class, 'store'])->name('roles.store');
        Route::put('/roles/{id}', [\App\Http\Controllers\RoleController::class, 'update'])->name('roles.update');
        Route::delete('/roles/{id}', [\App\Http\Controllers\RoleController::class, 'destroy'])->name('roles.destroy');
        Route::get('/roles/{id}/permissions', [\App\Http\Controllers\RoleController::class, 'getRolePermissions'])->name('roles.permissions');

        Route::get('/users', [\App\Http\Controllers\UserController::class, 'index'])->name('users.index');
        Route::post('/users', [\App\Http\Controllers\UserController::class, 'store'])->name('users.store');
        Route::put('/users/{id}', [\App\Http\Controllers\UserController::class, 'update'])->name('users.update');
        Route::post('/users/{id}/toggle-status', [\App\Http\Controllers\UserController::class, 'toggleStatus'])->name('users.toggle-status');
    });

    // Invoices Routes (can:invoices.view permission)
    // Invoices Routes (can:invoices.view permission)
    Route::middleware('can:invoices.view')->group(function () {
        Route::get('/invoices', [\App\Http\Controllers\InvoiceController::class, 'index'])->name('invoices.index');
        Route::post('/invoices/generate', [\App\Http\Controllers\InvoiceController::class, 'generate'])->name('invoices.generate');
        Route::post('/invoices/{id}/pay', [\App\Http\Controllers\InvoiceController::class, 'pay'])->name('invoices.pay');
    });
});

Route::get('/invoices/{invoice_number}/public', [\App\Http\Controllers\InvoiceController::class, 'publicView'])->name('invoices.public');

Route::post('/deploy-webhook', function (\Illuminate\Http\Request $request) {
    $token = env('DEPLOY_TOKEN');
    if (!$token || $request->header('X-Deploy-Token') !== $token) {
        return response()->json(['error' => 'Unauthorized'], 401);
    }
    $output = [];
    exec('cd /var/www/pakdoelnet && git stash 2>&1', $output);
    exec('cd /var/www/pakdoelnet && git pull 2>&1', $output);
    exec('cd /var/www/pakdoelnet && npm run build 2>&1', $output);
    return response()->json([
        'success' => true,
        'output' => $output
    ]);
});

Route::get('/debug-connection', function() {
    $routers = \App\Models\Router::all();
    $results = [];
    foreach ($routers as $r) {
        $connectionService = app(\App\Services\MikrotikConnectionService::class);
        $res = $connectionService->testConnection($r);
        $sessionsRes = $connectionService->getPppActiveSessions($r);
        
        $results[] = [
            'router_name' => $r->name,
            'ip' => $r->ip_address,
            'port' => $r->port,
            'type' => $r->connection_type,
            'is_active' => $r->is_active,
            'test_connection' => $res,
            'active_sessions_count' => $sessionsRes['success'] ? count($sessionsRes['sessions']) : $sessionsRes['error']
        ];
    }
    
    $dbSessionsCount = \App\Models\PppActiveSession::count();
    $dbCustomersCount = \App\Models\Customer::count();
    
    // Check Laravel queue jobs
    $jobsCount = 0;
    $failedJobsCount = 0;
    try {
        $jobsCount = \DB::table('jobs')->count();
        $failedJobsCount = \DB::table('failed_jobs')->count();
    } catch (\Exception $e) {
        $jobsCount = $e->getMessage();
    }

    // Running PHP processes
    $processes = [];
    exec('ps aux | grep php', $processes);

    // Check supervisor conf and status
    $supervisorConfExists = file_exists('/etc/supervisor/conf.d/pakdoelnet-scheduler.conf');
    $supervisorConf = $supervisorConfExists ? file_get_contents('/etc/supervisor/conf.d/pakdoelnet-scheduler.conf') : 'Does not exist';
    
    $supervisorStatus = [];
    exec('supervisorctl status 2>&1', $supervisorStatus);
    
    $sudoSupervisorStatus = [];
    exec('sudo supervisorctl status 2>&1', $sudoSupervisorStatus);

    // Scan log dir
    $logDir = storage_path('logs');
    $logFiles = file_exists($logDir) ? scandir($logDir) : [];
    
    $schedulerLog = file_exists(storage_path('logs/scheduler.log')) ? tail_file(storage_path('logs/scheduler.log'), 30) : 'No scheduler log';
    $laravelLog = file_exists(storage_path('logs/laravel.log')) ? tail_file(storage_path('logs/laravel.log'), 30) : 'No laravel log';

    return response()->json([
        'routers' => $results,
        'db_active_sessions' => $dbSessionsCount,
        'db_customers' => $dbCustomersCount,
        'jobs_count' => $jobsCount,
        'failed_jobs_count' => $failedJobsCount,
        'running_processes' => $processes,
        'supervisor_conf_exists' => $supervisorConfExists,
        'supervisor_conf' => $supervisorConf,
        'supervisor_status' => $supervisorStatus,
        'sudo_supervisor_status' => $sudoSupervisorStatus,
        'log_files' => $logFiles,
        'scheduler_log' => $schedulerLog,
        'laravel_log' => $laravelLog
    ]);
});

function tail_file($filepath, $lines = 10) {
    if (!file_exists($filepath)) return '';
    $f = fopen($filepath, "rb");
    if (!$f) return '';
    $buffer = 4096;
    fseek($f, 0, SEEK_END);
    $pos = ftell($f);
    $count = 0;
    $data = '';
    while ($pos > 0 && $count < $lines) {
        $seek = min($pos, $buffer);
        $pos -= $seek;
        fseek($f, $pos, SEEK_SET);
        $chunk = fread($f, $seek);
        $count += substr_count($chunk, "\n");
        $data = $chunk . $data;
    }
    fclose($f);
    return implode("\n", array_slice(explode("\n", $data), -$lines));
}

require __DIR__.'/auth.php';
