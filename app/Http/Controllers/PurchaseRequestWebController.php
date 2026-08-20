<?php

namespace App\Http\Controllers;

use App\Models\PurchaseRequest;
use Inertia\Inertia;
use Illuminate\Http\Request;

class PurchaseRequestWebController extends Controller
{
    public function index(Request $request)
    {
        $query = PurchaseRequest::with('items', 'webhookLogs')->latest('received_at');

        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->has('search') && $request->search !== '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('request_number', 'like', "%{$search}%")
                  ->orWhere('primary_spk_number', 'like', "%{$search}%")
                  ->orWhere('requested_by_name', 'like', "%{$search}%");
            });
        }

        $purchaseRequests = $query->paginate(10)->withQueryString();

        return Inertia::render('PurchaseRequests/Index', [
            'purchaseRequests' => $purchaseRequests,
            'filters' => $request->only(['status', 'search'])
        ]);
    }
}
