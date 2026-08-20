<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PurchaseRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PurchaseRequestController extends Controller
{
    public function receive(Request $request)
    {
        $idempotencyKey = $request->header('Idempotency-Key');

        if (!$idempotencyKey) {
            return response()->json([
                'message' => 'Idempotency-Key header is required.'
            ], 400);
        }

        // Check for existing request to handle idempotency correctly
        $existingRequest = PurchaseRequest::where('idempotency_key', $idempotencyKey)->first();

        if ($existingRequest) {
            return response()->json([
                'message' => 'Purchase request already exists.',
                'data' => [
                    'finlog_request_id' => $existingRequest->finlog_request_id,
                    'status' => $existingRequest->status,
                ]
            ], 409); // Conflict, which workshop app expects and handles as success
        }

        // Validate payload based on Workshop JSON format
        $validatedData = $request->validate([
            'request_number' => 'required|string',
            'is_batch' => 'required|boolean',
            'total_spks' => 'required|integer',
            'spk_list' => 'nullable|array',
            'primary_work_order_id' => 'nullable|integer',
            'primary_spk_number' => 'nullable|string',
            'type' => 'required|in:SHOPPING,PRODUCTION_PO',
            'requested_by' => 'required|array',
            'requested_by.user_id' => 'required|integer',
            'requested_by.name' => 'required|string',
            'requested_by.role' => 'required|string',
            'items' => 'required|array|min:1',
            'items.*.material_id' => 'required|integer',
            'items.*.material_name' => 'required|string',
            'items.*.specification' => 'nullable|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit' => 'required|string',
            'items.*.estimated_price' => 'required|numeric|min:0',
            'items.*.subtotal' => 'required|numeric|min:0',
            'items.*.work_order_id' => 'nullable|integer',
            'items.*.spk_number' => 'nullable|string',
            'items.*.item_id' => 'nullable|integer', // workshop_item_id
            'total_estimated_cost' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
            'callback_webhook_url' => 'required|url',
            'requested_at' => 'required|date',
        ]);

        try {
            DB::beginTransaction();

            $finlogRequestId = 'FLG-REQ-' . strtoupper(Str::random(8));

            $purchaseRequest = PurchaseRequest::create([
                'finlog_request_id' => $finlogRequestId,
                'request_number' => $validatedData['request_number'],
                'is_batch' => $validatedData['is_batch'],
                'total_spks' => $validatedData['total_spks'],
                'spk_list' => $validatedData['spk_list'],
                'primary_work_order_id' => $validatedData['primary_work_order_id'],
                'primary_spk_number' => $validatedData['primary_spk_number'],
                'type' => $validatedData['type'],
                'status' => 'PENDING',
                'requested_by_user_id' => $validatedData['requested_by']['user_id'],
                'requested_by_name' => $validatedData['requested_by']['name'],
                'requested_by_role' => $validatedData['requested_by']['role'],
                'total_estimated_cost' => $validatedData['total_estimated_cost'],
                'notes' => $validatedData['notes'] ?? null,
                'callback_webhook_url' => $validatedData['callback_webhook_url'],
                'idempotency_key' => $idempotencyKey,
                'payload_raw' => $request->all(),
                'received_at' => now(),
            ]);

            foreach ($validatedData['items'] as $item) {
                $purchaseRequest->items()->create([
                    'workshop_item_id' => $item['item_id'] ?? null,
                    'work_order_id' => $item['work_order_id'] ?? null,
                    'spk_number' => $item['spk_number'] ?? null,
                    'material_id' => $item['material_id'],
                    'material_name' => $item['material_name'],
                    'specification' => $item['specification'] ?? null,
                    'quantity' => $item['quantity'],
                    'unit' => $item['unit'],
                    'estimated_price' => $item['estimated_price'],
                    'subtotal' => $item['subtotal'],
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Purchase request received successfully.',
                'data' => [
                    'finlog_request_id' => $purchaseRequest->finlog_request_id,
                    'status' => $purchaseRequest->status,
                ]
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to process purchase request.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
