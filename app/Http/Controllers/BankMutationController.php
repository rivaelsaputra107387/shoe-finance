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

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('description', 'like', "%{$search}%");
        }

        $mutations = $query->paginate(25)->withQueryString();

        return Inertia::render('Transactions/BankMutations', [
            'mutations' => $mutations,
            'filters'   => $request->only(['bank_source', 'status', 'search']),
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
        $service = app(BankMutationService::class);
        $entry = $service->draftJournalEntry($bankMutation, auth()->id());

        if (!$entry) {
            return back()->with('error', 'Gagal membuat draft jurnal. Pastikan Akun Sementara (9999) ada di sistem.');
        }

        return back()->with('success', 'Draft Jurnal berhasil dibuat dari Mutasi Bank.');
    }
}
