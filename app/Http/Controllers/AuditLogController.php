<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Inertia\Inertia;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request) {
        $per_page = $request->get('per_page', 10);
        $search = $request->get('search', '');

        $query = AuditLog::orderBy("id", "desc")
            ->with('changed_user');

        // Apply search if provided
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('event', 'like', "%{$search}%")
                ->orWhere('changed_user.name', 'like', "%{$search}%")
                ->orWhere('changed_user.email', 'like', "%{$search}%")
                ->orWhere('date_changed', 'like', "%{$search}%");
            });
        }

        $auditLogs = $query->paginate($per_page)->withQueryString();
        return Inertia::render('Backend/User/Business/AuditLog/List', [
            'auditLogs' => $auditLogs->items(),
            'meta' => [
                'current_page' => $auditLogs->currentPage(),
                'from' => $auditLogs->firstItem(),
                'last_page' => $auditLogs->lastPage(),
                'per_page' => $per_page,
                'to' => $auditLogs->lastItem(),
                'total' => $auditLogs->total(),
                'links' => $auditLogs->linkCollection(),
                'path' => $auditLogs->path(),
            ],
            'filters' => [
                'search' => $search,
            ],
        ]);
    }
}
