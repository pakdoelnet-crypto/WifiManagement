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
    Route::get('/network-map/live', [MapController::class, 'getLiveStatus'])->name('network-map.live');

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

    // NOC Dashboard
    Route::get('/noc-dashboard', [\App\Http\Controllers\NocDashboardController::class, 'index'])->name('noc.index');
    Route::get('/noc-dashboard/live', [\App\Http\Controllers\NocDashboardController::class, 'getLiveStats'])->name('noc.live');

    // Finance Dashboard & Expenses
    Route::get('/finance', [\App\Http\Controllers\FinanceController::class, 'index'])->name('finance.index');
    Route::post('/finance/expenses', [\App\Http\Controllers\FinanceController::class, 'storeExpense'])->name('finance.expenses.store');
    Route::delete('/finance/expenses/{id}', [\App\Http\Controllers\FinanceController::class, 'destroyExpense'])->name('finance.expenses.destroy');

    // Ticket Gangguan
    Route::get('/tickets', [\App\Http\Controllers\TicketController::class, 'index'])->name('tickets.index');
    Route::post('/tickets', [\App\Http\Controllers\TicketController::class, 'store'])->name('tickets.store');
    Route::post('/tickets/{id}', [\App\Http\Controllers\TicketController::class, 'update'])->name('tickets.update');
    Route::delete('/tickets/{id}', [\App\Http\Controllers\TicketController::class, 'destroy'])->name('tickets.destroy');

    // Inventory
    Route::get('/inventory', [\App\Http\Controllers\InventoryController::class, 'index'])->name('inventory.index');
    Route::post('/inventory', [\App\Http\Controllers\InventoryController::class, 'store'])->name('inventory.store');
    Route::put('/inventory/{id}', [\App\Http\Controllers\InventoryController::class, 'update'])->name('inventory.update');
    Route::post('/inventory/{id}/adjust', [\App\Http\Controllers\InventoryController::class, 'adjustStock'])->name('inventory.adjust');
    Route::delete('/inventory/{id}', [\App\Http\Controllers\InventoryController::class, 'destroy'])->name('inventory.destroy');

    // WhatsApp Center
    Route::get('/whatsapp', [\App\Http\Controllers\WhatsappCenterController::class, 'index'])->name('whatsapp.index');
    Route::post('/whatsapp/settings', [\App\Http\Controllers\WhatsappCenterController::class, 'updateSettings'])->name('whatsapp.settings.update');
    Route::post('/whatsapp/send-manual', [\App\Http\Controllers\WhatsappCenterController::class, 'sendManual'])->name('whatsapp.send-manual');
    Route::post('/whatsapp/broadcast', [\App\Http\Controllers\WhatsappCenterController::class, 'broadcast'])->name('whatsapp.broadcast');

    // Traffic Monitoring
    Route::get('/traffic-monitoring', [\App\Http\Controllers\TrafficController::class, 'index'])->name('traffic.index');
    Route::get('/traffic-monitoring/stats', [\App\Http\Controllers\TrafficController::class, 'getStats'])->name('traffic.stats');

    // SLA Dashboard
    Route::get('/sla-dashboard', [\App\Http\Controllers\SlaController::class, 'index'])->name('sla.index');

    // ODP Management
    Route::get('/odps', [\App\Http\Controllers\OdpController::class, 'index'])->name('odp.index');
    Route::post('/odps', [\App\Http\Controllers\OdpController::class, 'store'])->name('odp.store');
    Route::put('/odps/{id}', [\App\Http\Controllers\OdpController::class, 'update'])->name('odp.update');
    Route::post('/odps/{id}/assign', [\App\Http\Controllers\OdpController::class, 'assignCustomer'])->name('odp.assign');
    Route::delete('/odps/{id}', [\App\Http\Controllers\OdpController::class, 'destroy'])->name('odp.destroy');

    // Backup
    Route::get('/backups', [\App\Http\Controllers\BackupController::class, 'index'])->name('backups.index');
    Route::post('/backups/db', [\App\Http\Controllers\BackupController::class, 'backupDatabase'])->name('backups.db');
    Route::post('/backups/router/{routerId}', [\App\Http\Controllers\BackupController::class, 'backupRouter'])->name('backups.router');
    Route::get('/backups/download/{filename}', [\App\Http\Controllers\BackupController::class, 'download'])->name('backups.download');
    Route::delete('/backups/{filename}', [\App\Http\Controllers\BackupController::class, 'destroy'])->name('backups.destroy');
    Route::post('/backups/restore', [\App\Http\Controllers\BackupController::class, 'restore'])->name('backups.restore');
});

// Customer Portal public/unauthenticated routes
Route::get('/portal/login', [\App\Http\Controllers\CustomerAuthController::class, 'showLogin'])->name('portal.login');
Route::post('/portal/login', [\App\Http\Controllers\CustomerAuthController::class, 'login']);
Route::post('/portal/logout', [\App\Http\Controllers\CustomerAuthController::class, 'logout'])->name('portal.logout');

// Customer Portal authenticated routes
Route::middleware([\App\Http\Middleware\CustomerPortalAuth::class])->group(function () {
    Route::get('/portal/dashboard', [\App\Http\Controllers\CustomerPortalController::class, 'index'])->name('portal.dashboard');
    Route::post('/portal/ticket', [\App\Http\Controllers\CustomerPortalController::class, 'reportTicket'])->name('portal.ticket');
    Route::post('/portal/change-password', [\App\Http\Controllers\CustomerPortalController::class, 'changePassword'])->name('portal.change-password');
});

Route::get('/invoices/{invoice_number}/public', [\App\Http\Controllers\InvoiceController::class, 'publicView'])->name('invoices.public');

Route::post('/deploy-webhook', function (\Illuminate\Http\Request $request) {
    $token = env('DEPLOY_TOKEN');
    if (!$token || $request->header('X-Deploy-Token') !== $token) {
        return response()->json(['error' => 'Unauthorized'], 401);
    }
    $output = [];
    putenv('HOME=/tmp');
    
    // 1. Configure git safe directory
    exec('git config --global --add safe.directory /var/www/pakdoelnet 2>&1', $output);
    
    // 2. Recursively fix permissions for any files owned by www-data in .git
    $gitPath = '/var/www/pakdoelnet/.git';
    if (is_dir($gitPath)) {
        try {
            $myUid = function_exists('posix_getuid') ? posix_getuid() : getmyuid();
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($gitPath, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );
            foreach ($iterator as $item) {
                if ($item->getOwner() === $myUid) {
                    @chmod($item->getPathname(), $item->isDir() ? 0775 : 0664);
                }
            }
            @chmod($gitPath, 0775);
        } catch (\Exception $e) {
            $output[] = 'Permission fix warning: ' . $e->getMessage();
        }
    }

    // 3. Execute Git deploy commands
    exec('cd /var/www/pakdoelnet && git stash 2>&1', $output);
    exec('cd /var/www/pakdoelnet && git fetch --all 2>&1', $output);
    exec('cd /var/www/pakdoelnet && git reset --hard origin/main 2>&1', $output);
    exec('cd /var/www/pakdoelnet && php artisan migrate --force 2>&1', $output);
    exec('cd /var/www/pakdoelnet && php artisan view:clear 2>&1', $output);
    exec('cd /var/www/pakdoelnet && php artisan config:clear 2>&1', $output);
    exec('cd /var/www/pakdoelnet && php artisan cache:clear 2>&1', $output);
    
    $cacheDetails = [];
    exec('ls -la /var/www/pakdoelnet/bootstrap/cache 2>&1', $cacheDetails);

    // Automatically optimize .env for SQLite to prevent database locks
    $envPath = '/var/www/pakdoelnet/.env';
    if (file_exists($envPath)) {
        $envContent = file_get_contents($envPath);
        $original = $envContent;
        $envContent = str_replace('SESSION_DRIVER=database', 'SESSION_DRIVER=file', $envContent);
        $envContent = str_replace('CACHE_STORE=database', 'CACHE_STORE=file', $envContent);
        if ($envContent !== $original) {
            file_put_contents($envPath, $envContent);
        }
    }

    $fpmServices = [];
    exec('systemctl list-unit-files | grep -i fpm 2>&1', $fpmServices);

    return response()->json([
        'success' => true,
        'fpm_services' => $fpmServices,
        'cache_details' => $cacheDetails,
        'processes' => $psOutput,
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

Route::get('/debug-git-status', function () {
    $output = [];
    exec('cd /var/www/pakdoelnet && git log -n 5 --oneline 2>&1', $output);
    exec('cd /var/www/pakdoelnet && git status 2>&1', $output);
    return response()->json([
        'status' => 'success',
        'git_output' => $output
    ]);
});

require __DIR__.'/auth.php';
