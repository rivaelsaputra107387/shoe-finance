<?php

namespace App\Services;

use App\Models\PurchaseRequest;
use Illuminate\Support\Facades\Http;

class FinlogWebhookSenderService
{
    public function sendStatusUpdate(PurchaseRequest $purchaseRequest): array
    {
        $payload = [
            'event' => 'purchase_request.status_updated',
            'data' => [
                'finlog_request_id' => $purchaseRequest->finlog_request_id,
                'request_number' => $purchaseRequest->request_number,
                'primary_work_order_id' => $purchaseRequest->primary_work_order_id,
                'primary_spk_number' => $purchaseRequest->primary_spk_number,
                'status' => $purchaseRequest->status,
                'updated_at' => now()->toIso8601String(),
            ]
        ];

        $secret = config('services.workshop.hmac_secret') ?: env('HMAC_SECRET', 'default_hmac_secret');
        $signature = hash_hmac('sha256', json_encode($payload), $secret);

        $response = Http::timeout(10)->withHeaders([
            'X-Finlog-Signature' => $signature,
            'X-Finlog-Event-Id' => uniqid('evt_'),
            'X-Finlog-Timestamp' => now()->timestamp,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->post($purchaseRequest->callback_webhook_url, $payload);

        return [
            'success' => $response->successful(),
            'status_code' => $response->status(),
            'body' => $response->body(),
            'payload' => $payload,
        ];
    }
}
