<?php

namespace App\Http\Middleware;

use App\Models\Central\Unit;
use App\Models\User;
use App\Support\UnitContext;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Defines the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'profile_photo_url' => $user->profile_photo_url,
                    'roles' => $user->getRoleNames()->toArray(),
                ] : null,
            ],
            'unit' => fn () => $user ? $this->unitProps($user) : null,
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'warning' => fn () => $request->session()->get('warning'),
                'info' => fn () => $request->session()->get('info'),
            ],
        ]);
    }

    private function unitProps(User $user): array
    {
        $active = app(UnitContext::class)->current();

        return [
            'active' => $active ? [
                'code' => $active->code,
                'name' => $active->name,
                'status' => $active->status,
            ] : null,
            'available' => $user->accessibleUnits()
                ->map(fn (Unit $unit) => ['id' => $unit->id, 'code' => $unit->code, 'name' => $unit->name, 'status' => $unit->status])
                ->values(),
            'canViewConsolidated' => $user->canViewConsolidated(),
            'hasPurchaseRequests' => $active?->code === config('finlog.purchase_request_unit'),
        ];
    }
}
