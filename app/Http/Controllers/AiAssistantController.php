<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\AiChatLog;
use App\Models\AiChatSession;
use App\Models\BankMutation;
use App\Models\FiscalPeriod;
use App\Models\JournalEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiAssistantController extends Controller
{
    public function chat(Request $request)
    {
        // Bypass PHP's default 30s limit — Gemini can be slow with large prompts
        set_time_limit(0);

        $request->validate([
            'session_id'              => 'nullable|exists:ai_chat_sessions,id',
            'messages'                => 'required|array',
            'messages.*.role'         => 'required|in:user,model',
            'messages.*.parts'        => 'required|array',
            'messages.*.parts.*.text' => 'required|string',
        ]);

        $sessionId = $request->input('session_id');
        if (!$sessionId) {
            $session = AiChatSession::create([
                'user_id' => auth()->id(),
                'title' => 'Percakapan ' . now()->format('d-m-Y H:i'),
            ]);
            $sessionId = $session->id;
        }

        $apiKey = env('GEMINI_API_KEY');
        if (!$apiKey) {
            return response()->json(['error' => 'GEMINI_API_KEY is missing.'], 500);
        }

        $messages          = $request->input('messages');
        $latestUserMessage = end($messages);

        if ($latestUserMessage && $latestUserMessage['role'] === 'user') {
            AiChatLog::create([
                'user_id'    => auth()->id(),
                'session_id' => $sessionId,
                'role'       => 'user',
                'message'    => $latestUserMessage['parts'][0]['text'] ?? '',
            ]);
        }

        $payload = [
            'system_instruction' => [
                'parts' => [['text' => $this->getSystemPrompt()]]
            ],
            'contents'         => $request->input('messages'),
            'generationConfig' => [
                'temperature'     => 0.2,
                'maxOutputTokens' => 2048,
            ],
        ];

        try {
            $modelUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.6-flash:generateContent?key=' . $apiKey;
            $response = Http::timeout(120)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($modelUrl, $payload);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                    $replyText = $data['candidates'][0]['content']['parts'][0]['text'];
                    AiChatLog::create([
                        'user_id'    => auth()->id(),
                        'session_id' => $sessionId,
                        'role'       => 'model',
                        'message'    => $replyText,
                    ]);
                    
                    // Update session updated_at
                    AiChatSession::where('id', $sessionId)->update(['updated_at' => now()]);

                    return response()->json([
                        'reply'      => $replyText,
                        'session_id' => $sessionId,
                    ]);
                }

                // Check for safety/block reason
                $blockReason = $data['promptFeedback']['blockReason'] ?? null;
                if ($blockReason) {
                    Log::warning('Gemini blocked prompt', ['reason' => $blockReason]);
                    return response()->json(['error' => "Permintaan diblokir oleh AI (alasan: {$blockReason}). Coba ubah pertanyaanmu."], 422);
                }

                Log::error('Gemini API Unexpected Format', ['response' => $data]);
                return response()->json(['error' => 'Format balasan AI tidak terduga. Coba lagi.'], 500);
            }

            // Map HTTP error codes to human-readable messages
            $status = $response->status();
            $geminiError = $response->json('error.message') ?? $response->json('error.status') ?? null;
            $humanError = match(true) {
                $status === 400 => 'Permintaan tidak valid dikirim ke Gemini API. ' . ($geminiError ?? ''),
                $status === 401 => 'API Key Gemini tidak valid atau tidak diizinkan.',
                $status === 403 => 'Akses ke Gemini API ditolak (quota atau izin).',
                $status === 429 => 'Terlalu banyak permintaan — Gemini API rate limit tercapai. Tunggu sebentar lalu coba lagi.',
                $status === 503 => 'Server Gemini sedang tidak tersedia (Service Unavailable). Coba lagi dalam beberapa saat.',
                $status >= 500 => 'Server Gemini mengalami error internal (HTTP ' . $status . '). Coba lagi.',
                default        => 'Gagal terhubung ke Gemini API (HTTP ' . $status . '). ' . ($geminiError ?? ''),
            };

            Log::error('Gemini API Error', ['status' => $status, 'body' => $response->body()]);
            return response()->json(['error' => trim($humanError)], $status);

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Gemini Connection Timeout: ' . $e->getMessage());
            return response()->json(['error' => 'Koneksi ke Gemini AI timeout (> 120 detik). Kemungkinan prompt terlalu panjang atau jaringan lambat. Coba lagi.'], 504);
        } catch (\Exception $e) {
            Log::error('AiAssistantController Exception: ' . $e->getMessage());
            return response()->json(['error' => 'Terjadi kesalahan sistem: ' . $e->getMessage()], 500);
        }
    }

    public function history($sessionId = null)
    {
        if (!$sessionId) {
            $session = AiChatSession::where('user_id', auth()->id())->latest('updated_at')->first();
            if (!$session) {
                return response()->json(['messages' => [], 'session_id' => null]);
            }
            $sessionId = $session->id;
        }

        $logs = AiChatLog::where('user_id', auth()->id())
            ->where('session_id', $sessionId)
            ->orderBy('id', 'asc')
            ->get()
            ->map(fn ($log) => [
                'role'  => $log->role,
                'parts' => [['text' => $log->message]],
            ]);
            
        return response()->json([
            'messages' => $logs,
            'session_id' => $sessionId
        ]);
    }

    public function sessions()
    {
        $sessions = AiChatSession::where('user_id', auth()->id())
            ->orderBy('updated_at', 'desc')
            ->get()
            ->map(fn($s) => [
                'id' => $s->id,
                'title' => $s->title,
                'updated_at' => $s->updated_at->format('d-m-Y H:i'),
            ]);
            
        return response()->json(['sessions' => $sessions]);
    }

    public function createSession()
    {
        $session = AiChatSession::create([
            'user_id' => auth()->id(),
            'title' => 'Percakapan ' . now()->format('d-m-Y H:i'),
        ]);

        return response()->json(['session' => [
            'id' => $session->id,
            'title' => $session->title,
            'updated_at' => $session->updated_at->format('d-m-Y H:i'),
        ]]);
    }

    public function renameSession(Request $request, $id)
    {
        $request->validate(['title' => 'required|string|max:255']);
        
        $session = AiChatSession::where('user_id', auth()->id())->findOrFail($id);
        $session->update(['title' => $request->title]);

        return response()->json(['message' => 'Renamed successfully', 'session' => [
            'id' => $session->id,
            'title' => $session->title,
            'updated_at' => $session->updated_at->format('d-m-Y H:i'),
        ]]);
    }

    public function deleteSession($id)
    {
        $session = AiChatSession::where('user_id', auth()->id())->findOrFail($id);
        $session->delete();

        return response()->json(['message' => 'Deleted successfully']);
    }

    private function getSystemPrompt(): string
    {
        $prompt = "Anda adalah Finlog AI Assistant, asisten cerdas khusus untuk divisi Finance Shoe Workshop.\n";
        $prompt .= "Anda bertugas membantu mengelola dan memahami Sistem Informasi Akuntansi (SIA) Finlog.\n\n";
        $prompt .= "Gunakan bahasa Indonesia yang profesional, ramah, dan ringkas. Gunakan format Markdown (bold, tabel, list) agar mudah dibaca.\n";
        $prompt .= "Jangan menjawab hal di luar konteks sistem ini. Tolak permintaan password/kredensial.\n\n";
        
        $prompt .= "--- INSTRUKSI UX (SANGAT PENTING) ---\n";
        $prompt .= "Di bagian PALING AKHIR dari setiap jawabanmu, kamu WAJIB memberikan 2-3 saran pertanyaan lanjutan (follow-up questions) yang relevan dengan topik yang baru saja dibahas.\n";
        $prompt .= "Formatnya harus persis seperti ini di baris paling bawah (pisahkan dengan karakter |):\n";
        $prompt .= "SUGGESTIONS: Saran Pertanyaan 1|Saran Pertanyaan 2\n\n";

        $prompt .= "--- KNOWLEDGE BASE ---\n\n";
        foreach ([
            'DOKUMENTASI_FINLOG.md',          // user manual lengkap
            'panduan_operasional_menu.md',     // panduan menu, alur, FAQ
            'coa_rules.md',                    // aturan chart of accounts
            'accounting_formulas_guide.md',    // rumus akuntansi
            'audit_laporan_finlog.md',         // audit & catatan laporan
            'panduan_arus_kas.md',
            'panduan_buku_besar.md',
            'panduan_laba_rugi.md',
            'panduan_neraca_lajur.md',
            'panduan_neraca.md',
            'panduan_perubahan_ekuitas.md',
        ] as $file) {
            $path = base_path($file);
            if (file_exists($path)) {
                $prompt .= "=== {$file} ===\n" . file_get_contents($path) . "\n\n";
            }
        }

        // ── NAVIGATION MAP ─────────────────────────────────────────────────────
        $prompt .= "--- PETA NAVIGASI & MENU RESMI SISTEM FINLOG ---\n\n";
        $prompt .= "Ini adalah DAFTAR MENU RESMI yang ada di sidebar Finlog. Gunakan ini sebagai sumber kebenaran mutlak — jangan mengarang atau menebak nama/URL menu.\n\n";
        $prompt .= "### UTAMA\n- Dashboard → /app/dashboard\n\n";
        $prompt .= "### TRANSAKSI\n";
        $prompt .= "- Pengajuan Belanja → /app/purchase-requests (khusus staff/admin)\n";
        $prompt .= "- Transaksi (Mutasi Bank) → /app/bank-mutations (import & generate draft jurnal dari mutasi bank)\n";
        $prompt .= "- Draft Jurnal → /app/draft-journals (jurnal draft/unapproved yang belum diposting)\n";
        $prompt .= "- Daftar Jurnal → /app/journal-entries (semua jurnal posted)\n";
        $prompt .= "- Arsip Transaksi → /app/transaction-archive (riwayat mutasi bank yang sudah selesai diproses/dijurnalkan)\n\n";
        $prompt .= "### LAPORAN KEUANGAN\n";
        $prompt .= "- Buku Besar → /app/general-ledger\n";
        $prompt .= "- Neraca Lajur → /app/trial-balance\n";
        $prompt .= "- Laba Rugi → /app/income-statement\n";
        $prompt .= "- Neraca → /app/balance-sheet\n";
        $prompt .= "- Perubahan Ekuitas → /app/equity-statement\n";
        $prompt .= "- Arus Kas → /app/cash-flow\n\n";
        $prompt .= "### MASTER & PENGATURAN\n";
        $prompt .= "- Chart of Accounts (COA) → /app/accounts\n";
        $prompt .= "- Periode Akuntansi → /app/fiscal-periods\n";
        $prompt .= "- Penutupan Periode → /app/period-closing (khusus owner/finance)\n";
        $prompt .= "- Manajemen Akun → /app/users (khusus owner)\n";
        $prompt .= "- Audit Trail → /app/audit-trail (khusus owner/finance)\n\n";

        // ── LIVE DB SNAPSHOT ───────────────────────────────────────────────────
        $prompt .= "--- DATA REAL-TIME DATABASE (" . now()->format('d M Y H:i') . " WIB) ---\n\n";
        try {
            $prompt .= $this->buildLiveSnapshot();
        } catch (\Exception $e) {
            Log::warning('AI DB snapshot error: ' . $e->getMessage());
            $prompt .= "(Data real-time tidak tersedia.)\n\n";
        }

        return $prompt;
    }


    private function buildLiveSnapshot(): string
    {
        $out = '';

        // Periode Akuntansi
        $activePeriod = FiscalPeriod::whereIn('status', ['open', 'reopened'])->orderByDesc('start_date')->first();
        $out .= "### PERIODE AKUNTANSI\n";
        $out .= $activePeriod
            ? "- Aktif: **{$activePeriod->name}** ({$activePeriod->start_date->format('d M Y')} - {$activePeriod->end_date->format('d M Y')}) | {$activePeriod->status}\n"
            : "- Tidak ada periode aktif.\n";
        FiscalPeriod::orderByDesc('start_date')->each(fn ($p) => $out .= "- {$p->name}: {$p->status}\n");
        $out .= "\n";

        if (!$activePeriod) return $out;
        $pid = $activePeriod->id;

        // Statistik Jurnal
        $total   = JournalEntry::where('fiscal_period_id', $pid)->count();
        $posted  = JournalEntry::where('fiscal_period_id', $pid)->where('status', 'posted')->count();
        $pendingActive = JournalEntry::where('fiscal_period_id', $pid)->whereIn('status', ['draft', 'unapproved'])->count();
        $out .= "### JURNAL — {$activePeriod->name}\n- Total: {$total}\n- Posted: {$posted}\n- Draft/Unapproved (Bulan Ini): {$pendingActive}\n\n";

        // Draft global lintas periode
        $globalPending = JournalEntry::whereIn('status', ['draft', 'unapproved'])->get();
        if ($globalPending->count() > 0) {
            $out .= "### DRAFT JURNAL MENGGANTUNG (SEMUA PERIODE)\n";
            $out .= "Total ada {$globalPending->count()} jurnal yang masih berstatus draft/unapproved di sistem.\n";
            $out .= "Rincian per bulan:\n";
            $globalPending->groupBy(fn($j) => $j->entry_date->format('F Y'))->each(function($group, $month) use (&$out) {
                $out .= "- {$month}: {$group->count()} draft\n";
            });
            $out .= "\n";
        }

        // Saldo Kas & Bank
        $cashAccounts = Account::active()
            ->where(fn ($q) => $q->where('code', 'like', '111%')->orWhere('code', 'like', '112%'))
            ->whereNotNull('parent_id')->get();
        $out .= "### SALDO KAS & BANK\n";
        $totalCash = 0;
        foreach ($cashAccounts as $acc) {
            $bal = (float) $acc->getBalanceForPeriod($pid);
            $totalCash += $bal;
            $out .= "- [{$acc->code}] {$acc->name}: Rp " . number_format($bal, 0, ',', '.') . "\n";
        }
        $out .= "- **TOTAL: Rp " . number_format($totalCash, 0, ',', '.') . "**\n\n";

        // Saldo Piutang Usaha (113x)
        $receivableAccounts = Account::active()
            ->where('code', 'like', '113%')->whereNotNull('parent_id')->get();
        if ($receivableAccounts->isNotEmpty()) {
            $out .= "### SALDO PIUTANG USAHA\n";
            $totalRec = 0;
            foreach ($receivableAccounts as $acc) {
                $bal = (float) $acc->getBalanceForPeriod($pid);
                $totalRec += $bal;
                $out .= "- [{$acc->code}] {$acc->name}: Rp " . number_format($bal, 0, ',', '.') . "\n";
            }
            $out .= "- **TOTAL: Rp " . number_format($totalRec, 0, ',', '.') . "**\n\n";
        }

        // Saldo Persediaan (114x / 115x)
        $inventoryAccounts = Account::active()
            ->where(fn ($q) => $q->where('code', 'like', '114%')->orWhere('code', 'like', '115%'))
            ->whereNotNull('parent_id')->get();
        if ($inventoryAccounts->isNotEmpty()) {
            $out .= "### SALDO PERSEDIAAN\n";
            $totalInv = 0;
            foreach ($inventoryAccounts as $acc) {
                $bal = (float) $acc->getBalanceForPeriod($pid);
                $totalInv += $bal;
                $out .= "- [{$acc->code}] {$acc->name}: Rp " . number_format($bal, 0, ',', '.') . "\n";
            }
            $out .= "- **TOTAL: Rp " . number_format($totalInv, 0, ',', '.') . "**\n\n";
        }

        // Saldo Hutang Usaha (211x)
        $payableAccounts = Account::active()
            ->where('code', 'like', '211%')->whereNotNull('parent_id')->get();
        if ($payableAccounts->isNotEmpty()) {
            $out .= "### SALDO HUTANG USAHA\n";
            $totalPay = 0;
            foreach ($payableAccounts as $acc) {
                $bal = (float) $acc->getBalanceForPeriod($pid);
                $totalPay += $bal;
                $out .= "- [{$acc->code}] {$acc->name}: Rp " . number_format($bal, 0, ',', '.') . "\n";
            }
            $out .= "- **TOTAL: Rp " . number_format($totalPay, 0, ',', '.') . "**\n\n";
        }

        // Top 5 Beban
        $topExpenses = DB::table('journal_entry_lines as jel')
            ->join('journal_entries as je', 'jel.journal_entry_id', '=', 'je.id')
            ->join('accounts as a', 'jel.account_id', '=', 'a.id')
            ->where('je.fiscal_period_id', $pid)->where('je.status', 'posted')
            ->where(fn ($q) => $q->where('a.code', 'like', '5%')->orWhere('a.code', 'like', '6%')->orWhere('a.code', 'like', '72%')->orWhere('a.code', 'like', '8%'))
            ->whereNotNull('a.parent_id')
            ->groupBy('a.id', 'a.code', 'a.name')
            ->select('a.code', 'a.name', DB::raw('SUM(jel.debit) - SUM(jel.credit) as net'))
            ->orderByDesc('net')->limit(5)->get();

        if ($topExpenses->isNotEmpty()) {
            $out .= "### TOP 5 BEBAN — {$activePeriod->name}\n";
            foreach ($topExpenses as $e) {
                $out .= "- [{$e->code}] {$e->name}: Rp " . number_format((float) $e->net, 0, ',', '.') . "\n";
            }
            $out .= "\n";
        }

        // Laba Bersih periode aktif
        $calcPnL = function (int $periodId): array {
            $rev = (float) DB::table('journal_entry_lines as jel')
                ->join('journal_entries as je', 'jel.journal_entry_id', '=', 'je.id')
                ->join('accounts as a', 'jel.account_id', '=', 'a.id')
                ->where('je.fiscal_period_id', $periodId)->where('je.status', 'posted')
                ->where(fn ($q) => $q->where('a.code', 'like', '4%')->orWhere('a.code', 'like', '71%'))
                ->sum(DB::raw('jel.credit - jel.debit'));
            $exp = (float) DB::table('journal_entry_lines as jel')
                ->join('journal_entries as je', 'jel.journal_entry_id', '=', 'je.id')
                ->join('accounts as a', 'jel.account_id', '=', 'a.id')
                ->where('je.fiscal_period_id', $periodId)->where('je.status', 'posted')
                ->where(fn ($q) => $q->where('a.code', 'like', '5%')->orWhere('a.code', 'like', '6%')->orWhere('a.code', 'like', '72%')->orWhere('a.code', 'like', '8%'))
                ->sum(DB::raw('jel.debit - jel.credit'));
            return ['revenue' => $rev, 'expense' => $exp, 'net' => $rev - $exp];
        };

        $pnl = $calcPnL($pid);
        $out .= "### LABA BERSIH — {$activePeriod->name}\n";
        $out .= "- Pendapatan: Rp " . number_format($pnl['revenue'], 0, ',', '.') . "\n";
        $out .= "- Beban: Rp " . number_format($pnl['expense'], 0, ',', '.') . "\n";
        $out .= "- **Laba Bersih: Rp " . number_format($pnl['net'], 0, ',', '.') . "**\n\n";

        // ── Multi-period comparison (3 bulan terakhir) ──────────────────────
        $recentPeriods = FiscalPeriod::whereIn('status', ['open', 'closed', 'reopened'])
            ->orderByDesc('start_date')
            ->limit(4)
            ->get();

        if ($recentPeriods->count() > 1) {
            $out .= "### PERBANDINGAN LABA BERSIH (3 PERIODE TERAKHIR)\n";
            $out .= "| Periode | Pendapatan | Beban | Laba Bersih |\n";
            $out .= "|---|---|---|---|\n";
            foreach ($recentPeriods as $rp) {
                $rpnl = $calcPnL($rp->id);
                $mark = $rp->id === $pid ? ' *(aktif)*' : '';
                $out .= "| {$rp->name}{$mark} | Rp " . number_format($rpnl['revenue'], 0, ',', '.') . " | Rp " . number_format($rpnl['expense'], 0, ',', '.') . " | Rp " . number_format($rpnl['net'], 0, ',', '.') . " |\n";
            }
            $out .= "\n";
        }

        // Mutasi Bank
        $pendingMut = BankMutation::where('status', 'pending')->count();
        $out .= "### MUTASI BANK\n- Pending (belum dijurnalkan): {$pendingMut} item\n\n";

        return $out;
    }
}
