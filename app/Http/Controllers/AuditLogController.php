<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AuditLogController extends Controller
{
    public function index()
    {
        // Only Super Admin, Owner, and Admin can view activity logs
        if (!auth()->user()->hasAnyRole(['Super Admin', 'Owner', 'Admin'])) {
            abort(403, 'Unauthorized to view audit logs.');
        }

        $logs = AuditLog::with('user')->latest()->paginate(25);

        return Inertia::render('AuditLogs/Index', [
            'logs' => $logs,
        ]);
    }
}
