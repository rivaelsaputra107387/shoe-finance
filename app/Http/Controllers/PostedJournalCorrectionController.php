<?php

namespace App\Http\Controllers;

use App\Models\JournalEntry;
use App\Services\PostedJournalCorrectionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class PostedJournalCorrectionController extends Controller
{
    public function __construct(private PostedJournalCorrectionService $corrections) {}

    public function edit(JournalEntry $journalEntry): Response|\Inertia\Response
    {
        $journalEntry->load(['lines.account', 'fiscalPeriod']);

        if ($blocker = $this->corrections->blocker($journalEntry)) {
            return redirect("/app/journal-entries/{$journalEntry->id}")->with('error', $blocker);
        }

        return Inertia::render('Transactions/CorrectJournal', [
            'entry' => $journalEntry,
            'accounts' => $this->corrections->selectableAccounts()->map->only(['id', 'code', 'name', 'type'])->values(),
            'requiredSourceLines' => $this->corrections->requiredSourceLines($journalEntry),
        ]);
    }

    public function update(Request $request, JournalEntry $journalEntry): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:10', 'max:500'],
            'lines' => ['required', 'array', 'min:2'],
            'lines.*.account_id' => ['required', 'integer'],
            'lines.*.debit' => ['required', 'numeric', 'min:0'],
            'lines.*.credit' => ['required', 'numeric', 'min:0'],
            'lines.*.description' => ['nullable', 'string', 'max:255'],
        ], [
            'reason.required' => 'Alasan koreksi wajib diisi.',
            'reason.min' => 'Tulis alasan koreksi minimal 10 karakter.',
            'reason.max' => 'Alasan koreksi maksimal 500 karakter.',
            'lines.*.description.max' => 'Keterangan baris maksimal 255 karakter.',
        ]);

        $this->corrections->correct($journalEntry, $validated['lines'], trim($validated['reason']), $request->user(), $request->ip());

        return redirect("/app/journal-entries/{$journalEntry->id}")->with('success', 'Koreksi jurnal tersimpan dan tercatat di riwayat.');
    }
}
