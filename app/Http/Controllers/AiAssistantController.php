<?php

namespace App\Http\Controllers;

use App\Models\AiChatLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiAssistantController extends Controller
{
    /**
     * Handle the chat request and proxy to Gemini API.
     */
    public function chat(Request $request)
    {
        $request->validate([
            'messages' => 'required|array',
            'messages.*.role' => 'required|in:user,model',
            'messages.*.parts' => 'required|array',
            'messages.*.parts.*.text' => 'required|string',
        ]);

        $apiKey = env('GEMINI_API_KEY');
        if (!$apiKey) {
            return response()->json(['error' => 'GEMINI_API_KEY is missing.'], 500);
        }

        // Get the latest user message
        $messages = $request->input('messages');
        $latestUserMessage = end($messages);

        // Save user message to DB
        if ($latestUserMessage && $latestUserMessage['role'] === 'user') {
            AiChatLog::create([
                'user_id' => auth()->id(),
                'role' => 'user',
                'message' => $latestUserMessage['parts'][0]['text'] ?? '',
            ]);
        }

        $systemPrompt = $this->getSystemPrompt();

        // Gemini 1.5 Flash (and higher) supports system_instruction at the root level
        $payload = [
            'system_instruction' => [
                'parts' => [
                    ['text' => $systemPrompt]
                ]
            ],
            'contents' => $request->input('messages'),
            'generationConfig' => [
                'temperature' => 0.2, // Keep it factual and less hallucinated for finance
                'maxOutputTokens' => 2048,
            ]
        ];

        try {
            // Menggunakan gemini-3.6-flash sesuai instruksi user
            $modelUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.6-flash:generateContent?key=' . $apiKey;

            $response = Http::timeout(30)->withHeaders([
                'Content-Type' => 'application/json',
            ])->post($modelUrl, $payload);

            if ($response->successful()) {
                $data = $response->json();
                
                // Extract text from the Gemini response structure
                if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                    $replyText = $data['candidates'][0]['content']['parts'][0]['text'];

                    // Save AI response to DB
                    AiChatLog::create([
                        'user_id' => auth()->id(),
                        'role' => 'model',
                        'message' => $replyText,
                    ]);

                    return response()->json([
                        'reply' => $replyText
                    ]);
                }
                
                Log::error('Gemini API Unexpected Format', ['response' => $data]);
                return response()->json(['error' => 'Format balasan AI tidak terduga.'], 500);
            }

            Log::error('Gemini API Error', ['status' => $response->status(), 'body' => $response->body()]);
            return response()->json([
                'error' => 'Gagal terhubung ke server AI.',
                'details' => $response->json()
            ], $response->status());

        } catch (\Exception $e) {
            Log::error('AiAssistantController Exception: ' . $e->getMessage());
            return response()->json(['error' => 'Terjadi kesalahan sistem: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Fetch chat history for the authenticated user.
     */
    public function history()
    {
        $logs = AiChatLog::where('user_id', auth()->id())
            ->orderBy('id', 'asc')
            ->get()
            ->map(function ($log) {
                return [
                    'role' => $log->role,
                    'parts' => [
                        ['text' => $log->message]
                    ]
                ];
            });

        return response()->json(['messages' => $logs]);
    }

    /**
     * Load knowledge base markdown files to build the system prompt.
     */
    private function getSystemPrompt(): string
    {
        $prompt = "Anda adalah 'Finlog AI Assistant', asisten cerdas internal khusus untuk divisi Finance di perusahaan Shoe Workshop.\n";
        $prompt .= "Tugas utama Anda adalah menjawab pertanyaan staf finance seputar operasional sistem SIA (Sistem Informasi Akuntansi) bernama Finlog.\n";
        $prompt .= "Gunakan dokumen panduan di bawah ini sebagai sumber kebenaran (Single Source of Truth) untuk menjawab pertanyaan.\n";
        $prompt .= "Jangan menjawab pertanyaan di luar konteks sistem ini atau di luar ranah akuntansi perusahaan. Jika ditanya password atau kredensial, tolak dengan sopan.\n\n";
        
        $prompt .= "--- KNOWLEDGE BASE (PANDUAN & ATURAN) ---\n\n";

        $filesToInclude = [
            'DOKUMENTASI_FINLOG.md',
            'coa_rules.md',
            'accounting_formulas_guide.md',
            'panduan_arus_kas.md',
            'panduan_buku_besar.md',
            'panduan_laba_rugi.md',
            'panduan_neraca_lajur.md',
            'panduan_neraca.md',
            'panduan_perubahan_ekuitas.md',
        ];

        foreach ($filesToInclude as $file) {
            $filePath = base_path($file);
            if (file_exists($filePath)) {
                $content = file_get_contents($filePath);
                $prompt .= "=== DOKUMEN: {$file} ===\n";
                $prompt .= $content . "\n\n";
            }
        }

        return $prompt;
    }
}
