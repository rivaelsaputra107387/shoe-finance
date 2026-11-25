<?php

namespace Tests\Feature;

use App\Models\FiscalPeriod;
use App\Models\User;
use Database\Seeders\AccountSeeder;
use Database\Seeders\FiscalPeriodSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

class ReportPdfPresentationTest extends TestCase
{
    use RefreshDatabase;

    private function equityData(array $overrides = []): array
    {
        return array_merge([
            'period_name' => 'Januari 2030',
            'beginning_capital' => -30000000.0,
            'retained_earnings' => -30000000.0,
            'additional_capital' => 0.0,
            'net_profit' => 12500000.5,
            'prive' => 20000000.0,
            'ending_capital' => -37499999.5,
            'modal_account_name' => 'Modal Disetor',
            'prive_account_name' => 'Prive',
        ], $overrides);
    }

    public function test_equity_pdf_shows_opening_capital_as_components_that_add_up(): void
    {
        $html = view('reports.equity-statement-pdf', ['data' => $this->equityData(), 'title' => 'LAPORAN PERUBAHAN EKUITAS'])->render();

        $this->assertStringNotContainsString('Modal Disetor (Awal Periode)', $html);
        $this->assertStringContainsString('Total Modal Awal Periode', $html);
        // Modal Disetor awal = modal awal − laba ditahan = 0, laba ditahan tampil sekali sebagai komponen.
        $this->assertStringContainsString('Rp 0,00', $html);
        $this->assertSame(2, substr_count($html, '(Rp 30.000.000,00)'));
        $this->assertStringContainsString('(Rp 7.499.999,50)', $html);
        $this->assertStringContainsString('(Rp 37.499.999,50)', $html);
    }

    public function test_equity_pdf_shows_additional_capital(): void
    {
        $html = view('reports.equity-statement-pdf', ['data' => $this->equityData([
            'beginning_capital' => 50000000.0,
            'retained_earnings' => 10000000.0,
            'additional_capital' => 5000000.0,
            'net_profit' => 1000000.0,
            'prive' => 0.0,
            'ending_capital' => 56000000.0,
        ])])->render();

        $this->assertStringContainsString('Rp 40.000.000,00', $html);
        $this->assertStringContainsString('Setoran Modal Disetor (+)', $html);
        $this->assertStringContainsString('Rp 5.000.000,00', $html);
        $this->assertStringContainsString('Rp 56.000.000,00', $html);
    }

    public function test_pdf_footer_uses_wib_time(): void
    {
        Carbon::setTestNow(Carbon::parse('2030-01-15 07:37:00', 'UTC'));

        $html = view('reports.equity-statement-pdf', ['data' => $this->equityData()])->render();

        $this->assertStringContainsString('Dicetak pada: 15/01/2030 14:37 WIB', $html);

        Carbon::setTestNow();
    }

    public function test_cash_flow_pdf_hides_zero_rows_and_sorts_by_account_code(): void
    {
        $html = view('reports.cash-flow-statement-pdf', ['data' => [
            'period_name' => 'Januari 2030',
            'is_valid' => true,
            'operating' => [
                ['account_code' => 7120, 'account_name' => 'Pendapatan Ongkir', 'amount' => 1500000],
                ['account_code' => 1180, 'account_name' => 'ayat silang', 'amount' => 0],
                ['account_code' => 4170, 'account_name' => 'Pendapatan Jasa', 'amount' => 9000000],
            ],
            'investing' => [],
            'financing' => [],
            'total_operating' => 10500000,
            'total_investing' => 0,
            'total_financing' => 0,
            'net_increase' => 10500000,
            'beginning_cash' => 0,
            'ending_cash' => 10500000,
        ]])->render();

        $this->assertStringNotContainsString('ayat silang', $html);
        $this->assertLessThan(strpos($html, 'Pendapatan Ongkir'), strpos($html, 'Pendapatan Jasa'));
    }

    public function test_each_pdf_export_has_its_own_title(): void
    {
        $this->seed([RolePermissionSeeder::class, FiscalPeriodSeeder::class, AccountSeeder::class]);
        $finance = User::factory()->create()->assignRole('finance');
        $period = FiscalPeriod::orderBy('start_date')->firstOrFail();

        $titles = [];
        View::composer('reports.layout-pdf', function ($view) use (&$titles) {
            $titles[] = $view->getData()['title'] ?? null;
        });

        foreach (['trial-balance', 'income-statement', 'balance-sheet', 'equity-statement', 'cash-flow'] as $report) {
            $this->actingAs($finance)
                ->get(route("reports.{$report}.export", ['fiscal_period_id' => $period->id, 'format' => 'pdf']))
                ->assertOk();
        }

        $this->assertSame(['NERACA LAJUR', 'LAPORAN LABA RUGI', 'LAPORAN NERACA', 'LAPORAN PERUBAHAN EKUITAS', 'LAPORAN ARUS KAS'], $titles);
    }
}
