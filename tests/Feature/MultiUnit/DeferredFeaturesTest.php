<?php

namespace Tests\Feature\MultiUnit;

use App\Http\Controllers\AiAssistantController;
use App\Jobs\SendWebhookToWorkshopJob;
use App\Models\PurchaseRequest;
use App\Models\WebhookEventLog;
use App\Services\FinlogWebhookSenderService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\MultiUnitTestCase;

class DeferredFeaturesTest extends MultiUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['finlog.purchase_request_unit' => 'unit_a', 'services.workshop.api_token' => 'token-uji', 'services.workshop.hmac_secret' => 'rahasia-uji']);
    }

    private function purchaseRequest($unit, string $number): PurchaseRequest
    {
        return $this->context()->run($unit, fn () => PurchaseRequest::forceCreate([
            'finlog_request_id' => "FIN-{$number}",
            'request_number' => $number,
            'primary_work_order_id' => '1',
            'primary_spk_number' => 'SPK',
            'requested_by_user_id' => 1,
            'requested_by_name' => 'Uji',
            'requested_by_role' => 'admin',
            'type' => 'SHOPPING',
            'total_estimated_cost' => 1000,
            'callback_webhook_url' => 'https://contoh.test/webhook',
            'status' => 'PENDING',
            'received_at' => now(),
        ]));
    }

    public function test_purchase_request_menu_exists_only_on_the_configured_unit(): void
    {
        $user = $this->userWithRole('staff', [$this->unitA, $this->unitB]);

        $this->actingInUnit($user, $this->unitA)->get('/app/purchase-requests')->assertOk();
        $this->actingInUnit($user, $this->unitB)->get('/app/purchase-requests')->assertNotFound();
    }

    public function test_api_without_valid_token_is_rejected(): void
    {
        $this->postJson('/api/v1/purchase-requests', [])->assertUnauthorized();
        $this->withToken('salah')->postJson('/api/v1/purchase-requests', [])->assertUnauthorized();
    }

    public function test_api_rejects_everything_when_no_token_is_configured(): void
    {
        config(['services.workshop.api_token' => null]);

        $this->withToken('default_secret_token')->postJson('/api/v1/purchase-requests', [])->assertUnauthorized();
    }

    public function test_api_activates_the_configured_unit(): void
    {
        // Isi permintaan sengaja tidak lengkap: yang diuji di sini hanya unit yang diaktifkan.
        $response = $this->withToken('token-uji')->postJson('/api/v1/purchase-requests', []);

        $this->assertNotContains($response->getStatusCode(), [401, 503]);
        $this->assertSame('unit_a', $this->context()->current()->code);
    }

    public function test_webhook_job_runs_on_its_own_unit_even_with_another_context(): void
    {
        Http::fake(['*' => Http::response(['ok' => true])]);

        $request = $this->purchaseRequest($this->unitA, 'PR-A');
        $this->context()->activate($this->unitB);

        (new SendWebhookToWorkshopJob($request->id, 'unit_a'))->handle(app(FinlogWebhookSenderService::class), $this->context());

        $this->assertSame('unit_b', $this->context()->current()->code);
        $this->assertSame(1, $this->context()->run($this->unitA, fn () => WebhookEventLog::count()));
        $this->assertSame(0, WebhookEventLog::count());
        Http::assertSentCount(1);
    }

    public function test_status_update_queues_job_with_id_and_unit(): void
    {
        Queue::fake();
        $request = $this->purchaseRequest($this->unitA, 'PR-A');
        $user = $this->userWithRole('staff', [$this->unitA]);

        $this->actingInUnit($user, $this->unitA)->post("/app/purchase-requests/{$request->id}/status", ['status' => 'APPROVED']);

        Queue::assertPushed(SendWebhookToWorkshopJob::class, fn ($job) => $job->purchaseRequestId === $request->id && $job->unitCode === 'unit_a');
    }

    public function test_webhook_refuses_to_sign_without_a_secret(): void
    {
        config(['services.workshop.hmac_secret' => null]);
        $request = $this->purchaseRequest($this->unitA, 'PR-A');

        $this->expectException(\RuntimeException::class);

        $this->context()->run($this->unitA, fn () => app(FinlogWebhookSenderService::class)->sendStatusUpdate($request));
    }

    public function test_ai_prompt_names_the_active_unit(): void
    {
        $this->context()->activate($this->unitB);
        $this->actingAs($this->userWithRole('finance', [$this->unitB]));

        $method = new \ReflectionMethod(AiAssistantController::class, 'getSystemPrompt');

        $this->assertStringContainsString($this->unitB->name, $method->invoke(new AiAssistantController));
    }
}
