<?php

namespace App\Jobs;

use App\Models\PurchaseRequest;
use App\Models\WebhookEventLog;
use App\Services\FinlogWebhookSenderService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendWebhookToWorkshopJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $purchaseRequest;
    public $tries = 3;
    public array $backoff = [60, 300, 900]; // 1 min, 5 min, 15 min

    /**
     * Create a new job instance.
     */
    public function __construct(PurchaseRequest $purchaseRequest)
    {
        $this->purchaseRequest = $purchaseRequest;
    }

    /**
     * Execute the job.
     */
    public function handle(FinlogWebhookSenderService $sender): void
    {
        $log = $this->purchaseRequest->webhookLogs()->create([
            'event_type' => 'purchase_request.status_updated',
            'status' => 'PENDING',
            'payload' => [], 
            'attempt_count' => $this->attempts(),
            'last_attempted_at' => now(),
        ]);

        try {
            $result = $sender->sendStatusUpdate($this->purchaseRequest);
            
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
