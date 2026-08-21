<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$json = '{
  "request_number": "REQ-2026-0002",
  "is_batch": false,
  "total_spks": 1,
  "spk_list": [
    {
      "work_order_id": 9543,
      "spk_number": "S-2608-13-0541-RQ"
    }
  ],
  "primary_work_order_id": null,
  "primary_spk_number": null,
  "type": "PRODUCTION_PO",
  "requested_by": {
    "user_id": 80,
    "name": "Dena",
    "role": "admin"
  },
  "items": [
    {
      "item_id": 2,
      "work_order_id": 9543,
      "spk_number": "S-2608-13-0541-RQ",
      "material_id": 220,
      "material_name": "MAESREO HITAM",
      "specification": "-",
      "quantity": 1,
      "unit": "pcs/pasang",
      "estimated_price": 17500,
      "subtotal": 17500
    }
  ],
  "total_estimated_cost": 17500,
  "notes": "Pengajuan gabungan untuk 1 SPK.",
  "callback_webhook_url": "https://staging-info.shoeworkshop.id/api/v1/webhooks/finlog/purchase-status",
  "requested_at": "2026-08-20T15:54:28+07:00"
}';

$request = Illuminate\Http\Request::create('/api/v1/purchase-requests', 'POST', [], [], [], [
    'HTTP_Idempotency-Key' => 'test-idempotency-123',
    'HTTP_Authorization' => 'Bearer ' . env('WORKSHOP_API_TOKEN', 'default_secret_token')
], $json);
$request->headers->set('Content-Type', 'application/json');

$response = app()->handle($request);
echo "Status: " . $response->getStatusCode() . "\n";
echo "Content: " . $response->getContent() . "\n";
