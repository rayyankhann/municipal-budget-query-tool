<?php

namespace App\Http\Controllers;

use App\Models\QueryLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'admin') {
            abort(403);
        }

        $logs = QueryLog::with('user:id,name,email,role')
            ->latest()
            ->paginate(25);

        return Inertia::render('AuditLog', [
            'logs' => $logs,
        ]);
    }
}
