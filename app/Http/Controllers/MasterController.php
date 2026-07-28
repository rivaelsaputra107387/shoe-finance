<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\FiscalPeriod;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MasterController extends Controller
{
    public function accounts(Request $request): Response
    {
        $query = Account::with('parent')->orderBy('code');

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }

        $accounts = $query->paginate(25)->withQueryString();
        $parents  = Account::whereNull('parent_id')->orderBy('code')->get();

        return Inertia::render('Master/Accounts', [
            'accounts' => $accounts,
            'parents'  => $parents,
            'filters'  => $request->only(['type', 'search']),
        ]);
    }

    public function storeAccount(Request $request)
    {
        $request->validate([
            'code'               => ['required', 'string', 'unique:accounts,code'],
            'name'               => ['required', 'string', 'max:255'],
            'type'               => ['required', 'string'],
            'normal_balance'     => ['required', 'string', 'in:Debet,Kredit'],
            'report_category'    => ['required', 'string', 'in:Neraca,Laba Rugi'],
            'cash_flow_category' => ['nullable', 'string'],
            'parent_id'          => ['nullable', 'exists:accounts,id'],
        ]);

        Account::create($request->all());

        return back()->with('success', 'Akun COA berhasil dibuat.');
    }

    public function fiscalPeriods(): Response
    {
        $periods = FiscalPeriod::orderByDesc('start_date')->get();

        return Inertia::render('Master/FiscalPeriods', [
            'periods' => $periods,
        ]);
    }

    public function storeFiscalPeriod(Request $request)
    {
        $request->validate([
            'name'       => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date'   => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        FiscalPeriod::create([
            'name'       => $request->name,
            'start_date' => $request->start_date,
            'end_date'   => $request->end_date,
            'status'     => 'open',
        ]);

        return back()->with('success', 'Periode Akuntansi berhasil dibuat.');
    }
}
