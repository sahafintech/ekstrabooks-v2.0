<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Inertia\Inertia;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $per_page = $request->get('per_page', 50);
        $search = $request->get('search', '');

        $query = AuditLog::with('changed_user');

        // Apply search if provided
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('event', 'like', "%{$search}%")
                    ->orWhere('date_changed', 'like', "%{$search}%")
                    ->orWhereHas('changed_user', function ($q2) use($search) {
                        $q2->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('name', 'like', "%{$search}%");
                    });
            });
        }

        // Handle sorting
        $sorting = $request->get('sorting', []);
        $sortColumn = $sorting['column'] ?? 'date_changed';
        $sortDirection = $sorting['direction'] ?? 'desc';

        // Handle special case for sorting by user name
        if ($sortColumn === 'changed_user.name') {
            $query->join('users', 'audit_logs.changed_by', '=', 'users.id')
                  ->orderBy('users.name', $sortDirection)
                  ->select('audit_logs.*');
        } else {
            $query->orderBy($sortColumn, $sortDirection);
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
                'sorting' => $sorting,
            ],
        ]);
    }
}
