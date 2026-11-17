<?php

namespace App\Http\Controllers;

use App\Models\PurchaseRequest;
use App\Jobs\SendWebhookToWorkshopJob;
use Illuminate\Http\Request;

class PurchaseRequestStatusController extends Controller
{
    public function updateStatus(Request $request, PurchaseRequest $purchaseRequest)
    {
        $validated = $request->validate([
            'status' => 'required|in:APPROVED,REJECTED,PURCHASED,RECEIVED,CANCELLED',
            'rejection_reason' => 'required_if:status,REJECTED|string|nullable'
        ]);

        $status = $validated['status'];

        $updateData = ['status' => $status];

        switch ($status) {
            case 'APPROVED':
                $updateData['approved_at'] = now();
                $updateData['approved_by'] = auth()->id() ?? 1;
                break;
            case 'REJECTED':
                $updateData['rejected_at'] = now();
                $updateData['rejection_reason'] = $validated['rejection_reason'] ?? null;
                break;
            case 'PURCHASED':
                $updateData['purchased_at'] = now();
                break;
            case 'RECEIVED':
                $updateData['received_material_at'] = now();
                break;
        }

        $purchaseRequest->update($updateData);

        // Trigger Webhook to Workshop
        SendWebhookToWorkshopJob::dispatch($purchaseRequest);

        // Since we don't have the full UI yet, we can return JSON or redirect
        if ($request->wantsJson()) {
            return response()->json(['message' => "Status updated to {$status} and webhook triggered."]);
        }

        return redirect()->back()->with('success', "Status updated to {$status} and webhook triggered.");
    }
}
