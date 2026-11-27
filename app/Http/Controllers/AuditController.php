<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'action' => ['nullable', 'in:created,updated,deleted'],
        ]);
        $logs = AuditLog::with('user')->latest();
        if (isset($filters['action'])) {
            $logs->where('action', $filters['action']);
        }

        return view('audit.index', [
            'logs' => $logs->paginate(30)->withQueryString(),
        ]);
    }
}
