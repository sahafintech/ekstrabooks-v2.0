<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Carbon\Carbon;
use Inertia\Inertia;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->get('per_page', 50);
        $search = trim((string) $request->get('search', ''));
        $changedBy = $request->get('changed_by', '');
        $dateRange = $request->get('date_range', null);

        $sorting = $request->get('sorting', []);
        if (!is_array($sorting)) {
            $sorting = [];
        }

        $query = AuditLog::with('changed_user');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('event', 'like', "%{$search}%")
                    ->orWhere('date_changed', 'like', "%{$search}%")
                    ->orWhereHas('changed_user', function ($q2) use ($search) {
                        $q2->where('email', 'like', "%{$search}%")
                            ->orWhere('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($changedBy !== null && $changedBy !== '') {
            $query->where('changed_by', $changedBy);
        }

        if (is_array($dateRange) && count($dateRange) === 2 && !empty($dateRange[0]) && !empty($dateRange[1])) {
            try {
                $start = Carbon::parse($dateRange[0])->startOfDay();
                $end = Carbon::parse($dateRange[1])->endOfDay();
                $query->whereBetween('date_changed', [$start, $end]);
            } catch (\Throwable $th) {
                $dateRange = null;
            }
        } else {
            $dateRange = null;
        }

        $allowedSortColumns = ['date_changed', 'event', 'changed_user.name'];
        $requestedSortColumn = $sorting['column'] ?? 'date_changed';
        $sortColumn = in_array($requestedSortColumn, $allowedSortColumns, true)
            ? $requestedSortColumn
            : 'date_changed';
        $sortDirection = strtolower($sorting['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        if ($sortColumn === 'changed_user.name') {
            $query->join('users', 'audit_logs.changed_by', '=', 'users.id')
                ->orderBy('users.name', $sortDirection)
                ->select('audit_logs.*');
        } else {
            $query->orderBy($sortColumn, $sortDirection);
        }

        $auditLogs = $query->paginate($perPage)->withQueryString();
        $users = User::whereIn(
            'id',
            AuditLog::query()->select('changed_by')->distinct()
        )->orderBy('name')
            ->get(['id', 'name', 'email']);

        return Inertia::render('Backend/User/Business/AuditLog/List', [
            'auditLogs' => $auditLogs->items(),
            'meta' => [
                'current_page' => $auditLogs->currentPage(),
                'from' => $auditLogs->firstItem(),
                'last_page' => $auditLogs->lastPage(),
                'per_page' => $auditLogs->perPage(),
                'to' => $auditLogs->lastItem(),
                'total' => $auditLogs->total(),
                'links' => $auditLogs->linkCollection(),
                'path' => $auditLogs->path(),
            ],
            'filters' => [
                'search' => $search,
                'per_page' => $auditLogs->perPage(),
                'changed_by' => (string) $changedBy,
                'date_range' => $dateRange,
                'sorting' => [
                    'column' => $sortColumn,
                    'direction' => $sortDirection,
                ],
            ],
            'users' => $users,
        ]);
    }
}
