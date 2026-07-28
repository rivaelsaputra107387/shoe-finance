<?php

namespace App\Http\Controllers;

use App\Models\AuditTrail;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditTrailController extends Controller
{
    public function index(Request $request): Response
    {
        $user = auth()->user();
        if (!$user->hasRole('owner')) {
            abort(403, 'Akses ditolak: Hanya Owner yang dapat mengakses Log Audit Trail.');
        }

        $query = AuditTrail::with('user')->latest('created_at');

        if ($request->filled('table_name')) {
            $query->where('table_name', $request->table_name);
        }

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        $auditTrails = $query->paginate(15)->withQueryString();

        return Inertia::render('Settings/AuditTrail', [
            'auditTrails'   => $auditTrails,
            'selectedTable' => $request->input('table_name', ''),
            'selectedAction' => $request->input('action', ''),
        ]);
    }
}
