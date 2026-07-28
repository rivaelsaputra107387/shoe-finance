<?php

namespace App\Http\Controllers;

use App\Models\BankMutation;
use App\Services\BankMutationParserService;
use App\Services\BankMutationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class BankMutationController extends Controller
{
    public function index(Request $request): Response
    {
        $query = BankMutation::with(['uploader', 'journalEntry'])
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc');

        if ($request->filled('bank_source')) {
            $query->where('bank_source', $request->bank_source);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('mutation_type')) {
            $query->where('mutation_type', $request->mutation_type);
        }

        if ($request->filled('start_date')) {
            $query->whereDate('date', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('date', '<=', $request->end_date);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('description', 'like', "%{$search}%");
        }

        $mutations = $query->paginate(25)->withQueryString();

        return Inertia::render('Transactions/BankMutations', [
            'mutations' => $mutations,
            'filters'   => $request->only(['bank_source', 'status', 'mutation_type', 'date_preset', 'start_date', 'end_date', 'search']),
        ]);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file'        => ['required', 'file'],
            'bank_source' => ['nullable', 'string'],
        ]);

        $file = $request->file('file');
        $path = $file->store('temp_imports', 'public');
        $fullPath = Storage::disk('public')->path($path);

        $parser = new BankMutationParserService();
        $records = $parser->parse($fullPath, $request->input('bank_source', 'AUTO'));

        if (empty($records)) {
            return back()->with('error', 'Tidak ada data mutasi yang berhasil dibaca dari file tersebut.');
        }

        $count = 0;
        foreach ($records as $item) {
            BankMutation::create([
                'date'          => $item['date'],
                'description'   => $item['description'],
                'amount'        => $item['amount'],
                'bank_source'   => $item['bank_source'],
                'mutation_type' => $item['mutation_type'],
                'status'        => 'pending',
                'uploaded_by'   => auth()->id(),
            ]);
            $count++;
        }

        Storage::disk('public')->delete($path);

        return back()->with('success', "Berhasil mengimpor {$count} data mutasi bank.");
    }

    public function generateDraft(BankMutation $bankMutation)
    {
        try {
            $service = app(BankMutationService::class);
            $service->draftJournalEntry($bankMutation, auth()->id());

            return back()->with('success', 'Draft Jurnal berhasil dibuat dari Mutasi Bank.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal membuat draft jurnal: ' . $e->getMessage());
        }
    }

    public function matchApi(BankMutation $bankMutation)
    {
        $service = app(BankMutationService::class);
        $service->matchWithApi($bankMutation, auth()->id());

        return back()->with('success', 'Berhasil mencocokkan mutasi bank dengan invoice (Match API).');
    }

    public function bulkGenerateDraft(Request $request)
    {
        $request->validate([
            'ids'   => ['required', 'array', 'min:1'],
            'ids.*' => ['exists:bank_mutations,id'],
        ]);

        $service = app(BankMutationService::class);
        $count = 0;

        foreach ($request->ids as $id) {
            $mutation = BankMutation::find($id);
            if ($mutation && $mutation->status === 'pending') {
                try {
                    $service->draftJournalEntry($mutation, auth()->id());
                    $count++;
                } catch (\Exception $e) {
                    // continue with next
                }
            }
        }

        return back()->with('success', "Berhasil membuat {$count} draft jurnal dari mutasi bank terpilih.");
    }

    public function bulkMatchApi(Request $request)
    {
        $request->validate([
            'ids'   => ['required', 'array', 'min:1'],
            'ids.*' => ['exists:bank_mutations,id'],
        ]);

        $service = app(BankMutationService::class);
        $count = 0;

        foreach ($request->ids as $id) {
            $mutation = BankMutation::find($id);
            if ($mutation && $mutation->status === 'pending') {
                $service->matchWithApi($mutation, auth()->id());
                $count++;
            }
        }

        return back()->with('success', "Berhasil mencocokkan {$count} mutasi bank terpilih (Match API).");
    }

    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids'   => ['required', 'array', 'min:1'],
            'ids.*' => ['exists:bank_mutations,id'],
        ]);

        $count = 0;
        foreach ($request->ids as $id) {
            $mutation = BankMutation::find($id);
            if ($mutation && $mutation->status !== 'posted') {
                $mutation->delete();
                $count++;
            }
        }

        return back()->with('success', "Berhasil menghapus {$count} data mutasi bank.");
    }
}
