<?php

namespace App\Http\Controllers;

use App\Models\Router;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class BackupController extends Controller
{
    public function index()
    {
        $backupDir = 'backups';
        if (!Storage::exists($backupDir)) {
            Storage::makeDirectory($backupDir);
        }

        $files = [];
        $allFiles = Storage::files($backupDir);
        foreach ($allFiles as $file) {
            $files[] = [
                'name' => basename($file),
                'size' => round(Storage::size($file) / 1024, 2) . ' KB',
                'created_at' => date('d M Y H:i', Storage::lastModified($file)),
                'path' => $file,
            ];
        }

        $routers = Router::where('is_active', true)->get();

        return Inertia::render('Backups/Index', [
            'files' => $files,
            'routers' => $routers,
        ]);
    }

    public function backupDatabase()
    {
        $sql = $this->generateDatabaseBackup();
        $filename = 'backups/db-backup-' . date('Ymd-His') . '.sql';
        Storage::put($filename, $sql);

        return redirect()->back()->with('success', 'Backup database berhasil dibuat.');
    }

    public function backupRouter($routerId)
    {
        $router = Router::findOrFail($routerId);
        
        $connectionService = app(\App\Services\MikrotikConnectionService::class);
        $rsc = "# PAK DOEL NET Router Export for: {$router->name}\n";
        $rsc .= "# Generated: " . date('Y-m-d H:i:s') . "\n\n";

        try {
            $secretsRes = $connectionService->getPppSecrets($router);
            if ($secretsRes['success']) {
                $rsc .= "/ppp secret\n";
                foreach ($secretsRes['secrets'] as $secret) {
                    $rsc .= "add name=\"{$secret['name']}\" password=\"{$secret['password']}\" service=pppoe profile=\"{$secret['profile']}\"\n";
                }
            } else {
                $rsc .= "# Error fetching secrets: " . $secretsRes['error'] . "\n";
            }
        } catch (\Exception $e) {
            $rsc .= "# Connection failed: " . $e->getMessage() . "\n";
        }

        $filename = 'backups/router-' . strtolower(str_replace(' ', '-', $router->name)) . '-' . date('Ymd-His') . '.rsc';
        Storage::put($filename, $rsc);

        return redirect()->back()->with('success', 'Backup konfigurasi router berhasil dibuat.');
    }

    public function download($filename)
    {
        $path = 'backups/' . $filename;
        if (!Storage::exists($path)) {
            abort(404);
        }
        return Storage::download($path);
    }

    public function destroy($filename)
    {
        $path = 'backups/' . $filename;
        if (Storage::exists($path)) {
            Storage::delete($path);
        }
        return redirect()->back()->with('success', 'File backup berhasil dihapus.');
    }

    public function restore(Request $request)
    {
        $validated = $request->validate([
            'backup_file' => 'required|file',
        ]);

        $file = $request->file('backup_file');
        $sql = file_get_contents($file->getRealPath());

        try {
            \DB::unprepared($sql);
            return redirect()->back()->with('success', 'Database berhasil di-restore dari file backup.');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['backup_file' => 'Gagal me-restore database: ' . $e->getMessage()]);
        }
    }

    private function generateDatabaseBackup()
    {
        $tables = ['users', 'roles', 'permissions', 'routers', 'packages', 'customers', 'invoices', 'payments', 'expenses', 'tickets', 'inventory_items', 'inventory_logs', 'odps', 'whatsapp_settings', 'whatsapp_logs', 'ping_logs'];
        $sql = "-- PAK DOEL NET Database Backup\n";
        $sql .= "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";

        foreach ($tables as $table) {
            if (!\Schema::hasTable($table)) continue;
            $sql .= "DROP TABLE IF EXISTS `$table`;\n";
            
            $rows = \DB::table($table)->get();
            foreach ($rows as $row) {
                $arr = (array)$row;
                $keys = array_map(function($k) { return "`$k`"; }, array_keys($arr));
                $vals = array_map(function($v) {
                    if (is_null($v)) return "NULL";
                    return "'" . addslashes($v) . "'";
                }, array_values($arr));
                
                $sql .= "INSERT INTO `$table` (" . implode(', ', $keys) . ") VALUES (" . implode(', ', $vals) . ");\n";
            }
            $sql .= "\n";
        }
        return $sql;
    }
}
