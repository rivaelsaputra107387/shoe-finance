<?php

namespace App\Jobs;

use App\Models\Central\Unit;
use App\Support\UnitContext;
use App\Models\PurchaseRequest;
use App\Models\WebhookEventLog;
use App\Services\FinlogWebhookSenderService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class SendWebhookToWorkshopJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public $tries = 3;
    public array $backoff = [60, 300, 900]; // 1 min, 5 min, 15 min

    /**
     * Create a new job instance.
     */
    public function __construct(public int $purchaseRequestId, public string $unitCode)
    {
    }

    /**
     * Execute the job.
     */
    public function handle(FinlogWebhookSenderService $sender, UnitContext $context): void
    {
        // Kirim ID dan kode unit, bukan model, karena pengajuan hanya bisa dimuat setelah konteks unit aktif.
        $context->run(
            Unit::where('code', $this->unitCode)->firstOrFail(),
            fn () => $this->send($sender, PurchaseRequest::findOrFail($this->purchaseRequestId))
        );
    }

    private function send(FinlogWebhookSenderService $sender, PurchaseRequest $purchaseRequest): void
    {
        $log = $purchaseRequest->webhookLogs()->create([
            'event_type' => 'purchase_request.status_updated',
            'status' => 'PENDING',
            'payload' => [], 
            'attempt_count' => $this->attempts(),
            'last_attempted_at' => now(),
        ]);

        try {
            $result = $sender->sendStatusUpdate($purchaseRequest);
            
            $log->update([
                'payload' => $result['payload'],
                'response_code' => $result['status_code'],
                'response_body' => $result['body'],
                'status' => $result['success'] ? 'SENT' : 'FAILED',
            ]);

            if (!$result['success'] && $this->attempts() < $this->tries) {
                $this->release($this->backoff[$this->attempts() - 1] ?? 900);
            }
        } catch (\Exception $e) {
            $log->update([
                'status' => 'FAILED',
                'response_body' => $e->getMessage(),
            ]);

            if ($this->attempts() < $this->tries) {
                $this->release($this->backoff[$this->attempts() - 1] ?? 900);
            }
        }
    }
}
