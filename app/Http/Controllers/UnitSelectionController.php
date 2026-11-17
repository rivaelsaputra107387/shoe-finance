<?php

namespace App\Http\Controllers;

use App\Http\Middleware\EnsureActiveUnit;
use App\Models\Central\Unit;
use App\Models\FiscalPeriod;
use App\Models\JournalEntry;
use App\Support\UnitContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class UnitSelectionController extends Controller
{
    public function index(Request $request, UnitContext $context)
    {
        $user = $request->user();
        $units = $user->accessibleUnits();

        if ($units->count() === 1) {
            $request->session()->put(EnsureActiveUnit::SESSION_KEY, $units->first()->id);

            return redirect('/app/dashboard');
        }

        return Inertia::render('Units/Select', [
            'units' => $units->map(fn (Unit $unit) => $this->summary($unit, $context))->values(),
            'activeUnitId' => $request->session()->get(EnsureActiveUnit::SESSION_KEY),
            'canViewConsolidated' => $user->canViewConsolidated(),
        ]);
    }

    public function select(Request $request)
    {
        $validated = $request->validate(['unit_id' => ['required', 'integer']]);
        $unit = Unit::find($validated['unit_id']);

        abort_unless($unit && $request->user()->canAccessUnit($unit), 403, 'Anda tidak memiliki akses ke unit ini.');

        $request->session()->put(EnsureActiveUnit::SESSION_KEY, $unit->id);

        // Muat ulang penuh, supaya tidak ada tampilan atau header yang masih memakai unit sebelumnya.
        return Inertia::location('/app/dashboard');
    }

    /**
     * Satu database unit yang bermasalah tidak boleh merusak halaman pilihan unit.
     */
    private function summary(Unit $unit, UnitContext $context): array
    {
        $summary = [
            'id' => $unit->id,
            'code' => $unit->code,
            'name' => $unit->name,
            'status' => $unit->status,
            'active_period' => null,
            'pending_journals' => null,
        ];

        try {
            return array_merge($summary, $context->run($unit, fn () => [
                'active_period' => FiscalPeriod::whereIn('status', ['open', 'reopened'])->orderBy('start_date')->value('name'),
                'pending_journals' => JournalEntry::where('status', 'unapproved')->count(),
            ]));
        } catch (\Throwable $e) {
            Log::warning("Ringkasan unit {$unit->code} gagal dimuat: {$e->getMessage()}");

            return $summary;
        }
    }
}
