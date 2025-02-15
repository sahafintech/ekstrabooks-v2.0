<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;

class AuditLogController extends Controller
{
    public function index() {
        $auditLogs = AuditLog::all();
        return view('backend.user.audit_log.list', compact('auditLogs'));
    }
}
